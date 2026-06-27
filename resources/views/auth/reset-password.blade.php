@extends('layout.auth.app')

@section('title', 'Reset Password')

@section('logo')
    <a href="/" class="navbar-brand navbar-brand-autodark">
        <h1 class="h2 fw-bold">{{ config('app.name') }}</h1>
    </a>
@endsection

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Set a new password</h2>

            <form action="{{ route('password.update') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                           class="form-control @error('email') is-invalid @enderror" required/>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="code">Reset code</label>
                    <input type="text" inputmode="numeric" maxlength="6" id="code" name="code"
                           class="form-control @error('code') is-invalid @enderror" placeholder="6-digit code" required/>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror" placeholder="At least 8 characters" required/>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control" placeholder="Repeat your password" required/>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Reset password</button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        <a href="{{ route('login') }}">Back to sign in</a>
    </div>
@endsection
