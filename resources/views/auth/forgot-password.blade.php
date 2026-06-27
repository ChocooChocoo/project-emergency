@extends('layout.auth.app')

@section('title', 'Forgot Password')

@section('logo')
    <a href="/" class="navbar-brand navbar-brand-autodark">
        <h1 class="h2 fw-bold">{{ config('app.name') }}</h1>
    </a>
@endsection

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-2">Forgot password</h2>
            <p class="text-secondary text-center mb-4">Enter your email and we'll send a reset code.</p>

            <form action="{{ route('password.email') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="you@example.com" required autofocus/>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Send reset code</button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        <a href="{{ route('login') }}">Back to sign in</a>
    </div>
@endsection
