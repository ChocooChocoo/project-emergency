@extends('layout.admin.app')

@section('title', 'Review Organization')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Platform Executive / Org Approvals</div>
                    <h2 class="page-title">Review: {{ $organization->name }}</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.org-approvals.index') }}" class="btn btn-link">&larr; Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Organization details</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-4">Type</dt><dd class="col-8">{{ ucwords(str_replace('_', ' ', $organization->org_type)) }}</dd>
                                <dt class="col-4">Email</dt><dd class="col-8">{{ $organization->email ?: '—' }}</dd>
                                <dt class="col-4">Phone</dt><dd class="col-8">{{ $organization->phone ?: '—' }}</dd>
                                <dt class="col-4">Address</dt><dd class="col-8">{{ $organization->address ?: '—' }}</dd>
                                <dt class="col-4">Admin contact</dt><dd class="col-8">{{ $organization->admin?->full_name ?? '—' }}</dd>
                                <dt class="col-4">Plan</dt><dd class="col-8">{{ $organization->subscription?->plan?->name ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Submitted documents</h3></div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Type</th><th>Number</th><th>Status</th><th class="text-center">Action</th></tr></thead>
                                <tbody>
                                    @forelse ($organization->documents as $doc)
                                        <tr>
                                            <td>{{ ucwords(str_replace('_', ' ', $doc->document_type)) }}</td>
                                            <td class="text-secondary">{{ $doc->document_number ?: '—' }}</td>
                                            <td><span class="badge {{ $doc->validation_status === 'validated' ? 'bg-green-lt' : ($doc->validation_status === 'rejected' ? 'bg-red-lt' : 'bg-yellow-lt') }}">{{ ucfirst($doc->validation_status) }}</span></td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.org-approvals.documents.status', [$organization, $doc]) }}" method="POST" class="d-inline-flex gap-1">
                                                    @csrf @method('PATCH')
                                                    <button name="validation_status" value="validated" class="btn btn-sm btn-success">Validate</button>
                                                    <button name="validation_status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-secondary py-3">No documents uploaded.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Decision</h3></div>
                        <div class="card-body">
                            <form action="{{ route('admin.org-approvals.approve', $organization) }}" method="POST" class="mb-2">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="ti ti-check me-1"></i> Approve &amp; activate
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#org-reject-detail">
                                <i class="ti ti-x me-1"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal" id="org-reject-detail" tabindex="-1">
                <div class="modal-dialog" role="document">
                    <form action="{{ route('admin.org-approvals.reject', $organization) }}" method="POST" class="modal-content">
                        @csrf @method('PATCH')
                        <div class="modal-header">
                            <h5 class="modal-title">Reject {{ $organization->name }}</h5>
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
        </div>
    </div>
@endsection
