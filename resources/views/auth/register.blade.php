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

            <form action="{{ route('register') }}" method="POST" autocomplete="off" novalidate>
                @csrf

                {{-- Name --}}
                <div class="row">
                    <div class="col mb-3">
                        <label class="form-label" for="first_name">First name</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                               class="form-control @error('first_name') is-invalid @enderror"
                               placeholder="Jane" required autofocus/>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col mb-3">
                        <label class="form-label" for="last_name">Last name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                               class="form-control @error('last_name') is-invalid @enderror"
                               placeholder="Doe" required/>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="you@example.com"
                        required
                    />
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Phone (optional) --}}
                <div class="mb-3">
                    <label class="form-label" for="phone">Phone <span class="form-label-description">optional</span></label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="form-control @error('phone') is-invalid @enderror" placeholder="09xxxxxxxxx"/>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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

                {{-- Terms --}}
                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" name="terms" value="1" class="form-check-input @error('terms') is-invalid @enderror" {{ old('terms') ? 'checked' : '' }}/>
                        <span class="form-check-label">I agree to the terms and conditions</span>
                    </label>
                    @error('terms')<div class="text-danger small">{{ $message }}</div>@enderror
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
