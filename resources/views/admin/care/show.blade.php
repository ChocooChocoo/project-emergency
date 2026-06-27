@extends('layout.admin.app')

@section('title', 'Care — ' . $incident->request_code)

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">S9 — Pre-hospital care</div>
                    <h2 class="page-title">{{ $incident->request_code }}</h2>
                </div>
                <div class="col-auto btn-list">
                    <a href="{{ route('admin.incidents.show', $incident) }}" class="btn btn-link">Incident detail</a>
                    @if (! in_array($incident->status, ['completed', 'resolved_on_scene', 'cancelled']))
                        <button type="button" class="btn btn-outline-success"
                            onclick="confirmAction(() => document.getElementById('resolve-form').submit(), { type:'success', title:'Resolve on scene?', message:'Closes the case without transport.', confirm:'Resolve' })">
                            <i class="ti ti-circle-check me-1"></i>Resolve on scene
                        </button>
                        <form id="resolve-form" action="{{ route('admin.care.resolve', $incident) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">

                {{-- Patient panel --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Patient</h3></div>
                        <div class="card-body">
                            <form action="{{ route('admin.care.patient.upsert', $incident) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="mb-2"><label class="form-label">Full name</label>
                                    <input name="full_name" class="form-control" value="{{ $incident->patient?->full_name ?? $incident->patient_name }}"></div>
                                <div class="row g-2 mb-2">
                                    <div class="col"><label class="form-label">Sex</label>
                                        <select name="sex" class="form-select">
                                            <option value="">—</option>
                                            @foreach (['male','female','other'] as $s)
                                                <option value="{{ $s }}" @selected($incident->patient?->sex === $s)>{{ ucfirst($s) }}</option>
                                            @endforeach
                                        </select></div>
                                    <div class="col"><label class="form-label">Birth date</label>
                                        <input type="date" name="birth_date" class="form-control" value="{{ $incident->patient?->birth_date?->format('Y-m-d') }}"></div>
                                </div>
                                <div class="mb-2"><label class="form-label">Phone</label>
                                    <input name="phone" class="form-control" value="{{ $incident->patient?->phone ?? $incident->contact_number }}"></div>
                                <div class="mb-3"><label class="form-label">Address</label>
                                    <input name="address" class="form-control" value="{{ $incident->patient?->address }}"></div>
                                <button class="btn btn-primary w-100" type="submit">Save patient</button>
                            </form>
                        </div>
                    </div>

                    {{-- Hospital endorsement --}}
                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Hospital endorsement</h3></div>
                        <div class="card-body">
                            @forelse ($incident->endorsements ?? [] as $e)
                                <div class="mb-2 pb-2 border-bottom">
                                    <strong>{{ $e->hospital?->name }}</strong>
                                    <span class="badge bg-{{ $e->status === 'accepted' ? 'green' : ($e->status === 'declined' ? 'red' : 'yellow') }}-lt">{{ ucfirst($e->status) }}</span>
                                    @if ($e->handoff_status === 'completed')<span class="badge bg-green-lt">Handed off</span>@endif
                                </div>
                            @empty
                            @endforelse
                            <form action="{{ route('admin.hospitals.endorse', $incident) }}" method="POST" class="mt-2">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Endorse to hospital</label>
                                    <select name="hospital_id" class="form-select" required>
                                        <option value="">Choose hospital…</option>
                                        @foreach (\App\Models\Hospital::where('is_archived', false)->orderBy('name')->get() as $h)
                                            <option value="{{ $h->id }}">{{ $h->name }} — {{ ucfirst($h->capacity_status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-outline-primary w-100" type="submit">Endorse patient</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Vitals + treatments + notes --}}
                <div class="col-lg-8">
                    {{-- Vitals --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Vitals</h3>
                            <div class="card-actions"><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-vitals"><i class="ti ti-plus me-1"></i>Record</button></div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th>Time</th><th>BP</th><th>Pulse</th><th>RR</th><th>Temp</th><th>SpO₂</th><th>GCS</th></tr></thead>
                                    <tbody>
                                        @forelse ($incident->vitals as $v)
                                            <tr>
                                                <td class="text-secondary">{{ $v->recorded_at?->format('H:i') }}</td>
                                                <td>{{ $v->bp_systolic }}/{{ $v->bp_diastolic }}</td>
                                                <td>{{ $v->pulse_rate ?? '—' }}</td>
                                                <td>{{ $v->respiratory_rate ?? '—' }}</td>
                                                <td>{{ $v->temperature_c ?? '—' }}</td>
                                                <td>{{ $v->oxygen_saturation ?? '—' }}</td>
                                                <td>{{ $v->gcs_score ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-secondary py-3">No vitals recorded.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Treatments --}}
                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Treatments</h3></div>
                        <div class="card-body">
                            <form action="{{ route('admin.care.treatments.store', $incident) }}" method="POST" class="row g-2 mb-3">
                                @csrf
                                <div class="col-md-4"><input name="treatment_type" class="form-control" placeholder="Treatment" required></div>
                                <div class="col-md-6"><input name="details" class="form-control" placeholder="Details"></div>
                                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
                            </form>
                            <ul class="list-unstyled mb-0">
                                @forelse ($incident->treatments as $t)
                                    <li class="mb-1"><span class="text-secondary small me-2">{{ $t->performed_at?->format('H:i') }}</span><strong>{{ $t->treatment_type }}</strong> {{ $t->details }}</li>
                                @empty
                                    <li class="text-secondary">No treatments recorded.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Pre-hospital notes</h3></div>
                        <div class="card-body">
                            <form action="{{ route('admin.care.notes.store', $incident) }}" method="POST" class="row g-2 mb-3">
                                @csrf
                                <div class="col-md-3">
                                    <select name="note_type" class="form-select">
                                        @foreach (['general','assessment','intervention','handover'] as $nt)<option value="{{ $nt }}">{{ ucfirst($nt) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-7"><input name="content" class="form-control" placeholder="Note" required></div>
                                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
                            </form>
                            <ul class="list-unstyled mb-0">
                                @forelse ($incident->prehospitalNotes as $n)
                                    <li class="mb-1"><span class="badge bg-secondary-lt me-1">{{ ucfirst($n->note_type) }}</span>{{ $n->content }}</li>
                                @empty
                                    <li class="text-secondary">No notes recorded.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Vitals modal --}}
    <div class="modal modal-blur fade" id="modal-vitals" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.care.vitals.store', $incident) }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Record vitals</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-3"><label class="form-label">BP systolic</label><input type="number" name="bp_systolic" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">BP diastolic</label><input type="number" name="bp_diastolic" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Pulse</label><input type="number" name="pulse_rate" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Resp rate</label><input type="number" name="respiratory_rate" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Temp °C</label><input type="number" step="0.1" name="temperature_c" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">SpO₂ %</label><input type="number" name="oxygen_saturation" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Glucose</label><input type="number" step="0.01" name="blood_glucose" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">GCS</label><input type="number" name="gcs_score" class="form-control"></div>
                            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save vitals</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection
