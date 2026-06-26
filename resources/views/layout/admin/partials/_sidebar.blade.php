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
        <div class="navbar-nav flex-row d-lg-none">
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                    <span class="avatar avatar-sm">JD</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="#" class="dropdown-item">Profile</a>
                    <a href="#" class="dropdown-item">Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('login') }}" class="dropdown-item">Sign out</a>
                </div>
            </div>
        </div>

        {{-- Nav menu --}}
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">

                {{-- Dashboard --}}
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-layout-dashboard"></i>
                        </span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>

                {{-- Orders --}}
                <li class="nav-item">
                    <a class="nav-link" href="#navbar-orders" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="navbar-orders">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-clipboard-list"></i>
                        </span>
                        <span class="nav-link-title">Orders</span>
                        <span class="nav-link-arrow ms-auto">
                            <i class="ti ti-chevron-down"></i>
                        </span>
                    </a>
                    <div class="navbar-nav collapse" id="navbar-orders">
                        <a class="nav-link" href="#">All Orders</a>
                        <a class="nav-link" href="#">New Order</a>
                        <a class="nav-link" href="#">Pending</a>
                        <a class="nav-link" href="#">Cancelled</a>
                    </div>
                </li>

                {{-- Users --}}
                <li class="nav-item">
                    <a class="nav-link" href="#navbar-users" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="navbar-users">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-users"></i>
                        </span>
                        <span class="nav-link-title">Users</span>
                        <span class="nav-link-arrow ms-auto">
                            <i class="ti ti-chevron-down"></i>
                        </span>
                    </a>
                    <div class="navbar-nav collapse" id="navbar-users">
                        <a class="nav-link" href="#">All Users</a>
                        <a class="nav-link" href="#">Add User</a>
                        <a class="nav-link" href="#">Roles &amp; Permissions</a>
                    </div>
                </li>

                {{-- Products --}}
                <li class="nav-item">
                    <a class="nav-link" href="#navbar-products" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="navbar-products">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-box"></i>
                        </span>
                        <span class="nav-link-title">Products</span>
                        <span class="nav-link-arrow ms-auto">
                            <i class="ti ti-chevron-down"></i>
                        </span>
                    </a>
                    <div class="navbar-nav collapse" id="navbar-products">
                        <a class="nav-link" href="#">All Products</a>
                        <a class="nav-link" href="#">Add Product</a>
                        <a class="nav-link" href="#">Categories</a>
                        <a class="nav-link" href="#">Inventory</a>
                    </div>
                </li>

                {{-- Reports --}}
                <li class="nav-item">
                    <a class="nav-link" href="#navbar-reports" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="navbar-reports">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-chart-bar"></i>
                        </span>
                        <span class="nav-link-title">Reports</span>
                        <span class="nav-link-arrow ms-auto">
                            <i class="ti ti-chevron-down"></i>
                        </span>
                    </a>
                    <div class="navbar-nav collapse" id="navbar-reports">
                        <a class="nav-link" href="#">Sales Report</a>
                        <a class="nav-link" href="#">User Report</a>
                        <a class="nav-link" href="#">Revenue</a>
                    </div>
                </li>

                {{-- Settings --}}
                <li class="nav-item mt-auto">
                    <a class="nav-link" href="#navbar-settings" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="navbar-settings">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-settings"></i>
                        </span>
                        <span class="nav-link-title">Settings</span>
                        <span class="nav-link-arrow ms-auto">
                            <i class="ti ti-chevron-down"></i>
                        </span>
                    </a>
                    <div class="navbar-nav collapse" id="navbar-settings">
                        <a class="nav-link" href="#">General</a>
                        <a class="nav-link" href="#">Security</a>
                        <a class="nav-link" href="#">Notifications</a>
                    </div>
                </li>

            </ul>
        </div>
    </div>
</aside>
