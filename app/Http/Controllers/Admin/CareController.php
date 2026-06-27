<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medical\StoreVitalsRequest;
use App\Models\Incident;
use App\Models\Patient;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareController extends Controller
{
    public function show(Incident $incident): View
    {
        $incident->load('vitals', 'treatments', 'prehospitalNotes', 'patient', 'activeAssignment.ambulance');

        return view('admin.care.show', compact('incident'));
    }

    public function storeVitals(StoreVitalsRequest $request, Incident $incident): RedirectResponse
    {
        $incident->vitals()->create($request->validated() + [
            'recorded_at' => now(), 'created_by' => $request->user()->id,
        ]);
        AuditLog::record('care.vitals_recorded', Incident::class, $incident->id);

        return back()->with('status', 'Vitals recorded.');
    }

    public function storeTreatment(Request $request, Incident $incident): RedirectResponse
    {
        $data = $request->validate([
            'treatment_type' => ['required', 'string', 'max:150'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);
        $incident->treatments()->create($data + [
            'performed_at' => now(), 'created_by' => $request->user()->id,
        ]);
        AuditLog::record('care.treatment_recorded', Incident::class, $incident->id);

        return back()->with('status', 'Treatment recorded.');
    }

    public function storeNote(Request $request, Incident $incident): RedirectResponse
    {
        $data = $request->validate([
            'note_type' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:2000'],
        ]);
        $incident->prehospitalNotes()->create($data + [
            'created_by' => $request->user()->id, 'created_at' => now(),
        ]);
        AuditLog::record('care.note_added', Incident::class, $incident->id);

        return back()->with('status', 'Note added.');
    }

    public function upsertPatient(Request $request, Incident $incident): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['nullable', 'string', 'max:150'],
            'sex' => ['nullable', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        Patient::updateOrCreate(
            ['incident_id' => $incident->id],
            $data + ['created_by' => $request->user()->id],
        );
        AuditLog::record('care.patient_updated', Incident::class, $incident->id);

        return back()->with('status', 'Patient details saved.');
    }

    public function resolveOnScene(Request $request, Incident $incident): RedirectResponse
    {
        $incident->update(['status' => 'resolved_on_scene', 'resolved_on_scene_at' => now()]);
        $incident->activeAssignment?->ambulance?->update(['status' => 'available']);

        $incident->updates()->create([
            'dispatch_assignment_id' => $incident->activeAssignment?->id,
            'status' => 'resolved_on_scene',
            'care_status' => 'resolved_on_scene',
            'update_type' => 'resolved_on_scene',
            'note' => 'Resolved on scene — no transport required.',
            'visibility' => 'public',
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);
        AuditLog::record('care.resolved_on_scene', Incident::class, $incident->id);

        return back()->with('status', 'Incident resolved on scene.');
    }
}
