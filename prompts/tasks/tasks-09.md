# Objective
## Super Admin Portal — Oversight-Only Portal for the Super Admin Role

---

## Context Sources
If the context above is not enough, it should be checked at:
- `prompts\plans\super-admin-portal-plan-technical.md`
- `prompts\plans\super-admin-portal-plan.md`

---

## Description
Give the Super Admin (SA) an oversight-only portal: its own landing and home, an Archive registry and Audit-log viewer, shared system config — and constrain the SA so it can no longer operate daily ops (dispatch/care/hospital/fleet). Today the SA is an all-access catch-all that can reach every screen, which contradicts the documented design where the Super Admin handles dev-team infrastructure plus read-only oversight, while day-to-day ops belong to the LGU and field roles. The core change is removing the wildcard inside `hasPermission()` so SA access derives entirely from its seeded role permissions, which are then narrowed to an oversight set. This plan depends on the LGU technical plan's `PortalRouter` helper and its City Settings screen, reusing both rather than duplicating them.

---

## Primary Objective
Constrain the Super Admin to an oversight-only portal — its own landing/home, an Archive registry UI, an Audit-log viewer, a health summary, and the core change of removing operational access (dispatch, care, hospital, fleet) — while keeping user management, archive, audit logs, system configuration, health monitoring, and shared approvals.

---

## Secondary Objectives
- SA landing & home: reuse the role-aware dashboard approach from the LGU plan, with SA-specific home cards.
- Constrain SA to oversight-only: remove the `isSuperAdmin()` wildcard bypass and narrow the seeded `super_admin` role to an explicit oversight permission set.
- Archive registry UI: one read-only browse screen over `archival_logs` with restore links to existing per-module actions.
- Audit & system logs UI: a read-only viewer over `audit_logs`.
- System configuration: reuse the single `admin.config.*` screen from the LGU technical plan, shared with no duplication.
- Health monitoring: a lightweight, read-only card group on the dashboard, not a new route or metrics stack.

---

## Supporting Tasks

### SA landing & home
- Rely on `PortalRouter::homeRouteFor()`, which already returns `'dashboard'` for anyone with `access-admin` (SA qualifies); no change needed beyond what the LGU plan adds.
- In `dashboard.blade.php`, wrap the SA's cards (User management, Archive registry, Audit logs, System settings, Health) in `@can('manage-users')`, `@can('manage-archive')`, `@can('view-audit-logs')`, `@can('manage-config')` so the same dashboard serves SA and LGU, each seeing only their cards.
- In `DashboardController::index`, which already computes `users/active/pending/archived`, add `archived_total` and optionally a recent-error count for the health card, guarded by permission.

### Constrain SA to oversight-only — the core change
- **Remove the wildcard from `hasPermission`:** edit `app/Models/User.php:80` to change `return $this->isSuperAdmin() || $this->permissionCodes()->contains($code);` to `return $this->permissionCodes()->contains($code);`, keeping `isSuperAdmin()` as a display/label helper that no longer bypasses permissions.
- **Narrow the seeded `super_admin` role:** edit `RolePermissionSeeder.php:48-52` to replace `->sync(Permission::pluck('id'))` with an explicit oversight set — keep `access-admin`, `manage-users`, `manage-archive`, `view-audit-logs`, `manage-config`, `review-approvals`, `review-org-approvals`, `view-incidents`, `view-reports`; exclude `dispatch-incidents`, `record-care`, `manage-hospitals`, `manage-fleet`, `drive-unit`.
- **State the break-glass tradeoff explicitly:** removing the wildcard removes the "SA can always get in" escape hatch; option (a) is a separate unseeded `developer_root` account_type retaining the bypass, option (b) is accepting that re-granting access is a seeder/DB change — recommend (b) for simplicity now, with (a) noted as the upgrade path.
- **Test fallout:** grep tests for `super` + dispatch/care/hospital/fleet assertions before changing the seeder, and flip any existing test asserting "super admin reaches X operational screen" to expect 403; `Gate::before` is unchanged.

### Archive registry UI (`manage-archive`)
- Add a route gated by `can.perm:manage-archive`: `GET /admin/archive` → `ArchiveController@index` (name `admin.archive.index`).
- Build `app/Http/Controllers/Admin/ArchiveController.php` with `index()` querying `archival_logs` ordered newest-first, paginated, grouped/filterable by `table_name`, passing each row's `table_name` and `record_id` so the view can link to the right module's restore route.
- Build the view `resources/views/admin/archive/index.blade.php` as a datatable with columns for type, item, who archived, reason, when, plus a 3-dot action with Restore (PATCH to the matching module restore route, confirmed via feedback modal) — reusing existing per-module restore controller methods with no new restore logic.

### Audit & system logs UI (`view-audit-logs`)
- Add a route gated by `can.perm:view-audit-logs`: `GET /admin/audit` → `AuditController@index` (name `admin.audit.index`).
- Build `app/Http/Controllers/Admin/AuditController.php` with `index()` paginating `audit_logs` newest-first, filterable by event/actor/date; optionally a second tab for a `system_logs` table if one exists.
- Build the view `resources/views/admin/audit/index.blade.php` as a read-only datatable: actor, event, target type/id, time, optional detail JSON — no write actions.

### System configuration — shared, no duplication
- Reuse the single `admin.config.*` screen defined in the LGU technical plan rather than building a second one, since `manage-config` is on both SA and LGU.
- Link the SA dashboard's "System settings" card to `admin.config.edit`.

### Health monitoring — lightweight
- Add a read-only card group on the dashboard, guarded by an oversight permission such as `view-audit-logs`, showing key counts (users/active/archived, open incidents) and, if `system_logs` exists, the latest few error rows — not a new route, and full monitoring/alerting is deferred.

### Sidebar
- Add `@can`-gated items in `_sidebar.blade.php`: Users (`manage-users`, exists), Archive (`manage-archive` → `admin.archive.index`, `ti ti-archive`), Logs (`view-audit-logs` → `admin.audit.index`, `ti ti-list-details`), System Settings (`manage-config` → `admin.config.edit`, `ti ti-settings`).

---

## Detailed Breakdown

### Verification — Tests
- Confirm SA reaches `/admin/users`, `/admin/archive`, `/admin/audit`, `/admin/config` (200).
- Confirm SA is now 403 on `/admin/dispatch`, `/admin/hospitals`, a care route, a driver route, and fleet store (proves the constraint).
- Confirm SA still passes oversight: `view-incidents`/`view-reports` read pages, if kept.
- Confirm LGU behavior is unchanged, and field permissions remain unassigned (only reachable once field roles are seeded).
- Regression: flip any prior "super admin can do everything" assertion to expect 403.

### Verification — Manual
- Run `php artisan migrate:fresh --seed`; log in as `superadmin@rescue.test / Password123!` and confirm landing on dashboard with the five SA cards.
- Open Archive (browse and restore one item), open Logs (read the trail), open System Settings.
- Confirm Dispatch/Care/Hospital/Fleet are gone from the sidebar and return 403 if hit directly.

### Verification — Cross-role check
- With the LGU and Citizen plans applied, confirm: super admin → dashboard (oversight), LGU → dashboard (governance), citizen → citizen.home — three roles, three correct homes, none able to reach the others' operational screens.

---

## Files
**New:** `app/Http/Controllers/Admin/ArchiveController.php`, `app/Http/Controllers/Admin/AuditController.php`, `resources/views/admin/archive/index.blade.php`, `resources/views/admin/audit/index.blade.php` (+ optional `app/Models/ArchivalLog.php`, `AuditLogEntry`).
**Edit:** `app/Models/User.php` (remove wildcard), `database/seeders/RolePermissionSeeder.php` (narrow super_admin), `routes/web.php` (archive + audit groups), `resources/views/layout/admin/partials/_sidebar.blade.php` (cards/items), `resources/views/dashboard.blade.php` (SA cards + health), `app/Http/Controllers/Admin/DashboardController.php` (oversight counts).
**Reuse:** `app/Support/PortalRouter.php` and `admin.config.*` (from the LGU technical plan); existing per-module `restore` actions; `AuditLog`.