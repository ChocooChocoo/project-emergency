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

        // Platform roles (organization_id = NULL => global). super_admin needs no explicit
        // permission rows — Gate::before grants it everything — but we attach all anyway
        // so the relationship is queryable.
        $superAdmin = Role::updateOrCreate(
            ['organization_id' => null, 'name' => 'super_admin'],
            ['scope' => 'platform', 'description' => 'Dev-team root console', 'is_active' => true]
        );
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        $lgu = Role::updateOrCreate(
            ['organization_id' => null, 'name' => 'platform_executive'],
            ['scope' => 'platform', 'description' => 'LGU / Platform Executive', 'is_active' => true]
        );
        $lgu->permissions()->sync(
            Permission::whereIn('code', [
                'access-admin', 'review-approvals', 'view-audit-logs', 'manage-config',
                'manage-organizations', 'review-org-approvals', 'manage-fleet', 'view-incidents',
                // S7–S10: LGU runs dispatch, care, hospitals and safety. drive-unit is a field
                // role granted per-driver, so it is deliberately not on the platform executive.
                'dispatch-incidents', 'record-care', 'manage-hospitals', 'manage-safety',
                // S11: LGU reviews performance reports.
                'view-reports',
            ])->pluck('id')
        );
    }
}
