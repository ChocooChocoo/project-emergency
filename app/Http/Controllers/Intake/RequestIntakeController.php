<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Http\Requests\Intake\StoreIncidentRequest;
use App\Models\Incident;
use App\Services\AuditLog;
use App\Services\GuestSessionService;
use App\Services\StrikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RequestIntakeController extends Controller
{
    /** Merge radius for the Master Incident Ticket (R2). Docs: 50–150m; use upper bound. */
    private const MERGE_RADIUS_M = 150;

    /** Only group against very recent reports. */
    private const MERGE_WINDOW_MIN = 15;

    /** Persistent per-device identifier for anti-abuse strike tracking (R4). */
    private const DEVICE_COOKIE = 'device_uuid';

    public function create(): View
    {
        return view('request.create');
    }

    public function store(StoreIncidentRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        // R4 — block devices that have hit the false-alarm strike limit.
        $deviceUuid = $request->cookie(self::DEVICE_COOKIE) ?: (string) Str::uuid();
        if (StrikeService::isBlocked($deviceUuid)) {
            return back()->withInput()->with('error',
                'This device has been blocked from submitting requests due to repeated false alarms. Contact your LGU to appeal.');
        }

        // Resolve requester: registered user OR guest session — never both (R6).
        $guest = null;
        if (! $user) {
            $guest = GuestSessionService::resolveOrCreate($request);
            if (! $guest->hasQuotaRemaining()) {
                return back()->withInput()->with('error',
                    'Guest request limit reached. Please register to continue submitting requests.');
            }
        }

        $incident = DB::transaction(function () use ($data, $user, $guest) {
            $masterId = $this->findMasterTicket((float) $data['pickup_lat'], (float) $data['pickup_lng']);

            $incident = Incident::create($data + [
                'request_code' => 'REQ-'.strtoupper((string) Str::ulid()),
                'master_incident_id' => $masterId,
                'user_id' => $user?->id,
                'guest_id' => $guest?->id,
                'status' => 'pending',
                'severity' => $data['severity'] ?? ($data['request_type'] === 'one_tap' ? 2 : 4),
                'is_public_tracking' => true,
            ]);

            $incident->updates()->create([
                'status' => 'pending',
                'update_type' => 'created',
                'note' => $masterId ? 'Grouped into an existing nearby incident.' : 'Request received.',
                'visibility' => 'public',
                'created_by' => $user?->id,
                'created_at' => now(),
            ]);

            if ($guest) {
                GuestSessionService::consume($guest);
            }

            return $incident;
        });

        AuditLog::record('incident.created', Incident::class, $incident->id);

        $response = redirect()->route('request.track', $incident->request_code)
            ->with('status', "Request submitted. Your reference is {$incident->request_code}.");

        // Persist the guest key so the same device keeps its quota/session.
        if ($guest) {
            $response->withCookie(Cookie::make(GuestSessionService::COOKIE, $guest->guest_key, 60 * 24 * 365));
        }

        // Persist the device id so anti-abuse strikes (R4) stick to the device.
        $response->withCookie(Cookie::make(self::DEVICE_COOKIE, $deviceUuid, 60 * 24 * 365));

        return $response;
    }

    public function track(string $code): View
    {
        $incident = Incident::where('request_code', $code)->firstOrFail();

        return view('request.track', compact('incident'));
    }

    /**
     * S8 — public tracking JSON the track page polls (~10s). No realtime socket;
     * ponytail: polling. Reverb broadcasting is the documented upgrade path.
     */
    public function status(string $code): JsonResponse
    {
        $incident = Incident::with('activeAssignment.ambulance', 'activeAssignment.driver')
            ->where('request_code', $code)->firstOrFail();

        $a = $incident->activeAssignment;
        $unit = $a?->ambulance;

        return response()->json([
            'status' => $incident->status,
            'status_label' => ucwords(str_replace('_', ' ', $incident->status)),
            'eta_minutes' => $incident->eta_minutes,
            'assignment' => $a ? [
                'unit_status' => ucwords(str_replace('_', ' ', $a->status)),
                'plate_no' => $unit?->plate_no,
                'tier' => $unit?->tier ? strtoupper($unit->tier) : null,
                'driver_name' => $a->driver?->full_name,
                'driver_phone' => $a->driver?->phone,
                'last_lat' => $unit?->last_lat,
                'last_lng' => $unit?->last_lng,
                'last_seen_at' => $unit?->last_seen_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * S10 — citizen/guest cancellation never hard-cancels. It flags the incident for
     * field verification and keeps it pending (anti-abuse: a "cancelled" call could still
     * be a real emergency until a unit confirms on scene).
     */
    public function cancel(Request $request, string $code): RedirectResponse
    {
        $incident = Incident::where('request_code', $code)->firstOrFail();

        if (in_array($incident->status, ['completed', 'resolved_on_scene'], true)) {
            return back()->with('error', 'This request is already closed.');
        }

        DB::transaction(function () use ($incident, $request) {
            $incident->update([
                'status' => 'pending',
                'is_flagged_for_abuse' => false,
                'notes' => trim(($incident->notes ?? '')."\nCancellation requested — needs field verification."),
            ]);

            $incident->updates()->create([
                'status' => 'pending',
                'care_status' => 'needs_field_verification',
                'update_type' => 'cancellation_requested',
                'note' => 'Requester asked to cancel — held pending field verification.',
                'visibility' => 'organization',
                'created_by' => $request->user()?->id,
                'created_at' => now(),
            ]);
        });

        AuditLog::record('incident.cancellation_requested', Incident::class, $incident->id);

        return back()->with('status', 'Cancellation noted. A responder will verify before closing.');
    }

    /**
     * R2 — find an open incident within MERGE_RADIUS_M of this report and return its master id
     * (or its own id if it is already a master). Returns null if none nearby.
     *
     * ponytail: O(n) Haversine over the small set of recent open incidents — no spatial index.
     * Promote to a proper HeatmapAggregator + spatial index in S7 if intake volume grows.
     */
    private function findMasterTicket(float $lat, float $lng): ?int
    {
        $candidates = Incident::query()
            ->whereIn('status', Incident::OPEN_STATUSES)
            ->whereNotNull('pickup_lat')
            ->whereNotNull('pickup_lng')
            ->where('created_at', '>=', now()->subMinutes(self::MERGE_WINDOW_MIN))
            ->get(['id', 'master_incident_id', 'pickup_lat', 'pickup_lng']);

        foreach ($candidates as $c) {
            if ($this->haversineMeters($lat, $lng, (float) $c->pickup_lat, (float) $c->pickup_lng) <= self::MERGE_RADIUS_M) {
                return $c->master_incident_id ?? $c->id;
            }
        }

        return null;
    }

    /** Great-circle distance in meters. */
    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6_371_000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
