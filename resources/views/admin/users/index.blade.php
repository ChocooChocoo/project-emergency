@extends('layout.admin.app')

@section('title', 'Users')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Super Admin</div>
            <h2 class="page-title">User Management</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body p-0">
                    <div id="table-users" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-name">Name</button></th>
                                    <th><button class="table-sort" data-sort="sort-email">Email</button></th>
                                    <th><button class="table-sort" data-sort="sort-type">Type</button></th>
                                    <th><button class="table-sort" data-sort="sort-status">Status</button></th>
                                    <th><button class="table-sort" data-sort="sort-active">Active</button></th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($users as $user)
                                    <tr class="{{ $user->is_archived ? 'opacity-50' : '' }}">
                                        <td class="sort-name">
                                            <a href="{{ route('admin.users.show', $user) }}">{{ $user->full_name }}</a>
                                            @if ($user->is_archived)<span class="badge bg-secondary-lt ms-1">Archived</span>@endif
                                        </td>
                                        <td class="sort-email text-secondary">{{ $user->email }}</td>
                                        <td class="sort-type">{{ ucwords(str_replace('_', ' ', $user->account_type)) }}</td>
                                        <td class="sort-status">{{ ucwords(str_replace('_', ' ', $user->account_status)) }}</td>
                                        <td class="sort-active">
                                            <span class="badge {{ $user->is_active ? 'bg-green-lt' : 'bg-red-lt' }}">
                                                {{ $user->is_active ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @include('admin.users._actions', ['user' => $user])
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">No users found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new List('table-users', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: ['sort-name', 'sort-email', 'sort-type', 'sort-status', 'sort-active'],
            });
        });
    </script>
@endpush
