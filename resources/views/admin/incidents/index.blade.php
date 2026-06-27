@extends('layout.admin.app')

@section('title', 'Incidents')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">S6 — Intake</div>
            <h2 class="page-title">Incidents</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- Filters --}}
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.incidents.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <input type="text" name="q" class="form-control" placeholder="Code or patient name" value="{{ $search }}">
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-select">
                                <option value="">All types</option>
                                <option value="one_tap" @selected($type === 'one_tap')>One-tap</option>
                                <option value="detailed" @selected($type === 'detailed')>Detailed</option>
                                <option value="scheduled" @selected($type === 'scheduled')>Scheduled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All statuses</option>
                                @foreach (['pending','dispatched','ongoing','on_scene','transporting','completed','cancelled'] as $s)
                                    <option value="{{ $s }}" @selected($status === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="organization_id" class="form-select">
                                <option value="">All organizations</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}" @selected($orgId == $org->id)>{{ $org->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary flex-fill" type="submit">Filter</button>
                            <a href="{{ route('admin.incidents.index') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div id="table-incidents" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-code">Code</button></th>
                                    <th><button class="table-sort" data-sort="sort-type">Type</button></th>
                                    <th><button class="table-sort" data-sort="sort-status">Status</button></th>
                                    <th><button class="table-sort" data-sort="sort-org">Organization</button></th>
                                    <th><button class="table-sort" data-sort="sort-patient">Patient</button></th>
                                    <th><button class="table-sort" data-sort="sort-date">Submitted</button></th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($incidents as $incident)
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
                                    <tr>
                                        <td class="sort-code">
                                            <a href="{{ route('admin.incidents.show', $incident) }}" class="fw-bold">{{ $incident->request_code }}</a>
                                            @if ($incident->master_incident_id)
                                                <span class="badge bg-azure-lt ms-1">Grouped</span>
                                            @endif
                                        </td>
                                        <td class="sort-type">
                                            <span class="badge bg-secondary-lt">{{ ucwords(str_replace('_', ' ', $incident->request_type)) }}</span>
                                        </td>
                                        <td class="sort-status">
                                            <span class="badge bg-{{ $statusColor }}-lt">{{ ucwords(str_replace('_', ' ', $incident->status)) }}</span>
                                        </td>
                                        <td class="sort-org text-secondary">{{ $incident->organization?->name ?? '—' }}</td>
                                        <td class="sort-patient">{{ $incident->patient_name ?? '—' }}</td>
                                        <td class="sort-date text-secondary">{{ $incident->created_at?->format('M d, Y H:i') }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('admin.incidents.show', $incident) }}">
                                                        <i class="ti ti-eye me-2"></i>View
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-4">No incidents found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($incidents->hasPages())
                    <div class="card-footer d-flex align-items-center">
                        {{ $incidents->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}"></script>
    <script>
        new List('table-incidents', {
            sortClass: 'table-sort',
            listClass: 'table-tbody',
            valueNames: ['sort-code', 'sort-type', 'sort-status', 'sort-org', 'sort-patient', 'sort-date'],
        });
    </script>
@endpush
