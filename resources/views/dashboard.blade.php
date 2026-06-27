@extends('layout.admin.app')

@section('title', 'Dashboard')

@section('content')

    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Console</div>
                    <h2 class="page-title">Dashboard</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @can('manage-users')
                <div class="row row-deck row-cards">
                    @php($cards = [
                        ['Total users', $stats['users'], 'ti-users', 'bg-blue'],
                        ['Active', $stats['active'], 'ti-user-check', 'bg-green'],
                        ['Awaiting approval', $stats['pending'], 'ti-user-question', 'bg-yellow'],
                        ['Archived', $stats['archived'], 'ti-archive', 'bg-secondary'],
                    ])
                    @foreach ($cards as [$label, $value, $icon, $bg])
                        <div class="col-sm-6 col-lg-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar {{ $bg }} text-white me-3"><i class="ti {{ $icon }}"></i></span>
                                        <div>
                                            <div class="h1 mb-0">{{ $value }}</div>
                                            <div class="text-secondary">{{ $label }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endcan

            {{-- Governance quick-cards: each renders only for viewers with the permission. --}}
            <div class="row row-deck row-cards mt-1">
                @php($gcards = [
                    ['review-approvals', 'admin.approvals.index', 'Pending approvals', $gov['pending_accounts'], 'ti-user-check', 'yellow'],
                    ['review-org-approvals', 'admin.org-approvals.index', 'Pending org approvals', $gov['pending_orgs'], 'ti-building-bank', 'orange'],
                    ['manage-organizations', 'admin.organizations.index', 'Organizations', null, 'ti-building', 'blue'],
                    ['view-incidents', 'admin.incidents.index', 'Flagged incidents', $gov['flagged_incidents'], 'ti-urgent', 'red'],
                    ['manage-safety', 'admin.safety.index', 'Blocked devices', $gov['blocked_devices'], 'ti-shield-lock', 'pink'],
                    ['view-reports', 'admin.reports.index', 'Reports', null, 'ti-report-analytics', 'green'],
                    ['manage-archive', 'admin.archive.index', 'Archive registry', $gov['archived_total'], 'ti-archive', 'secondary'],
                    ['view-audit-logs', 'admin.audit.index', 'Audit logs', null, 'ti-list-details', 'purple'],
                    ['manage-config', 'admin.config.edit', 'City Settings', null, 'ti-settings', 'azure'],
                ])
                @foreach ($gcards as [$perm, $route, $label, $count, $icon, $color])
                    @can($perm)
                        <div class="col-sm-6 col-lg-3">
                            <a href="{{ route($route) }}" class="card card-link">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar bg-{{ $color }}-lt me-3"><i class="ti {{ $icon }}"></i></span>
                                        <div>
                                            <div class="font-weight-medium">{{ $label }}</div>
                                            @isset($count)
                                                <span class="badge bg-{{ $color }}-lt">{{ $count }}</span>
                                            @endisset
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endcan
                @endforeach
            </div>
        </div>
    </div>

@endsection
