@extends('layout.admin.app')

@section('title', 'Incident — ' . $incident->request_code)

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Incidents</div>
                    <h2 class="page-title">{{ $incident->request_code }}</h2>
                </div>
                <div class="col-auto btn-list">
                    @can('dispatch-incidents')
                        @if ($incident->status === 'pending')
                            <a href="{{ route('admin.dispatch.show', $incident) }}" class="btn btn-primary"><i class="ti ti-send me-1"></i>Dispatch</a>
                        @endif
                    @endcan
                    @can('record-care')
                        <a href="{{ route('admin.care.show', $incident) }}" class="btn btn-outline-primary"><i class="ti ti-heartbeat me-1"></i>Care</a>
                    @endcan
                    <a href="{{ route('admin.incidents.index') }}" class="btn btn-link">&larr; Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">

                {{-- Incident details --}}
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Details</h3></div>
                        <div class="card-body">
                            @php
                                $statusColor = match($incident->status) {
                                    'pending'     => 'yellow',
                                    'dispatched'  => 'blue',
                                    'ongoing','on_scene','transporting' => 'orange',
                                    'completed'   => 'green',
                                    'cancelled'   => 'red',
                                    default       => 'secondary',
                                };
                            @endphp
                            <dl class="row">
                                <dt class="col-5">Status</dt>
                                <dd class="col-7"><span class="badge bg-{{ $statusColor }}-lt">{{ ucwords(str_replace('_', ' ', $incident->status)) }}</span></dd>
                                <dt class="col-5">Type</dt>
                                <dd class="col-7"><span class="badge bg-secondary-lt">{{ ucwords(str_replace('_', ' ', $incident->request_type)) }}</span></dd>
                                <dt class="col-5">Severity</dt>
                                <dd class="col-7">{{ $incident->severity ?? '—' }}</dd>
                                <dt class="col-5">Nature</dt>
                                <dd class="col-7">{{ $incident->incident_type ?? '—' }}</dd>
                                <dt class="col-5">Organization</dt>
                                <dd class="col-7">{{ $incident->organization?->name ?? '—' }}</dd>
                                <dt class="col-5">Submitted</dt>
                                <dd class="col-7">{{ $incident->created_at?->format('M d, Y H:i') }}</dd>
                                @if ($incident->scheduled_for)
                                    <dt class="col-5">Scheduled</dt>
                                    <dd class="col-7">{{ $incident->scheduled_for->format('M d, Y H:i') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    {{-- Requester --}}
                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Requester</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                @if ($incident->user)
                                    <dt class="col-5">User</dt>
                                    <dd class="col-7">{{ $incident->user->full_name }}</dd>
                                    <dt class="col-5">Email</dt>
                                    <dd class="col-7">{{ $incident->user->email }}</dd>
                                @elseif ($incident->guest)
                                    <dt class="col-5">Guest</dt>
                                    <dd class="col-7"><span class="text-secondary font-monospace">{{ $incident->guest->guest_key }}</span></dd>
                                    <dt class="col-5">Requests used</dt>
                                    <dd class="col-7">{{ $incident->guest->requests_used }} / {{ $incident->guest->requests_limit }}</dd>
                                @else
                                    <dd class="col-12 text-secondary">Unknown requester.</dd>
                                @endif
                                <dt class="col-5">Patient name</dt>
                                <dd class="col-7">{{ $incident->patient_name ?? '—' }}</dd>
                                <dt class="col-5">Contact</dt>
                                <dd class="col-7">{{ $incident->contact_number ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Location --}}
                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Location</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Address</dt>
                                <dd class="col-7">{{ $incident->pickup_address ?? '—' }}</dd>
                                <dt class="col-5">Landmark</dt>
                                <dd class="col-7">{{ $incident->pickup_landmark ?? '—' }}</dd>
                                <dt class="col-5">Coordinates</dt>
                                <dd class="col-7">
                                    @if ($incident->pickup_lat && $incident->pickup_lng)
                                        {{ $incident->pickup_lat }}, {{ $incident->pickup_lng }}
                                    @else
                                        —
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Right column --}}
                <div class="col-lg-7">

                    {{-- Master / child grouping (R2) --}}
                    @if ($incident->masterIncident || $incident->childReports->isNotEmpty())
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Incident Grouping</h3></div>
                            <div class="card-body">
                                @if ($incident->masterIncident)
                                    <p class="mb-1 text-secondary small">Grouped under master:</p>
                                    <a href="{{ route('admin.incidents.show', $incident->masterIncident) }}" class="fw-bold">
                                        {{ $incident->masterIncident->request_code }}
                                    </a>
                                @endif
                                @if ($incident->childReports->isNotEmpty())
                                    <p class="mb-1 text-secondary small mt-2">Related reports grouped here:</p>
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($incident->childReports as $child)
                                            <li><a href="{{ route('admin.incidents.show', $child) }}">{{ $child->request_code }}</a>
                                                <span class="text-secondary small ms-1">{{ $child->created_at?->format('M d H:i') }}</span></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Summary / notes --}}
                    @if ($incident->request_summary || $incident->notes)
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">Notes</h3></div>
                            <div class="card-body">
                                @if ($incident->request_summary)
                                    <p class="mb-1"><strong>Summary:</strong> {{ $incident->request_summary }}</p>
                                @endif
                                @if ($incident->notes)
                                    <p class="mb-0"><strong>Notes:</strong> {{ $incident->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Updates timeline --}}
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Updates</h3></div>
                        <div class="card-body">
                            @forelse ($incident->updates as $update)
                                <div class="d-flex mb-3">
                                    <div class="me-3 text-secondary small text-nowrap" style="min-width:110px">
                                        {{ $update->created_at?->format('M d H:i') }}
                                    </div>
                                    <div>
                                        <span class="badge bg-secondary-lt me-1">{{ ucwords(str_replace('_', ' ', $update->update_type)) }}</span>
                                        {{ $update->note ?? '' }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-secondary mb-0">No updates recorded.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
