@extends('layout.auth.app')

@section('title', 'Create Account')

@section('logo')
    <a href="/" class="navbar-brand navbar-brand-autodark">
        <h1 class="h2 fw-bold">{{ config('app.name') }}</h1>
    </a>
@endsection

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Create your account</h2>

            <form action="#" method="POST" autocomplete="off" novalidate>

                {{-- Name --}}
                <div class="mb-3">
                    <label class="form-label" for="name">Full name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        placeholder="Jane Doe"
                        required
                        autofocus
                    />
                </div>

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
                    />
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group input-group-flat">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="At least 8 characters"
                            required
                        />
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" data-bs-toggle="tooltip" title="Show password"
                               onclick="togglePassword('password', event)">
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

                {{-- Confirm password --}}
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <div class="input-group input-group-flat">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Repeat your password"
                            required
                        />
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" data-bs-toggle="tooltip" title="Show password"
                               onclick="togglePassword('password_confirmation', event)">
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

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        Create account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword(id, e) {
            e.preventDefault();
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
@endpush
