<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\EmailOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showForm(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $termsVersion = config('app.terms_version', 'v1');

        $user = User::create([
            'account_type' => 'citizen',
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'account_status' => 'pending_otp',
            'is_active' => true,
            'is_approved' => true,
            'terms_accepted_at' => now(),
            'terms_version' => $termsVersion,
        ]);

        DB::table('terms_acceptance_logs')->insert([
            'user_id' => $user->id,
            'terms_version' => $termsVersion,
            'accepted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $code = EmailOtp::issue($user);
        session()->put('pending_user_id', $user->id);

        return redirect()->route('verify-email.show')
            ->with('dev_otp', config('app.debug') ? $code : null);
    }
}
