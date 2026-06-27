@extends('layout.admin.app')

@section('title', 'Dispatch — ' . $incident->request_code)

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Dispatch</div>
                    <h2 class="page-title">{{ $incident->request_code }}</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.dispatch.index') }}" class="btn btn-link">&larr; Back to queue</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">

                {{-- Incident summary --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Incident</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Status</dt>
                                <dd class="col-7"><span class="badge bg-{{ $incident->status === 'pending' ? 'yellow' : 'blue' }}-lt">{{ ucwords(str_replace('_', ' ', $incident->status)) }}</span></dd>
                                <dt class="col-5">Severity</dt>
                                <dd class="col-7"><span class="badge bg-{{ $incident->severity <= 2 ? 'red' : 'secondary' }}-lt">Sev {{ $incident->severity }}</span></dd>
                                <dt class="col-5">Nature</dt>
                                <dd class="col-7">{{ $incident->incident_type ?? '—' }}</dd>
                                <dt class="col-5">Address</dt>
                                <dd class="col-7">{{ $incident->pickup_address }}</dd>
                                <dt class="col-5">Coordinates</dt>
                                <dd class="col-7">{{ $incident->pickup_lat && $incident->pickup_lng ? "{$incident->pickup_lat}, {$incident->pickup_lng}" : '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    @if ($incident->activeAssignment)
                        <div class="card mt-3">
                            <div class="card-header"><h3 class="card-title">Current assignment</h3></div>
                            <div class="card-body">
                                <p class="mb-1"><strong>{{ $incident->activeAssignment->ambulance?->plate_no }}</strong>
                                    <span class="badge bg-blue-lt ms-1">{{ ucwords(str_replace('_', ' ', $incident->activeAssignment->status)) }}</span></p>
                                <p class="mb-0 text-secondary">Driver: {{ $incident->activeAssignment->driver?->full_name ?? '—' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- DSS ranked units --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recommended units</h3>
                            <span class="card-subtitle ms-2 text-secondary">DSS-ranked by proximity, tier &amp; equipment</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th><th>Unit</th><th>Organization</th><th>Tier</th>
                                            <th>Distance</th><th>ETA</th><th>Score</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ranked as $row)
                                            @php($a = $row['ambulance'])
                                            <tr>
                                                <td>{{ $row['dss_rank'] }}</td>
                                                <td>
                                                    <a href="{{ route('admin.ambulances.show', $a) }}" class="fw-bold">{{ $a->plate_no }}</a>
                                                    @if ($row['dss_rank'] === 1)<span class="badge bg-green-lt ms-1">Top pick</span>@endif
                                                </td>
                                                <td class="text-secondary">{{ $a->organization?->name }}</td>
                                                <td><span class="badge bg-{{ $a->tier === 'als' ? 'purple' : 'secondary' }}-lt">{{ strtoupper($a->tier ?? '—') }}</span></td>
                                                <td>{{ $row['distance_km'] !== null ? number_format($row['distance_km'], 1).' km' : '—' }}</td>
                                                <td>{{ $row['eta_minutes'] !== null ? $row['eta_minutes'].' min' : '—' }}</td>
                                                <td class="text-secondary">{{ $row['score'] }}</td>
                                                <td class="text-center">
                                                    @if ($incident->status === 'pending')
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            onclick="confirmAction(() => document.getElementById('assign-{{ $a->id }}').submit(), { type:'primary', title:'Dispatch {{ $a->plate_no }}?', message:'This locks the incident to {{ $a->organization?->name }}.', confirm:'Dispatch' })">
                                                            <i class="ti ti-send me-1"></i>Assign
                                                        </button>
                                                        <form id="assign-{{ $a->id }}" action="{{ route('admin.dispatch.store', $incident) }}" method="POST" class="d-none">
                                                            @csrf
                                                            <input type="hidden" name="ambulance_id" value="{{ $a->id }}">
                                                            <input type="hidden" name="driver_user_id" value="{{ $a->current_driver_user_id ?? $a->organization?->admin_user_id }}">
                                                            <input type="hidden" name="dss_rank" value="{{ $row['dss_rank'] }}">
                                                        </form>
                                                    @else
                                                        <span class="text-secondary small">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="text-center text-secondary py-4">No available units to recommend.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
