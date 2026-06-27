@extends('layout.admin.app')

@section('title', 'Notifications')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">S11</div>
                    <h2 class="page-title">Notifications</h2>
                </div>
                <div class="col-auto">
                    <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="ti ti-checks me-2"></i>Mark all read
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="list-group list-group-flush">
                    @forelse ($notifications as $n)
                        <div class="list-group-item {{ $n->is_read ? '' : 'bg-primary-lt' }}">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="status-dot {{ $n->is_read ? '' : 'status-dot-animated bg-primary' }} d-block"></span>
                                </div>
                                <div class="col">
                                    <div class="fw-bold">{{ $n->title }}</div>
                                    <div class="text-secondary">{{ $n->message }}</div>
                                    <div class="small text-secondary mt-1">{{ $n->created_at?->diffForHumans() }}</div>
                                </div>
                                @unless ($n->is_read)
                                    <div class="col-auto">
                                        <form method="POST" action="{{ route('admin.notifications.read', $n) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                                        </form>
                                    </div>
                                @endunless
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-secondary py-5">
                            <i class="ti ti-bell-off fs-1 d-block mb-2"></i>
                            No notifications.
                        </div>
                    @endforelse
                </div>
                @if ($notifications->hasPages())
                    <div class="card-footer d-flex align-items-center">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
