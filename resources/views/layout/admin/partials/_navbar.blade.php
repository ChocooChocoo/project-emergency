<header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
    <div class="container-xl">

        <div class="navbar-nav flex-row order-md-last ms-auto">

            {{-- Dark / Light toggle --}}
            <div class="nav-item d-none d-md-flex me-1">
                <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="ti ti-moon fs-3"></i>
                </a>
                <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="ti ti-sun fs-3"></i>
                </a>
            </div>

            {{-- Notifications --}}
            <div class="nav-item dropdown d-none d-md-flex me-2">
                <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1"
                    aria-label="Show notifications" data-bs-auto-close="outside">
                    <i class="ti ti-bell fs-3"></i>
                    <span class="badge bg-red"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                    <div class="card">
                        <div class="card-header d-flex">
                            <h3 class="card-title">Notifications</h3>
                            <div class="btn-close ms-auto" data-bs-dismiss="dropdown"></div>
                        </div>
                        <div class="list-group list-group-flush list-group-hoverable">
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto"><span class="status-dot status-dot-animated bg-red d-block"></span></div>
                                    <div class="col text-truncate">
                                        <a href="#" class="text-body d-block">New order received</a>
                                        <div class="d-block text-secondary text-truncate mt-n1">2 minutes ago</div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto"><span class="status-dot bg-yellow d-block"></span></div>
                                    <div class="col text-truncate">
                                        <a href="#" class="text-body d-block">Server load at 85%</a>
                                        <div class="d-block text-secondary text-truncate mt-n1">15 minutes ago</div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto"><span class="status-dot bg-green d-block"></span></div>
                                    <div class="col text-truncate">
                                        <a href="#" class="text-body d-block">Deployment completed</a>
                                        <div class="d-block text-secondary text-truncate mt-n1">1 hour ago</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- User avatar --}}
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                    <span class="avatar avatar-sm rounded bg-blue text-white">JD</span>
                    <div class="d-none d-xl-block ps-2">
                        <div>Jane Doe</div>
                        <div class="mt-1 small text-secondary">Administrator</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="#" class="dropdown-item">Profile</a>
                    <a href="#" class="dropdown-item">Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('login') }}" class="dropdown-item">Sign out</a>
                </div>
            </div>

        </div>
    </div>
</header>
