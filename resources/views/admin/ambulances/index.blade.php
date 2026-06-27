@extends('layout.admin.app')

@section('title', 'Fleet')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Fleet</div>
                    <h2 class="page-title">Ambulances</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.ambulances.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Register ambulance
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body p-0">
                    <div id="table-ambulances" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-plate">Plate</button></th>
                                    <th><button class="table-sort" data-sort="sort-org">Organization</button></th>
                                    <th><button class="table-sort" data-sort="sort-tier">Tier</button></th>
                                    <th><button class="table-sort" data-sort="sort-status">Status</button></th>
                                    <th>Serviceable</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($ambulances as $amb)
                                    <tr class="{{ $amb->is_archived ? 'opacity-50' : '' }}">
                                        <td class="sort-plate">
                                            <a href="{{ route('admin.ambulances.show', $amb) }}">{{ $amb->plate_no }}</a>
                                            @if ($amb->unit_code)<span class="text-secondary ms-1">/ {{ $amb->unit_code }}</span>@endif
                                            @if ($amb->is_archived)<span class="badge bg-secondary-lt ms-1">Archived</span>@endif
                                        </td>
                                        <td class="sort-org text-secondary">{{ $amb->organization?->name ?? '—' }}</td>
                                        <td class="sort-tier">
                                            @if ($amb->tier)<span class="badge bg-azure-lt">{{ strtoupper($amb->tier) }}</span>@else —@endif
                                        </td>
                                        <td class="sort-status"><span class="badge bg-blue-lt">{{ ucwords(str_replace('_', ' ', $amb->status)) }}</span></td>
                                        <td><span class="badge {{ $amb->is_serviceable ? 'bg-green-lt' : 'bg-red-lt' }}">{{ $amb->is_serviceable ? 'Yes' : 'No' }}</span></td>
                                        <td class="text-center">@include('admin.ambulances._actions', ['amb' => $amb])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">No ambulances registered.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    {{ $ambulances->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new List('table-ambulances', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: ['sort-plate', 'sort-org', 'sort-tier', 'sort-status'],
            });
        });
    </script>
@endpush
