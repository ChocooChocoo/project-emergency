<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'active' => User::where('account_status', 'active')->count(),
            'pending' => User::where('account_status', 'awaiting_approval')->count(),
            'archived' => User::where('is_archived', true)->count(),
        ];

        // Governance counts: computed only when the viewer can see the module.
        $u = auth()->user();
        $gov = [
            'pending_accounts' => $u->hasPermission('review-approvals')
                ? User::where('account_status', 'awaiting_approval')->count() : null,
            'pending_orgs' => $u->hasPermission('review-org-approvals')
                ? Organization::where('organization_status', 'pending_review')->count() : null,
            'flagged_incidents' => $u->hasPermission('view-incidents')
                ? Incident::where('is_flagged_for_abuse', true)->count() : null,
            'blocked_devices' => $u->hasPermission('manage-safety')
                ? DeviceToken::where('is_blocked', true)->count() : null,
            'archived_total' => $u->hasPermission('manage-archive')
                ? DB::table('archival_logs')->count() : null,
        ];

        return view('dashboard', compact('stats', 'gov'));
    }
}
