@extends('layout.admin.app')

@section('title', 'Approvals')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Super Admin</div>
            <h2 class="page-title">Account Review &amp; Approvals</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body p-0">
                    <div id="table-approvals" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-name">Name</button></th>
                                    <th><button class="table-sort" data-sort="sort-email">Email</button></th>
                                    <th><button class="table-sort" data-sort="sort-type">Type</button></th>
                                    <th><button class="table-sort" data-sort="sort-requested">Requested</button></th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($pending as $user)
                                    <tr>
                                        <td class="sort-name">{{ $user->full_name }}</td>
                                        <td class="sort-email text-secondary">{{ $user->email }}</td>
                                        <td class="sort-type">{{ ucwords(str_replace('_', ' ', $user->account_type)) }}</td>
                                        <td class="sort-requested text-secondary">{{ $user->created_at?->diffForHumans() }}</td>
                                        <td class="text-center">
                                            {{-- Hidden approve form --}}
                                            <form id="approve-{{ $user->id }}" action="{{ route('admin.approvals.approve', $user) }}" method="POST" class="d-none">
                                                @csrf @method('PATCH')
                                            </form>

                                            <div class="dropdown text-center">
                                                <a href="#" class="btn-action dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item text-success" href="#"
                                                       onclick="event.preventDefault(); confirmAction(
                                                           () => document.getElementById('approve-{{ $user->id }}').submit(),
                                                           { type:'success', title:'Approve account?', message:'{{ $user->full_name }} will be activated.', confirm:'Approve' }
                                                       )">
                                                        <i class="ti ti-user-check me-2"></i>Approve
                                                    </a>
                                                    <a class="dropdown-item text-danger" href="#"
                                                       data-bs-toggle="modal" data-bs-target="#reject-{{ $user->id }}">
                                                        <i class="ti ti-user-x me-2"></i>Reject
                                                    </a>
                                                </div>
                                            </div>

                                            {{-- Reject modal: needs a reason textarea --}}
                                            <div class="modal" id="reject-{{ $user->id }}" tabindex="-1">
                                                <div class="modal-dialog" role="document">
                                                    <form action="{{ route('admin.approvals.reject', $user) }}" method="POST" class="modal-content text-start">
                                                        @csrf @method('PATCH')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject {{ $user->full_name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <label class="form-label">Reason</label>
                                                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject account</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">No accounts awaiting approval.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    {{ $pending->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new List('table-approvals', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: ['sort-name', 'sort-email', 'sort-type', 'sort-requested'],
            });
        });
    </script>
@endpush
