@extends('layout.citizen.app')

@section('title', 'My History')

@section('content')
    <div class="page-header mb-4">
        <h2 class="page-title">My Request History</h2>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="table-history" class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><button class="table-sort" data-sort="sort-code">Code</button></th>
                            <th><button class="table-sort" data-sort="sort-type">Type</button></th>
                            <th><button class="table-sort" data-sort="sort-status">Status</button></th>
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
                                    'completed','resolved_on_scene' => 'green',
                                    'cancelled'   => 'red',
                                    default       => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td class="sort-code">
                                    <a href="{{ route('request.track', $incident->request_code) }}" class="fw-bold">{{ $incident->request_code }}</a>
                                </td>
                                <td class="sort-type">
                                    <span class="badge bg-secondary-lt">{{ ucwords(str_replace('_', ' ', $incident->request_type)) }}</span>
                                </td>
                                <td class="sort-status">
                                    <span class="badge bg-{{ $statusColor }}-lt">{{ ucwords(str_replace('_', ' ', $incident->status)) }}</span>
                                </td>
                                <td class="sort-date text-secondary">{{ $incident->created_at?->format('M d, Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('request.track', $incident->request_code) }}">
                                                <i class="ti ti-map-pin me-2"></i>Track
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">You have no requests yet.</td></tr>
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
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}"></script>
    <script>
        new List('table-history', {
            sortClass: 'table-sort',
            listClass: 'table-tbody',
            valueNames: ['sort-code', 'sort-type', 'sort-status', 'sort-date'],
        });
    </script>
@endpush
