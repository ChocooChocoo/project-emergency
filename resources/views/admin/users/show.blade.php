@extends('layout.admin.app')

@section('title', 'User')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Super Admin / Users</div>
                    <h2 class="page-title">{{ $user->full_name }}</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-link">&larr; Back to users</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-3">Email</dt><dd class="col-9">{{ $user->email }}</dd>
                        <dt class="col-3">Phone</dt><dd class="col-9">{{ $user->phone ?: '—' }}</dd>
                        <dt class="col-3">Account type</dt><dd class="col-9">{{ ucwords(str_replace('_',' ',$user->account_type)) }}</dd>
                        <dt class="col-3">Status</dt><dd class="col-9">{{ ucwords(str_replace('_',' ',$user->account_status)) }}</dd>
                        <dt class="col-3">Active</dt><dd class="col-9">{{ $user->is_active ? 'Yes' : 'No' }}</dd>
                        <dt class="col-3">Approved</dt><dd class="col-9">{{ $user->is_approved ? 'Yes' : 'No' }}</dd>
                        <dt class="col-3">Email verified</dt><dd class="col-9">{{ $user->email_verified_at?->format('Y-m-d H:i') ?: 'No' }}</dd>
                        <dt class="col-3">Last login</dt><dd class="col-9">{{ $user->last_login_at?->format('Y-m-d H:i') ?: '—' }}</dd>
                        <dt class="col-3">Registered</dt><dd class="col-9">{{ $user->created_at?->format('Y-m-d H:i') }}</dd>
                        @if ($user->is_archived)
                            <dt class="col-3">Archived</dt><dd class="col-9">{{ $user->archived_at?->format('Y-m-d H:i') }} — {{ $user->archive_reason ?: 'no reason' }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer">
                    @include('admin.users._actions', ['user' => $user])
                </div>
            </div>
        </div>
    </div>
@endsection
