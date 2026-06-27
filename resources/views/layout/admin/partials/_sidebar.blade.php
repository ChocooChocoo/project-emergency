<aside class="navbar navbar-vertical navbar-expand-lg">
    <div class="container-fluid">

        {{-- Mobile toggle --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Brand --}}
        <a href="{{ route('dashboard') }}" class="navbar-brand navbar-brand-autodark">
            <span class="fw-bold fs-3">{{ config('app.name') }}</span>
        </a>

        {{-- Mobile user avatar --}}
        @auth
            @php($u = auth()->user())
            <div class="navbar-nav flex-row d-lg-none">
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                        <span class="avatar avatar-sm">{{ strtoupper(substr($u->first_name, 0, 1).substr($u->last_name, 0, 1)) }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth

        {{-- Nav menu --}}
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">

                {{-- Dashboard --}}
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-layout-dashboard"></i></span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>

                {{-- Users --}}
                @can('manage-users')
                    <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.users.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-users"></i></span>
                            <span class="nav-link-title">Users</span>
                        </a>
                    </li>
                @endcan

                {{-- Archive registry --}}
                @can('manage-archive')
                    <li class="nav-item {{ request()->routeIs('admin.archive.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.archive.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-archive"></i></span>
                            <span class="nav-link-title">Archive</span>
                        </a>
                    </li>
                @endcan

                {{-- Audit & system logs --}}
                @can('view-audit-logs')
                    <li class="nav-item {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.audit.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-list-details"></i></span>
                            <span class="nav-link-title">Logs</span>
                        </a>
                    </li>
                @endcan

                {{-- Approvals --}}
                @can('review-approvals')
                    <li class="nav-item {{ request()->routeIs('admin.approvals.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.approvals.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-user-check"></i></span>
                            <span class="nav-link-title">Approvals</span>
                        </a>
                    </li>
                @endcan

                {{-- S4 — Organizations --}}
                @can('manage-organizations')
                    <li class="nav-item {{ request()->routeIs('admin.organizations.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.organizations.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-building"></i></span>
                            <span class="nav-link-title">Organizations</span>
                        </a>
                    </li>
                @endcan

                @can('review-org-approvals')
                    <li class="nav-item {{ request()->routeIs('admin.org-approvals.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.org-approvals.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-building-bank"></i></span>
                            <span class="nav-link-title">Org Approvals</span>
                        </a>
                    </li>
                @endcan

                {{-- S5 — Fleet --}}
                @can('manage-fleet')
                    <li class="nav-item {{ request()->routeIs('admin.ambulances.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.ambulances.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-ambulance"></i></span>
                            <span class="nav-link-title">Fleet</span>
                        </a>
                    </li>
                @endcan

                {{-- S6 — Incidents --}}
                @can('view-incidents')
                    <li class="nav-item {{ request()->routeIs('admin.incidents.*') && ! request()->routeIs('admin.care.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.incidents.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-urgent"></i></span>
                            <span class="nav-link-title">Incidents</span>
                        </a>
                    </li>
                @endcan

                {{-- S7 — Dispatch --}}
                @can('dispatch-incidents')
                    <li class="nav-item {{ request()->routeIs('admin.dispatch.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.dispatch.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-radar"></i></span>
                            <span class="nav-link-title">Dispatch</span>
                        </a>
                    </li>
                @endcan

                {{-- S8 — Driver --}}
                @can('drive-unit')
                    <li class="nav-item {{ request()->routeIs('admin.driver.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.driver.duty') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-steering-wheel"></i></span>
                            <span class="nav-link-title">Driver</span>
                        </a>
                    </li>
                @endcan

                {{-- S9 — Hospitals --}}
                @can('manage-hospitals')
                    <li class="nav-item {{ request()->routeIs('admin.hospitals.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.hospitals.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-building-hospital"></i></span>
                            <span class="nav-link-title">Hospitals</span>
                        </a>
                    </li>
                @endcan

                {{-- S10 — Safety --}}
                @can('manage-safety')
                    <li class="nav-item {{ request()->routeIs('admin.safety.*') || request()->routeIs('admin.ads.*') ? 'active' : '' }}">
                        <a class="nav-link" href="#navbar-safety" data-bs-toggle="collapse" role="button">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-shield-lock"></i></span>
                            <span class="nav-link-title">Safety</span>
                            <span class="nav-link-arrow ms-auto"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="navbar-nav collapse {{ request()->routeIs('admin.safety.*') || request()->routeIs('admin.ads.*') ? 'show' : '' }}" id="navbar-safety">
                            <a class="nav-link {{ request()->routeIs('admin.safety.*') ? 'active' : '' }}" href="{{ route('admin.safety.index') }}">Anti-abuse</a>
                            <a class="nav-link {{ request()->routeIs('admin.ads.*') ? 'active' : '' }}" href="{{ route('admin.ads.index') }}">Ad placements</a>
                        </div>
                    </li>
                @endcan

                {{-- S11 — Reports --}}
                @can('view-reports')
                    <li class="nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.reports.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-report-analytics"></i></span>
                            <span class="nav-link-title">Reports</span>
                        </a>
                    </li>
                @endcan

                {{-- City Settings --}}
                @can('manage-config')
                    <li class="nav-item {{ request()->routeIs('admin.config.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.config.edit') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-settings"></i></span>
                            <span class="nav-link-title">City Settings</span>
                        </a>
                    </li>
                @endcan

            </ul>
        </div>
    </div>
</aside>
