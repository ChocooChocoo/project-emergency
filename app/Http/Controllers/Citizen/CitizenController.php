<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Citizen portal — landing + account screens for registered citizens.
 * Every action is scoped to the authenticated user (auth()->id()); a citizen
 * only ever sees or edits their own data. Console users are bounced to /dashboard.
 */
class CitizenController extends Controller
{
    public function home(): View|RedirectResponse
    {
        // A misrouted staffer belongs on the admin console, not here.
        if (auth()->user()->hasPermission('access-admin')) {
            return redirect()->route('dashboard');
        }

        return view('citizen.home');
    }

    public function profile(): View
    {
        return view('citizen.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'alt_phone' => ['nullable', 'string', 'max:30'],
        ]);

        auth()->user()->update($data);
        AuditLog::record('citizen.profile_updated', User::class, auth()->id());

        return redirect()->route('citizen.profile')->with('status', 'Profile updated.');
    }

    public function medical(): View
    {
        return view('citizen.medical', ['medical' => auth()->user()->medical_info ?? []]);
    }

    public function updateMedical(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'blood_type' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        auth()->user()->update(['medical_info' => $data]);
        AuditLog::record('citizen.medical_updated', User::class, auth()->id());

        return redirect()->route('citizen.medical')->with('status', 'Medical info saved.');
    }

    public function history(): View
    {
        $incidents = Incident::where('user_id', auth()->id())->latest()->paginate(15);

        return view('citizen.history', compact('incidents'));
    }
}
