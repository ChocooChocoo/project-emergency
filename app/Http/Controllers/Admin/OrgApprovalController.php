<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationDocument;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrgApprovalController extends Controller
{
    public function index(): View
    {
        $pending = Organization::where('organization_status', 'pending_review')
            ->with('subscription.plan')
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.org-approvals.index', compact('pending'));
    }

    public function show(Organization $organization): View
    {
        $organization->load(['documents', 'subscription.plan', 'admin', 'coverageAreas']);

        return view('admin.org-approvals.show', compact('organization'));
    }

    public function approve(Request $request, Organization $organization): RedirectResponse
    {
        DB::transaction(function () use ($request, $organization) {
            $organization->update([
                'organization_status' => 'active',
                'is_approved' => true,
                'is_active' => true,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'rejected_reason' => null,
            ]);

            $this->logApprovalRecord($organization, 'approved', $request->user()->id);
        });

        AuditLog::record('organization.approved', Organization::class, $organization->id);

        return redirect()->route('admin.org-approvals.index')
            ->with('status', "Approved {$organization->name}.");
    }

    public function reject(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        DB::transaction(function () use ($data, $organization, $request) {
            $organization->update([
                'organization_status' => 'rejected',
                'is_approved' => false,
                'is_active' => false,
                'rejected_reason' => $data['reason'],
            ]);

            $this->logApprovalRecord($organization, 'rejected', $request->user()->id, $data['reason']);
        });

        AuditLog::record('organization.rejected', Organization::class, $organization->id, ['reason' => $data['reason']]);

        return redirect()->route('admin.org-approvals.index')
            ->with('status', "Rejected {$organization->name}.");
    }

    /** Validate or reject a single uploaded org document. */
    public function updateDocumentStatus(Request $request, Organization $organization, OrganizationDocument $document): RedirectResponse
    {
        abort_unless($document->organization_id === $organization->id, 404);

        $data = $request->validate([
            'validation_status' => ['required', 'in:validated,rejected,pending'],
        ]);

        $document->update([
            'validation_status' => $data['validation_status'],
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
        ]);

        AuditLog::record('organization.document.'.$data['validation_status'], OrganizationDocument::class, $document->id);

        return back()->with('status', 'Document marked '.$data['validation_status'].'.');
    }

    private function logApprovalRecord(Organization $org, string $status, int $reviewerId, ?string $notes = null): void
    {
        DB::table('approval_records')->insert([
            'target_type' => 'organization',
            'target_id' => $org->id,
            'request_type' => 'org_onboarding',
            'status' => $status,
            'organization_id' => $org->id,
            'reviewed_by' => $reviewerId,
            'notes' => $notes,
            'requested_at' => $org->created_at ?? now(),
            'reviewed_at' => now(),
        ]);
    }
}
