@extends('layout.admin.app')

@section('title', 'Organizations')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Onboarding</div>
                    <h2 class="page-title">Organizations</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> New organization
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body p-0">
                    <div id="table-orgs" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-name">Name</button></th>
                                    <th><button class="table-sort" data-sort="sort-type">Type</button></th>
                                    <th><button class="table-sort" data-sort="sort-plan">Plan</button></th>
                                    <th><button class="table-sort" data-sort="sort-status">Status</button></th>
                                    <th>Active</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($organizations as $org)
                                    <tr class="{{ $org->is_archived ? 'opacity-50' : '' }}">
                                        <td class="sort-name">
                                            <a href="{{ route('admin.organizations.show', $org) }}">{{ $org->name }}</a>
                                            @if ($org->is_archived)<span class="badge bg-secondary-lt ms-1">Archived</span>@endif
                                        </td>
                                        <td class="sort-type">{{ ucwords(str_replace('_', ' ', $org->org_type)) }}</td>
                                        <td class="sort-plan text-secondary">{{ $org->subscription?->plan?->name ?? '—' }}</td>
                                        <td class="sort-status">
                                            <span class="badge {{ $org->organization_status === 'active' ? 'bg-green-lt' : ($org->organization_status === 'rejected' ? 'bg-red-lt' : 'bg-yellow-lt') }}">
                                                {{ ucwords(str_replace('_', ' ', $org->organization_status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $org->is_active ? 'bg-green-lt' : 'bg-red-lt' }}">{{ $org->is_active ? 'Yes' : 'No' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @include('admin.organizations._actions', ['org' => $org])
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">No organizations found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    {{ $organizations->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new List('table-orgs', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: ['sort-name', 'sort-type', 'sort-plan', 'sort-status'],
            });
        });
    </script>
@endpush
