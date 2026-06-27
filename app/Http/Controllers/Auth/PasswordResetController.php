<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\PasswordResetOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $request->input('email'))->first();

        // Always issue if the user exists, but respond the same way either way
        // (don't leak which emails are registered).
        $devOtp = null;
        if ($user) {
            $code = PasswordResetOtp::issue($user);
            $devOtp = config('app.debug') ? $code : null;
        }

        return redirect()->route('password.reset', ['email' => $request->input('email')])
            ->with('status', 'If that email is registered, a reset code has been sent.')
            ->with('dev_otp', $devOtp);
    }

    public function showResetForm(Request $request): View
    {
        return view('auth.reset-password', ['email' => $request->query('email')]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! PasswordResetOtp::consume($user, $data['code'])) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $user->update(['password' => $data['password']]);

        return redirect()->route('login')->with('status', 'Password updated. You can sign in now.');
    }
}
