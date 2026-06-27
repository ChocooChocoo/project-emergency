<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dispatch\StoreAssignmentRequest;
use App\Models\Ambulance;
use App\Models\DispatchAssignment;
use App\Models\Incident;
use App\Services\AuditLog;
use App\Services\DssService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DispatchController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'queue'); // queue | scheduled | active

        $pending = Incident::query()
            ->with('organization')
            ->where('status', 'pending')
            ->where('is_archived', false)
            ->when($tab === 'scheduled',
                fn ($q) => $q->where('request_type', 'scheduled'),
                fn ($q) => $q->where('request_type', '!=', 'scheduled'))
            ->orderByDesc('severity')->orderBy('created_at')
            ->paginate(15)->withQueryString();

        $active = DispatchAssignment::query()
            ->with('incident', 'ambulance', 'organization', 'driver')
            ->whereNotIn('status', ['completed', 'cancelled', 'reassigned', 'timed_out'])
            ->orderByDesc('id')->limit(50)->get();

        return view('admin.dispatch.index', compact('pending', 'active', 'tab'));
    }

    public function show(Incident $incident): View
    {
        $incident->load('organization', 'user', 'guest', 'updates', 'activeAssignment.ambulance', 'activeAssignment.driver');

        return view('admin.dispatch.show', [
            'incident' => $incident,
            'ranked' => DssService::rank($incident),
        ]);
    }

    public function store(StoreAssignmentRequest $request, Incident $incident): RedirectResponse
    {
        if ($incident->status !== 'pending') {
            return back()->with('error', 'This incident is no longer pending dispatch.');
        }

        $ambulance = Ambulance::with('organization')->findOrFail($request->integer('ambulance_id'));

        if ($ambulance->status !== 'available' || $ambulance->is_archived || ! $ambulance->is_serviceable) {
            return back()->with('error', 'Selected ambulance is not available.');
        }

        // R7 — an assignment's org must match its ambulance's org. Org is taken from the
        // ambulance (not user input) so they can never drift.
        $orgId = $ambulance->organization_id;

        DB::transaction(function () use ($incident, $ambulance, $orgId, $request) {
            $assignment = DispatchAssignment::create([
                'incident_id' => $incident->id,
                'organization_id' => $orgId,
                'dispatcher_user_id' => $request->user()->id,
                'assigned_by' => $request->user()->id,
                'ambulance_id' => $ambulance->id,
                'driver_user_id' => $request->integer('driver_user_id'),
                'status' => 'assigned',
                'dss_rank' => $request->integer('dss_rank') ?: null,
                'assigned_at' => now(),
                'response_deadline_at' => now()->addSeconds($this->dssTimeoutSeconds()),
                'dispatch_notes' => $request->input('dispatch_notes'),
            ]);

            $incident->update(['status' => 'dispatched', 'organization_id' => $orgId]);
            $ambulance->update(['status' => 'dispatched']);

            $incident->updates()->create([
                'dispatch_assignment_id' => $assignment->id,
                'status' => 'dispatched',
                'update_type' => 'dispatched',
                'note' => "Unit {$ambulance->plate_no} assigned by {$ambulance->organization?->name}.",
                'visibility' => 'public',
                'created_by' => $request->user()->id,
                'created_at' => now(),
            ]);

            AuditLog::record('dispatch.assigned', DispatchAssignment::class, $assignment->id);
        });

        return redirect()->route('admin.dispatch.show', $incident)
            ->with('status', "Unit {$ambulance->plate_no} dispatched.");
    }

    public function reassign(Request $request, DispatchAssignment $assignment): RedirectResponse
    {
        DB::transaction(function () use ($assignment, $request) {
            $assignment->update(['status' => 'reassigned', 'ended_at' => now()]);
            $assignment->ambulance?->update(['status' => 'available']);
            $assignment->incident?->update(['status' => 'pending']);

            $assignment->incident?->updates()->create([
                'dispatch_assignment_id' => $assignment->id,
                'status' => 'pending',
                'update_type' => 'reassigned',
                'note' => 'Assignment released for reassignment.',
                'visibility' => 'organization',
                'created_by' => $request->user()->id,
                'created_at' => now(),
            ]);

            AuditLog::record('dispatch.reassigned', DispatchAssignment::class, $assignment->id);
        });

        return back()->with('status', 'Assignment released. Incident reopened for dispatch.');
    }

    /** DSS countdown, tunable by LGU via system_configurations (R10). Defaults to 60s. */
    private function dssTimeoutSeconds(): int
    {
        $value = DB::table('system_configurations')
            ->where('scope', 'global')->where('config_key', 'dss_timeout_seconds')
            ->value('config_value');

        return (int) ($value ?: 60);
    }
}
