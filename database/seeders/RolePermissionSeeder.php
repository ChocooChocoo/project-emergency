<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Base permissions (one code per S3 module action; extend as later modules land).
        $permissions = [
            ['code' => 'access-admin',    'name' => 'Access admin console', 'module' => 'core'],
            ['code' => 'manage-users',    'name' => 'Manage users',         'module' => 'users'],
            ['code' => 'review-approvals', 'name' => 'Review account approvals', 'module' => 'approvals'],
            ['code' => 'view-audit-logs', 'name' => 'View audit & system logs', 'module' => 'audit'],
            ['code' => 'manage-config',   'name' => 'Manage system configuration', 'module' => 'config'],
            ['code' => 'manage-archive',  'name' => 'Manage archive registry', 'module' => 'archive'],
            // S4 — Organizations & Onboarding.
            ['code' => 'manage-organizations', 'name' => 'Manage organizations', 'module' => 'organizations'],
            ['code' => 'review-org-approvals', 'name' => 'Review organization approvals', 'module' => 'organizations'],
            // S5 — Fleet.
            ['code' => 'manage-fleet', 'name' => 'Manage fleet', 'module' => 'fleet'],
            // S6 — Citizen/Guest request intake.
            ['code' => 'view-incidents', 'name' => 'View incident requests', 'module' => 'incidents'],
            // S7 — DSS + Dispatch.
            ['code' => 'dispatch-incidents', 'name' => 'Dispatch incidents', 'module' => 'dispatch'],
            // S8 — Driver + live tracking.
            ['code' => 'drive-unit', 'name' => 'Drive ambulance unit', 'module' => 'driver'],
            // S9 — Medical + hospital handoff.
            ['code' => 'record-care', 'name' => 'Record pre-hospital care', 'module' => 'medical'],
            ['code' => 'manage-hospitals', 'name' => 'Manage hospitals & handoff', 'module' => 'hospitals'],
            // S10 — Anti-abuse / sustainability.
            ['code' => 'manage-safety', 'name' => 'Manage anti-abuse & ads', 'module' => 'safety'],
            // S11 — Reports + hardening.
            ['code' => 'view-reports', 'name' => 'View performance reports', 'module' => 'reports'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['code' => $p['code']], $p);
        }

        // Platform roles (organization_id = NULL => global). super_admin is oversight-only:
        // its access derives entirely from these rows now (no Gate::before wildcard). It keeps
        // user mgmt, archive, audit, config, approvals, and read-only incident/report oversight,
        // but NOT daily ops (dispatch/care/hospital/fleet/driver) or org/safety record management.
        $superAdmin = Role::updateOrCreate(
            ['organization_id' => null, 'name' => 'super_admin'],
            ['scope' => 'platform', 'description' => 'Dev-team root console', 'is_active' => true]
        );
        $superAdmin->permissions()->sync(
            Permission::whereIn('code', [
                'access-admin', 'manage-users', 'manage-archive', 'view-audit-logs',
                'manage-config', 'review-approvals', 'review-org-approvals',
                'view-incidents', 'view-reports',
            ])->pluck('id')
        );

        $lgu = Role::updateOrCreate(
            ['organization_id' => null, 'name' => 'platform_executive'],
            ['scope' => 'platform', 'description' => 'LGU / Platform Executive', 'is_active' => true]
        );
        // Governance-only: the LGU governs (approvals, settings, reports, oversight) and does
        // not dispatch, record care, or run hospital handoffs. The operational permissions
        // (dispatch-incidents, record-care, manage-hospitals, manage-fleet) belong to field/org
        // roles built later; they are unassigned — even super admin cannot reach them
        // (oversight-only; Gate::before wildcard was removed).
        $lgu->permissions()->sync(
            Permission::whereIn('code', [
                'access-admin', 'review-approvals', 'view-audit-logs', 'manage-config',
                'manage-organizations', 'review-org-approvals', 'view-incidents',
                'manage-safety', 'view-reports',
            ])->pluck('id')
        );
    }
}
