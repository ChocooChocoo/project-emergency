@extends('layout.auth.app')

@section('title', 'Sign In')

@section('logo')
    <a href="/" class="navbar-brand navbar-brand-autodark">
        <h1 class="h2 fw-bold">{{ config('app.name') }}</h1>
    </a>
@endsection

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Sign in to your account</h2>

            <form action="#" method="POST" autocomplete="off" novalidate>

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="you@example.com"
                        required
                        autofocus
                    />
                </div>

                {{-- Password --}}
                <div class="mb-2">
                    <label class="form-label" for="password">
                        Password
                        <span class="form-label-description">
                            <a href="{{ route('password.request') }}">Forgot password?</a>
                        </span>
                    </label>
                    <div class="input-group input-group-flat">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Your password"
                            required
                        />
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" data-bs-toggle="tooltip" title="Show password"
                               onclick="togglePassword(event)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                     viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                                </svg>
                            </a>
                        </span>
                    </div>
                </div>

                {{-- Remember me --}}
                <div class="mb-2">
                    <label class="form-check cursor-pointer">
                        <input type="checkbox" name="remember" class="form-check-input"/>
                        <span class="form-check-label">Remember me on this device</span>
                    </label>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        Don't have an account? <a href="{{ route('register') }}">Sign up</a>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword(e) {
            e.preventDefault();
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
@endpush
