@extends('layout.admin.app')

@section('title', 'Organization Approvals')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Platform Executive (LGU)</div>
            <h2 class="page-title">Organization Approvals</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body p-0">
                    <div id="table-org-approvals" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-name">Name</button></th>
                                    <th><button class="table-sort" data-sort="sort-type">Type</button></th>
                                    <th><button class="table-sort" data-sort="sort-plan">Plan</button></th>
                                    <th><button class="table-sort" data-sort="sort-submitted">Submitted</button></th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($pending as $org)
                                    <tr>
                                        <td class="sort-name"><a href="{{ route('admin.org-approvals.show', $org) }}">{{ $org->name }}</a></td>
                                        <td class="sort-type">{{ ucwords(str_replace('_', ' ', $org->org_type)) }}</td>
                                        <td class="sort-plan text-secondary">{{ $org->subscription?->plan?->name ?? '—' }}</td>
                                        <td class="sort-submitted text-secondary">{{ $org->created_at?->diffForHumans() }}</td>
                                        <td class="text-center">
                                            <form id="org-approve-{{ $org->id }}" action="{{ route('admin.org-approvals.approve', $org) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>

                                            <div class="dropdown text-center">
                                                <a href="#" class="btn-action dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('admin.org-approvals.show', $org) }}">
                                                        <i class="ti ti-eye me-2"></i>Review
                                                    </a>
                                                    <a class="dropdown-item text-success" href="#"
                                                       onclick="event.preventDefault(); confirmAction(
                                                           () => document.getElementById('org-approve-{{ $org->id }}').submit(),
                                                           { type:'success', title:'Approve organization?', message:'{{ $org->name }} will be activated.', confirm:'Approve' }
                                                       )">
                                                        <i class="ti ti-check me-2"></i>Approve
                                                    </a>
                                                    <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#org-reject-{{ $org->id }}">
                                                        <i class="ti ti-x me-2"></i>Reject
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="modal" id="org-reject-{{ $org->id }}" tabindex="-1">
                                                <div class="modal-dialog" role="document">
                                                    <form action="{{ route('admin.org-approvals.reject', $org) }}" method="POST" class="modal-content text-start">
                                                        @csrf @method('PATCH')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject {{ $org->name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <label class="form-label">Reason</label>
                                                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject organization</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">No organizations awaiting review.</td></tr>
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
            new List('table-org-approvals', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: ['sort-name', 'sort-type', 'sort-plan', 'sort-submitted'],
            });
        });
    </script>
@endpush
