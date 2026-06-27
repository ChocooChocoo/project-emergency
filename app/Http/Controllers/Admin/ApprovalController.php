<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLog;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(): View
    {
        $pending = User::where('account_status', 'awaiting_approval')
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.approvals.index', compact('pending'));
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->update([
                'account_status' => 'active',
                'is_approved' => true,
                'is_active' => true,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $this->logApprovalRecord($user, 'approved', $request->user()->id);
        });

        AuditLog::record('account.approved', User::class, $user->id);
        Notifier::send($user->id, 'Account approved', 'Your account has been approved. You can now sign in.', 'account');

        return back()->with('status', "Approved {$user->full_name}.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        DB::transaction(function () use ($data, $user, $request) {
            $user->update([
                'account_status' => 'rejected',
                'is_approved' => false,
                'rejected_reason' => $data['reason'],
                'rejection_count' => $user->rejection_count + 1,
            ]);

            $this->logApprovalRecord($user, 'rejected', $request->user()->id, $data['reason']);
        });

        AuditLog::record('account.rejected', User::class, $user->id, ['reason' => $data['reason']]);
        Notifier::send($user->id, 'Account rejected', "Your registration was rejected: {$data['reason']}", 'account');

        return back()->with('status', "Rejected {$user->full_name}.");
    }

    private function logApprovalRecord(User $user, string $status, int $reviewerId, ?string $notes = null): void
    {
        DB::table('approval_records')->insert([
            'target_type' => 'user',
            'target_id' => $user->id,
            'request_type' => 'account_activation',
            'status' => $status,
            'organization_id' => $user->organization_id,
            'reviewed_by' => $reviewerId,
            'notes' => $notes,
            'requested_at' => $user->created_at ?? now(),
            'reviewed_at' => now(),
        ]);
    }
}
