<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\PortalRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showForm(): View
    {
        return view('auth.login');
    }

    public function authenticate(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        // Gate on verification / activation status.
        if ($user->email_verified_at === null) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'Please verify your email first.']);
        }

        if (! $user->is_active || $user->is_archived || $user->account_status !== 'active') {
            Auth::logout();
            $msg = $user->account_status === 'awaiting_approval'
                ? 'Your account is awaiting approval.'
                : 'Your account is not active.';

            throw ValidationException::withMessages(['email' => $msg]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route(PortalRouter::homeRouteFor($user)));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
