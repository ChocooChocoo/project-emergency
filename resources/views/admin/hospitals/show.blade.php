@extends('layout.admin.app')

@section('title', 'Hospital — ' . $hospital->name)

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Hospital</div>
                    <h2 class="page-title">{{ $hospital->name }}</h2>
                </div>
                <div class="col-auto"><a href="{{ route('admin.hospitals.index') }}" class="btn btn-link">&larr; Back</a></div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Profile</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Type</dt><dd class="col-7">{{ ucfirst($hospital->facility_type) }}</dd>
                                <dt class="col-5">City</dt><dd class="col-7">{{ $hospital->city }}, {{ $hospital->province }}</dd>
                                <dt class="col-5">Phone</dt><dd class="col-7">{{ $hospital->phone ?? '—' }}</dd>
                                <dt class="col-5">Capacity</dt><dd class="col-7"><span class="badge bg-{{ $hospital->capacity_status === 'full' ? 'red' : 'green' }}-lt">{{ ucfirst($hospital->capacity_status) }}</span></dd>
                                <dt class="col-5">Beds</dt><dd class="col-7">{{ $hospital->available_beds ?? '—' }}</dd>
                                <dt class="col-5">ER</dt><dd class="col-7"><span class="badge bg-{{ $hospital->is_er_open ? 'green' : 'red' }}-lt">{{ $hospital->is_er_open ? 'Open' : 'Closed' }}</span></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Endorsements / handoff queue --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Incoming endorsements</h3></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th>Incident</th><th>Status</th><th>Handoff</th><th class="text-center">Actions</th></tr></thead>
                                    <tbody>
                                        @forelse ($hospital->endorsements as $e)
                                            <tr>
                                                <td><a href="{{ route('admin.incidents.show', $e->incident_id) }}">{{ $e->incident?->request_code }}</a></td>
                                                <td><span class="badge bg-{{ $e->status === 'accepted' ? 'green' : ($e->status === 'declined' ? 'red' : 'yellow') }}-lt">{{ ucfirst($e->status) }}</span></td>
                                                <td><span class="badge bg-{{ $e->handoff_status === 'completed' ? 'green' : 'secondary' }}-lt">{{ ucfirst($e->handoff_status) }}</span></td>
                                                <td class="text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-action dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            @if ($e->status === 'pending')
                                                                <button class="dropdown-item" onclick="document.getElementById('accept-{{ $e->id }}').submit()"><i class="ti ti-check me-2 text-success"></i>Accept</button>
                                                                <button class="dropdown-item" onclick="document.getElementById('decline-{{ $e->id }}').submit()"><i class="ti ti-x me-2 text-danger"></i>Decline</button>
                                                                <form id="accept-{{ $e->id }}" action="{{ route('admin.hospitals.respond', $e) }}" method="POST" class="d-none">@csrf @method('PATCH')<input type="hidden" name="decision" value="accepted"></form>
                                                                <form id="decline-{{ $e->id }}" action="{{ route('admin.hospitals.respond', $e) }}" method="POST" class="d-none">@csrf @method('PATCH')<input type="hidden" name="decision" value="declined"></form>
                                                            @elseif ($e->status === 'accepted' && $e->handoff_status !== 'completed')
                                                                <button class="dropdown-item" onclick="confirmAction(() => document.getElementById('handoff-{{ $e->id }}').submit(), { type:'success', title:'Confirm handoff?', message:'Closes the incident.', confirm:'Confirm' })"><i class="ti ti-clipboard-check me-2 text-success"></i>Confirm handoff</button>
                                                                <form id="handoff-{{ $e->id }}" action="{{ route('admin.hospitals.handoff', $e) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
                                                            @else
                                                                <span class="dropdown-item text-secondary">No actions</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-secondary py-4">No endorsements yet.</td></tr>
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
