@extends('layout.admin.app')

@section('title', 'Organization')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Onboarding / Organizations</div>
                    <h2 class="page-title">{{ $organization->name }}</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.organizations.index') }}" class="btn btn-link">&larr; Back</a>
                    <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Profile</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-4">Type</dt><dd class="col-8">{{ ucwords(str_replace('_', ' ', $organization->org_type)) }}</dd>
                                <dt class="col-4">Status</dt>
                                <dd class="col-8">
                                    <span class="badge {{ $organization->organization_status === 'active' ? 'bg-green-lt' : ($organization->organization_status === 'rejected' ? 'bg-red-lt' : 'bg-yellow-lt') }}">
                                        {{ ucwords(str_replace('_', ' ', $organization->organization_status)) }}
                                    </span>
                                </dd>
                                <dt class="col-4">Code</dt><dd class="col-8">{{ $organization->code ?: '—' }}</dd>
                                <dt class="col-4">Email</dt><dd class="col-8">{{ $organization->email ?: '—' }}</dd>
                                <dt class="col-4">Phone</dt><dd class="col-8">{{ $organization->phone ?: '—' }}</dd>
                                <dt class="col-4">Dispatch hotline</dt><dd class="col-8">{{ $organization->dispatch_hotline_ops ?: '—' }}</dd>
                                <dt class="col-4">Address</dt><dd class="col-8">{{ $organization->address ?: '—' }}</dd>
                                <dt class="col-4">Service city</dt><dd class="col-8">{{ $organization->service_city ?: '—' }}</dd>
                                <dt class="col-4">24/7</dt><dd class="col-8">{{ $organization->is_24_7 ? 'Yes' : 'No' }}</dd>
                                <dt class="col-4">Admin contact</dt><dd class="col-8">{{ $organization->admin?->full_name ?? '—' }}</dd>
                                <dt class="col-4">Approved by</dt><dd class="col-8">{{ $organization->approvedBy?->full_name ?? '—' }} {{ $organization->approved_at ? '('.$organization->approved_at->format('Y-m-d').')' : '' }}</dd>
                                @if ($organization->rejected_reason)
                                    <dt class="col-4">Rejected reason</dt><dd class="col-8 text-danger">{{ $organization->rejected_reason }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Documents</h3></div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Type</th><th>Number</th><th>Status</th><th>Submitted</th></tr></thead>
                                <tbody>
                                    @forelse ($organization->documents as $doc)
                                        <tr>
                                            <td>{{ ucwords(str_replace('_', ' ', $doc->document_type)) }}</td>
                                            <td class="text-secondary">{{ $doc->document_number ?: '—' }}</td>
                                            <td><span class="badge {{ $doc->validation_status === 'validated' ? 'bg-green-lt' : ($doc->validation_status === 'rejected' ? 'bg-red-lt' : 'bg-yellow-lt') }}">{{ ucfirst($doc->validation_status) }}</span></td>
                                            <td class="text-secondary">{{ $doc->submitted_at?->format('Y-m-d') }}</td>
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
                        <div class="card-header"><h3 class="card-title">Subscription</h3></div>
                        <div class="card-body">
                            @php($plan = $organization->subscription?->plan)
                            <dl class="row">
                                <dt class="col-6">Plan</dt><dd class="col-6">{{ $plan?->name ?? '—' }}</dd>
                                <dt class="col-6">State</dt><dd class="col-6">{{ ucfirst($organization->subscription?->status ?? '—') }}</dd>
                                <dt class="col-6">Max ambulances</dt><dd class="col-6">{{ $plan?->is_unlimited ? '∞' : ($plan?->max_ambulances ?? '—') }}</dd>
                                <dt class="col-6">Max members</dt><dd class="col-6">{{ $plan?->is_unlimited ? '∞' : ($plan?->max_members ?? '—') }}</dd>
                            </dl>
                            <div class="text-secondary small">
                                Ambulances registered: {{ $organization->ambulances->count() }}
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Coverage</h3></div>
                        <div class="card-body">
                            @forelse ($organization->coverageAreas as $area)
                                <span class="badge bg-blue-lt me-1 mb-1">{{ $area->barangay_name ?? $area->area_name ?? $area->coverage_name }}</span>
                            @empty
                                <p class="text-secondary mb-0">{{ $organization->covered_barangays_json ?: 'No coverage areas defined.' }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
