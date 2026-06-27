<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ambulance;
use App\Models\DeviceToken;
use App\Models\DispatchAssignment;
use App\Models\HospitalEndorsement;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * S11 — LGU performance metrics. Read-only aggregation over existing tables;
 * no writes, no new schema. All queries use the builder (bound params, no raw concat).
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        // Default window: last 30 days. Native <input type="date"> feeds these.
        $to = $this->parseDate($request->query('to')) ?? Carbon::today()->endOfDay();
        $from = $this->parseDate($request->query('from')) ?? $to->copy()->subDays(30)->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        $to = $to->endOfDay();
        $from = $from->startOfDay();

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'responseKpis' => $this->responseKpis($from, $to),
            'volume' => $this->volumeOutcomes($from, $to),
            'fleet' => $this->fleetUtilization($from, $to),
            'safety' => $this->safetyAbuse($from, $to),
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Response-time KPIs: minutes across dispatch milestones, avg + median. */
    private function responseKpis(Carbon $from, Carbon $to): array
    {
        $rows = DispatchAssignment::query()
            ->whereBetween('assigned_at', [$from, $to])
            ->get(['assigned_at', 'accepted_at', 'arrived_on_scene_at', 'arrived_at_hospital_at']);

        $legs = [
            'Assign → accept' => fn ($r) => $this->minutesBetween($r->assigned_at, $r->accepted_at),
            'Assign → on-scene' => fn ($r) => $this->minutesBetween($r->assigned_at, $r->arrived_on_scene_at),
            'On-scene → hospital' => fn ($r) => $this->minutesBetween($r->arrived_on_scene_at, $r->arrived_at_hospital_at),
            'Assign → hospital (total)' => fn ($r) => $this->minutesBetween($r->assigned_at, $r->arrived_at_hospital_at),
        ];

        $out = [];
        foreach ($legs as $label => $calc) {
            $values = $rows->map($calc)->filter(fn ($m) => $m !== null)->values();
            $out[] = [
                'label' => $label,
                'count' => $values->count(),
                'avg' => $values->isEmpty() ? null : round($values->avg(), 1),
                'median' => $this->median($values),
            ];
        }

        return ['dispatches' => $rows->count(), 'legs' => $out];
    }

    private function minutesBetween($start, $end): ?float
    {
        if (! $start || ! $end) {
            return null;
        }
        $mins = $start->diffInSeconds($end, false) / 60;

        return $mins >= 0 ? $mins : null; // ignore clock-skew negatives
    }

    /** ponytail: PHP-side median, push to SQL percentile if rows grow. */
    private function median(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }
        $sorted = $values->sort()->values();
        $mid = intdiv($sorted->count(), 2);

        $median = $sorted->count() % 2
            ? $sorted[$mid]
            : ($sorted[$mid - 1] + $sorted[$mid]) / 2;

        return round($median, 1);
    }

    /** Volume & outcomes: incidents by status / type / severity in the window. */
    private function volumeOutcomes(Carbon $from, Carbon $to): array
    {
        $base = fn () => Incident::query()->whereBetween('created_at', [$from, $to]);

        return [
            'total' => $base()->count(),
            'byStatus' => $base()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all(),
            'byType' => $base()->selectRaw('request_type, count(*) as c')->groupBy('request_type')->pluck('c', 'request_type')->all(),
            'bySeverity' => $base()->selectRaw('severity, count(*) as c')->groupBy('severity')->orderBy('severity')->pluck('c', 'severity')->all(),
            'completed' => (clone $base())->where('status', 'completed')->count(),
            'resolvedOnScene' => (clone $base())->where('status', 'resolved_on_scene')->count(),
            'cancelled' => (clone $base())->where('status', 'cancelled')->count(),
            'falseAlarm' => (clone $base())->where('is_flagged_for_abuse', true)->count(),
        ];
    }

    /** Fleet utilization: dispatch counts per ambulance + live status snapshot. */
    private function fleetUtilization(Carbon $from, Carbon $to): array
    {
        $perUnit = DispatchAssignment::query()
            ->whereBetween('assigned_at', [$from, $to])
            ->selectRaw('ambulance_id, count(*) as dispatches')
            ->groupBy('ambulance_id')
            ->orderByDesc('dispatches')
            ->limit(15)
            ->get();

        $units = Ambulance::whereIn('id', $perUnit->pluck('ambulance_id'))
            ->get(['id', 'unit_code', 'plate_no'])->keyBy('id');

        return [
            'busiest' => $perUnit->map(fn ($r) => [
                'unit' => $units[$r->ambulance_id]->unit_code ?? $units[$r->ambulance_id]->plate_no ?? "#{$r->ambulance_id}",
                'dispatches' => $r->dispatches,
            ])->all(),
            'statusSnapshot' => Ambulance::query()->where('is_archived', false)
                ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all(),
        ];
    }

    /** Safety & abuse: strike trends, blocks, handoff acceptance. */
    private function safetyAbuse(Carbon $from, Carbon $to): array
    {
        $endorsements = HospitalEndorsement::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $accepted = (int) ($endorsements['accepted'] ?? 0);
        $declined = (int) ($endorsements['declined'] ?? 0);
        $decided = $accepted + $declined;

        return [
            'flaggedIncidents' => Incident::whereBetween('created_at', [$from, $to])
                ->where('is_flagged_for_abuse', true)->count(),
            'blockedDevices' => DeviceToken::where('is_blocked', true)->count(),
            'devicesWithStrikes' => DeviceToken::where('false_alarm_count', '>', 0)->count(),
            'totalStrikes' => (int) DeviceToken::sum('false_alarm_count'),
            'handoffAccepted' => $accepted,
            'handoffDeclined' => $declined,
            'handoffAcceptanceRate' => $decided ? round($accepted / $decided * 100, 1) : null,
        ];
    }
}
