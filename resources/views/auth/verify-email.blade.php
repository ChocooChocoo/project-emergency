@extends('layout.auth.app')

@section('title', 'Verify Email')

@section('logo')
    <a href="/" class="navbar-brand navbar-brand-autodark">
        <h1 class="h2 fw-bold">{{ config('app.name') }}</h1>
    </a>
@endsection

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-2">Verify your email</h2>
            <p class="text-secondary text-center mb-4">Enter the 6-digit code we sent to your email.</p>

            <form action="{{ route('verify-email.verify') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="code">Verification code</label>
                    <input type="text" inputmode="numeric" maxlength="6" id="code" name="code"
                           class="form-control text-center @error('code') is-invalid @enderror"
                           placeholder="••••••" required autofocus/>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Verify</button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        Didn't get a code?
        <form action="{{ route('verify-email.resend') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link p-0 align-baseline">Resend</button>
        </form>
    </div>
@endsection
