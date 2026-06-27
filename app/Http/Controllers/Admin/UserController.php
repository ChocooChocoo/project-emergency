<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = $request->query('type');
        $status = $request->query('status');

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($type, fn ($q) => $q->where('account_type', $type))
            ->when($status, fn ($q) => $q->where('account_status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'type', 'status'));
    }

    public function show(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::record($user->is_active ? 'user.activated' : 'user.deactivated', User::class, $user->id);

        return back()->with('status', 'User '.($user->is_active ? 'activated' : 'deactivated').'.');
    }

    public function archive(Request $request, User $user): RedirectResponse
    {
        $request->validate(['archive_reason' => ['nullable', 'string', 'max:500']]);

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'is_archived' => true,
                'archived_at' => now(),
                'archived_by' => $request->user()->id,
                'archive_reason' => $request->input('archive_reason'),
                'is_active' => false,
            ]);

            DB::table('archival_logs')->insert([
                'table_name' => 'users',
                'record_id' => $user->id,
                'archived_by' => $request->user()->id,
                'archive_reason' => $request->input('archive_reason'),
                'archived_at' => now(),
                'snapshot_json' => $user->toJson(),
            ]);
        });

        AuditLog::record('user.archived', User::class, $user->id);

        return back()->with('status', 'User archived.');
    }

    public function restore(User $user): RedirectResponse
    {
        $user->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
            'archive_reason' => null,
        ]);
        AuditLog::record('user.restored', User::class, $user->id);

        return back()->with('status', 'User restored.');
    }
}
