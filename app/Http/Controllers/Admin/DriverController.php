<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AmbulanceLocation;
use App\Models\DispatchAssignment;
use App\Models\DriverDutyState;
use App\Services\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function duty(Request $request): View
    {
        $driverId = $request->user()->id;

        $duty = DriverDutyState::with('ambulance')->firstWhere('driver_user_id', $driverId);

        $assignments = DispatchAssignment::query()
            ->with('incident', 'ambulance')
            ->where('driver_user_id', $driverId)
            ->whereNotIn('status', ['completed', 'cancelled', 'reassigned', 'timed_out'])
            ->orderByDesc('id')->get();

        return view('admin.driver.duty', compact('duty', 'assignments'));
    }

    public function updateDuty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:on_duty,off_duty,break'],
            'ambulance_id' => ['nullable', 'integer', 'exists:ambulances,id'],
        ]);

        DriverDutyState::updateOrCreate(
            ['driver_user_id' => $request->user()->id],
            ['status' => $data['status'], 'ambulance_id' => $data['ambulance_id'] ?? null, 'started_at' => now()],
        );

        AuditLog::record('driver.duty.'.$data['status']);

        return back()->with('status', 'Duty status updated.');
    }

    public function assignment(Request $request, DispatchAssignment $assignment): View
    {
        $this->authorizeDriver($request, $assignment);
        $assignment->load('incident', 'ambulance', 'organization');

        return view('admin.driver.assignment', [
            'assignment' => $assignment,
            'next' => $this->nextStatus($assignment->status),
        ]);
    }

    /**
     * Step the assignment one stage along DispatchAssignment::FLOW, stamping the matching
     * timestamp and mirroring the milestone onto the incident. Illegal jumps are rejected.
     *
     * ponytail: linear whitelist machine — no rejection/timeout branches (those are the
     * queued-job upgrade path). Driver advances forward only.
     */
    public function advance(Request $request, DispatchAssignment $assignment): RedirectResponse
    {
        $this->authorizeDriver($request, $assignment);

        $next = $this->nextStatus($assignment->status);
        if (! $next) {
            return back()->with('error', 'Assignment is already complete.');
        }

        [$stamp, $incidentStatus] = DispatchAssignment::MILESTONES[$next];

        DB::transaction(function () use ($assignment, $next, $stamp, $incidentStatus, $request) {
            $assignment->update(['status' => $next, $stamp => now()]);

            $incident = $assignment->incident;
            $incident?->update(['status' => $incidentStatus]
                + ($next === 'completed' ? ['completed_at' => now()] : []));

            if ($next === 'completed') {
                $assignment->ambulance?->update(['status' => 'available']);
            }

            $incident?->updates()->create([
                'dispatch_assignment_id' => $assignment->id,
                'status' => $incidentStatus,
                'update_type' => $next,
                'note' => 'Driver: '.ucwords(str_replace('_', ' ', $next)).'.',
                'visibility' => 'public',
                'created_by' => $request->user()->id,
                'created_at' => now(),
            ]);

            AuditLog::record('driver.advance.'.$next, DispatchAssignment::class, $assignment->id);
        });

        return back()->with('status', 'Status advanced to '.ucwords(str_replace('_', ' ', $next)).'.');
    }

    public function pushLocation(Request $request, DispatchAssignment $assignment): JsonResponse
    {
        $this->authorizeDriver($request, $assignment);

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        AmbulanceLocation::create([
            'ambulance_id' => $assignment->ambulance_id,
            'dispatch_assignment_id' => $assignment->id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'recorded_at' => now(),
        ]);

        $assignment->ambulance?->update([
            'last_lat' => $data['lat'], 'last_lng' => $data['lng'], 'last_seen_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Only the assigned driver (or a dispatcher) may act on an assignment. */
    private function authorizeDriver(Request $request, DispatchAssignment $assignment): void
    {
        $user = $request->user();
        abort_unless(
            $assignment->driver_user_id === $user->id || $user->hasPermission('dispatch-incidents'),
            403,
        );
    }

    private function nextStatus(string $current): ?string
    {
        $i = array_search($current, DispatchAssignment::FLOW, true);

        return ($i === false || $i + 1 >= count(DispatchAssignment::FLOW))
            ? null
            : DispatchAssignment::FLOW[$i + 1];
    }
}
