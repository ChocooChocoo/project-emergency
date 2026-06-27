<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Org\StoreOrganizationRequest;
use App\Http\Requests\Org\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = $request->query('type');
        $status = $request->query('status');

        $organizations = Organization::query()
            ->with('subscription.plan')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->when($type, fn ($q) => $q->where('org_type', $type))
            ->when($status, fn ($q) => $q->where('organization_status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.organizations.index', compact('organizations', 'search', 'type', 'status'));
    }

    public function create(): View
    {
        return view('admin.organizations.create', [
            'plans' => Plan::where('is_active', true)->orderBy('name')->get(),
            'admins' => $this->candidateAdmins(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $planId = $data['plan_id'];
        unset($data['plan_id']);

        $org = DB::transaction(function () use ($data, $planId) {
            $org = Organization::create($data + [
                'organization_status' => 'pending_review',
                'is_approved' => false,
                'is_active' => false,
            ]);

            $org->subscription()->create([
                'plan_id' => $planId,
                'status' => 'trialing',
            ]);

            return $org;
        });

        AuditLog::record('organization.created', Organization::class, $org->id);

        return redirect()->route('admin.organizations.show', $org)
            ->with('status', "Organization \"{$org->name}\" created (pending review).");
    }

    public function show(Organization $organization): View
    {
        $organization->load(['subscription.plan', 'documents', 'coverageAreas', 'admin', 'approvedBy', 'ambulances']);

        return view('admin.organizations.show', compact('organization'));
    }

    public function edit(Organization $organization): View
    {
        return view('admin.organizations.edit', [
            'organization' => $organization->load('subscription'),
            'plans' => Plan::where('is_active', true)->orderBy('name')->get(),
            'admins' => $this->candidateAdmins(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $data = $request->validated();
        $planId = $data['plan_id'];
        unset($data['plan_id']);

        DB::transaction(function () use ($data, $planId, $organization) {
            $organization->update($data);
            $organization->subscription()->updateOrCreate(
                ['organization_id' => $organization->id],
                ['plan_id' => $planId]
            );
        });

        AuditLog::record('organization.updated', Organization::class, $organization->id);

        return redirect()->route('admin.organizations.show', $organization)
            ->with('status', 'Organization updated.');
    }

    public function archive(Request $request, Organization $organization): RedirectResponse
    {
        $request->validate(['archive_reason' => ['nullable', 'string', 'max:500']]);

        DB::transaction(function () use ($request, $organization) {
            $organization->update([
                'is_archived' => true,
                'archived_at' => now(),
                'archived_by' => $request->user()->id,
                'archive_reason' => $request->input('archive_reason'),
                'is_active' => false,
            ]);

            DB::table('archival_logs')->insert([
                'table_name' => 'organizations',
                'record_id' => $organization->id,
                'archived_by' => $request->user()->id,
                'archive_reason' => $request->input('archive_reason'),
                'archived_at' => now(),
                'snapshot_json' => $organization->toJson(),
            ]);
        });

        AuditLog::record('organization.archived', Organization::class, $organization->id);

        return back()->with('status', 'Organization archived.');
    }

    public function restore(Organization $organization): RedirectResponse
    {
        $organization->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
            'archive_reason' => null,
        ]);
        AuditLog::record('organization.restored', Organization::class, $organization->id);

        return back()->with('status', 'Organization restored.');
    }

    /** Personnel/org-admin candidates an org can be tied to as its admin contact. */
    private function candidateAdmins()
    {
        return User::whereIn('account_type', ['personnel', 'org_admin'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }
}
