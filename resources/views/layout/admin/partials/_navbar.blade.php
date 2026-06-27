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

            {{-- Notifications (current user's own; queried inline so every admin page has it) --}}
            @auth
                @php
                    $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                    $recentNotifications = \App\Models\Notification::where('user_id', auth()->id())->orderByDesc('id')->limit(5)->get();
                @endphp
                <div class="nav-item dropdown d-none d-md-flex me-2">
                    <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1"
                        aria-label="Show notifications" data-bs-auto-close="outside">
                        <i class="ti ti-bell fs-3"></i>
                        @if ($unreadCount > 0)
                            <span class="badge bg-red"></span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                        <div class="card">
                            <div class="card-header d-flex">
                                <h3 class="card-title">Notifications @if ($unreadCount > 0)<span class="badge bg-red-lt ms-2">{{ $unreadCount }}</span>@endif</h3>
                                <div class="btn-close ms-auto" data-bs-dismiss="dropdown"></div>
                            </div>
                            <div class="list-group list-group-flush list-group-hoverable">
                                @forelse ($recentNotifications as $n)
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto"><span class="status-dot {{ $n->is_read ? '' : 'status-dot-animated bg-primary' }} d-block"></span></div>
                                            <div class="col text-truncate">
                                                <a href="{{ route('admin.notifications.index') }}" class="text-body d-block">{{ $n->title }}</a>
                                                <div class="d-block text-secondary text-truncate mt-n1">{{ $n->created_at?->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item text-secondary text-center py-3">No notifications.</div>
                                @endforelse
                            </div>
                            <div class="card-footer text-center p-2">
                                <a href="{{ route('admin.notifications.index') }}" class="text-secondary">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endauth

            {{-- User avatar --}}
            @auth
                @php($u = auth()->user())
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                        <span class="avatar avatar-sm rounded bg-blue text-white">
                            {{ strtoupper(substr($u->first_name, 0, 1).substr($u->last_name, 0, 1)) }}
                        </span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ $u->full_name }}</div>
                            <div class="mt-1 small text-secondary">{{ ucwords(str_replace('_', ' ', $u->account_type)) }}</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">Sign out</button>
                        </form>
                    </div>
                </div>
            @endauth

        </div>
    </div>
</header>
