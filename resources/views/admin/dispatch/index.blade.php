@extends('layout.admin.app')

@section('title', 'Dispatch')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">S7 — DSS + Dispatch</div>
            <h2 class="page-title">Dispatch Console</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- Tabs --}}
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'queue' ? 'active' : '' }}" href="{{ route('admin.dispatch.index', ['tab' => 'queue']) }}">Emergency queue</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'scheduled' ? 'active' : '' }}" href="{{ route('admin.dispatch.index', ['tab' => 'scheduled']) }}">Scheduled</a>
                </li>
            </ul>

            {{-- Pending incidents --}}
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ $tab === 'scheduled' ? 'Scheduled requests' : 'Awaiting dispatch' }}</h3></div>
                <div class="card-body p-0">
                    <div id="table-queue" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-code">Code</button></th>
                                    <th><button class="table-sort" data-sort="sort-sev">Severity</button></th>
                                    <th><button class="table-sort" data-sort="sort-addr">Location</button></th>
                                    @if ($tab === 'scheduled')
                                        <th><button class="table-sort" data-sort="sort-when">Scheduled for</button></th>
                                    @else
                                        <th><button class="table-sort" data-sort="sort-date">Submitted</button></th>
                                    @endif
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($pending as $incident)
                                    <tr>
                                        <td class="sort-code"><a href="{{ route('admin.dispatch.show', $incident) }}" class="fw-bold">{{ $incident->request_code }}</a></td>
                                        <td class="sort-sev"><span class="badge bg-{{ $incident->severity <= 2 ? 'red' : 'yellow' }}-lt">Sev {{ $incident->severity }}</span></td>
                                        <td class="sort-addr text-secondary">{{ \Illuminate\Support\Str::limit($incident->pickup_address, 40) }}</td>
                                        @if ($tab === 'scheduled')
                                            <td class="sort-when text-secondary">{{ $incident->scheduled_for?->format('M d, Y H:i') ?? '—' }}</td>
                                        @else
                                            <td class="sort-date text-secondary">{{ $incident->created_at?->format('M d, Y H:i') }}</td>
                                        @endif
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-action dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('admin.dispatch.show', $incident) }}"><i class="ti ti-send me-2"></i>Dispatch</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">Nothing waiting for dispatch.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($pending->hasPages())
                    <div class="card-footer d-flex align-items-center">{{ $pending->links() }}</div>
                @endif
            </div>

            {{-- Active assignments board --}}
            <div class="card">
                <div class="card-header"><h3 class="card-title">Active assignments</h3></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Incident</th><th>Unit</th><th>Organization</th><th>Driver</th><th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($active as $a)
                                    <tr>
                                        <td><a href="{{ route('admin.dispatch.show', $a->incident_id) }}">{{ $a->incident?->request_code }}</a></td>
                                        <td>{{ $a->ambulance?->plate_no }}</td>
                                        <td class="text-secondary">{{ $a->organization?->name }}</td>
                                        <td class="text-secondary">{{ $a->driver?->full_name ?? '—' }}</td>
                                        <td><span class="badge bg-blue-lt">{{ ucwords(str_replace('_', ' ', $a->status)) }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-action dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('admin.dispatch.show', $a->incident_id) }}"><i class="ti ti-eye me-2"></i>View incident</a>
                                                    <button type="button" class="dropdown-item text-danger"
                                                        onclick="confirmAction(() => document.getElementById('reassign-{{ $a->id }}').submit(), { type:'warning', title:'Release assignment?', message:'The unit is freed and the incident reopens for dispatch.', confirm:'Release' })">
                                                        <i class="ti ti-refresh me-2"></i>Reassign
                                                    </button>
                                                    <form id="reassign-{{ $a->id }}" action="{{ route('admin.dispatch.reassign', $a) }}" method="POST" class="d-none">
                                                        @csrf @method('PATCH')
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">No active assignments.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}"></script>
    <script>
        new List('table-queue', { sortClass:'table-sort', listClass:'table-tbody',
            valueNames: ['sort-code','sort-sev','sort-addr','sort-date','sort-when'] });
    </script>
@endpush
