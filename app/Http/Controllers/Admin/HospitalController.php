<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HandoffSummary;
use App\Models\Hospital;
use App\Models\HospitalEndorsement;
use App\Models\Incident;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $hospitals = Hospital::query()
            ->where('is_archived', false)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.hospitals.index', compact('hospitals', 'search'));
    }

    public function create(): View
    {
        return view('admin.hospitals.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'facility_type' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity_status' => ['nullable', 'in:available,limited,full,unknown'],
            'available_beds' => ['nullable', 'integer', 'min:0'],
            'is_er_open' => ['nullable', 'boolean'],
        ]);

        $hospital = Hospital::create($data + ['created_by' => $request->user()->id, 'is_er_open' => (bool) ($data['is_er_open'] ?? true)]);
        AuditLog::record('hospital.created', Hospital::class, $hospital->id);

        return redirect()->route('admin.hospitals.show', $hospital)->with('status', "Hospital {$hospital->name} registered.");
    }

    public function show(Hospital $hospital): View
    {
        $hospital->load(['endorsements' => fn ($q) => $q->with('incident')->latest('id')->limit(20)]);

        return view('admin.hospitals.show', compact('hospital'));
    }

    /** Medic/dispatcher endorses a patient to a hospital — handoff begins as pending. */
    public function endorse(Request $request, Incident $incident): RedirectResponse
    {
        $data = $request->validate([
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($incident, $data, $request) {
            $endorsement = HospitalEndorsement::create([
                'dispatch_assignment_id' => $incident->activeAssignment?->id,
                'incident_id' => $incident->id,
                'hospital_id' => $data['hospital_id'],
                'endorsed_by' => $request->user()->id,
                'status' => 'pending',
                'handoff_status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            $incident->update(['destination_hospital_id' => $data['hospital_id']]);
            AuditLog::record('hospital.endorsed', HospitalEndorsement::class, $endorsement->id);
        });

        return back()->with('status', 'Patient endorsed to hospital.');
    }

    /** Hospital accepts or declines the endorsement. Decline reopens the choice. */
    public function respond(Request $request, HospitalEndorsement $endorsement): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accepted,declined'],
            'response_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $endorsement->update([
            'status' => $data['decision'],
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
            'response_notes' => $data['response_notes'] ?? null,
        ]);
        AuditLog::record('hospital.responded.'.$data['decision'], HospitalEndorsement::class, $endorsement->id);

        return back()->with('status', "Endorsement {$data['decision']}.");
    }

    /** Final handoff: completes the incident, frees the unit, writes the handoff summary. */
    public function confirmHandoff(Request $request, HospitalEndorsement $endorsement): RedirectResponse
    {
        if ($endorsement->status !== 'accepted') {
            return back()->with('error', 'Only an accepted endorsement can complete a handoff.');
        }

        DB::transaction(function () use ($endorsement, $request) {
            $incident = $endorsement->incident;

            $endorsement->update([
                'handoff_status' => 'completed',
                'handoff_confirmed_at' => now(),
                'completed_at' => now(),
            ]);

            HandoffSummary::updateOrCreate(
                ['incident_id' => $incident->id],
                [
                    'summary' => $request->input('summary', 'Patient handed off to hospital.'),
                    'outcome' => $request->input('outcome'),
                    'handoff_to' => $endorsement->hospital?->name,
                    'handoff_at' => now(),
                    'created_by' => $request->user()->id,
                ],
            );

            $incident->update(['status' => 'completed', 'completed_at' => now()]);
            $incident->activeAssignment?->update(['status' => 'completed', 'completed_at' => now(), 'handover_completed_at' => now()]);
            $incident->dispatchAssignments()->latest('id')->first()?->ambulance?->update(['status' => 'available']);

            $incident->updates()->create([
                'dispatch_assignment_id' => $endorsement->dispatch_assignment_id,
                'status' => 'completed',
                'care_status' => 'handoff_completed',
                'update_type' => 'handoff_completed',
                'note' => "Handoff completed at {$endorsement->hospital?->name}.",
                'visibility' => 'public',
                'created_by' => $request->user()->id,
                'created_at' => now(),
            ]);

            AuditLog::record('hospital.handoff_completed', HospitalEndorsement::class, $endorsement->id);
        });

        return back()->with('status', 'Handoff completed. Incident closed.');
    }
}
