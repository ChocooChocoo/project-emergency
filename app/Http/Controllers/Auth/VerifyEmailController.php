<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtp;
use App\Support\PortalRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser()) {
            return redirect()->route('register');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user = $this->pendingUser();
        if (! $user) {
            return redirect()->route('register');
        }

        if (! EmailOtp::verify($user, $request->string('code'))) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        // Citizens go active immediately; personnel/org/hospital await approval.
        $user->update([
            'email_verified_at' => now(),
            'account_status' => $user->account_type === 'citizen' ? 'active' : 'awaiting_approval',
        ]);

        $request->session()->forget('pending_user_id');

        if ($user->account_status === 'active') {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route(PortalRouter::homeRouteFor($user))->with('status', 'Email verified. Welcome!');
        }

        return redirect()->route('login')
            ->with('status', 'Email verified. Your account is awaiting approval.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser();
        if (! $user) {
            return redirect()->route('register');
        }

        $code = EmailOtp::issue($user);

        return back()->with('status', 'A new code has been sent.')
            ->with('dev_otp', config('app.debug') ? $code : null);
    }

    private function pendingUser(): ?User
    {
        $id = session('pending_user_id');

        return $id ? User::find($id) : null;
    }
}
