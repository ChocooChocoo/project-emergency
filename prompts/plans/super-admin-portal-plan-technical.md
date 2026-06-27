# Super Admin Portal — Technical Implementation Plan

> Companion to `super-admin-portal-plan.md` (non-technical, same folder). This is the build-ready spec.
> Goal: give the Super Admin (SA) an oversight-only portal — its own landing + home, an Archive registry and Audit-log viewer, shared system config — and **constrain the SA so it can no longer operate daily ops** (dispatch/care/hospital/fleet).
> Stack rules: follow `CLAUDE.md` (Tabler UI, `can.perm` gating, `AuditLog`, datatable pattern, feedback modal).

---

## Context

The SA today is an **all-access catch-all**: it can reach every screen. That contradicts the docs (`DOCUMENT ARCHITECTURE.md:198` and `PROCESS AND FLOW.md §8`: Super Admin = dev-team infrastructure + **read-only** oversight; day-to-day ops belong to the LGU/field). This plan delivers the non-technical plan's "still needed" items: SA landing/home, Archive registry UI, Audit-log viewer, a health summary, and — the core change — **constrain the SA to oversight-only**.

**Depends on** the LGU technical plan's `PortalRouter` helper (role-aware landing) and its City Settings screen (`admin.config.*`). Build the LGU plan first, or create those here.

**The sensitive part:** the SA's "everything" is not seeded permissions — it's a wildcard inside `hasPermission()`. Constraining the SA means **removing that wildcard**, which removes the break-glass catch-all. Handled carefully below.

---

## Verified facts (code-traced)

- Wildcard location: `app/Models/User.php:80` — `hasPermission($code) = isSuperAdmin() || permissionCodes()->contains($code)`. `isSuperAdmin()` (`:66`) = `account_type === 'super_admin'`. **The `isSuperAdmin() ||` is the all-access bypass.**
- Gate: `app/Providers/AppServiceProvider.php:21-23` delegates to `hasPermission` — **unchanged by this plan** (it keeps working once the wildcard moves out of `hasPermission`).
- The `super_admin` **role already has explicit permission rows**: `RolePermissionSeeder.php:48-52` does `$superAdmin->permissions()->sync(Permission::pluck('id'))`. So removing the wildcard does **not** lock the SA out — its access then derives from these seeded rows, which we narrow.
- Archive data already exists: `archival_logs` rows are written by `UserController::archive` (`app/Http/Controllers/Admin/UserController.php:61`), `OrganizationController::archive`, `AmbulanceController::archive` (note: ambulance archive does **not** snapshot — see Citizen/06 notes), and Hospital. `manage-archive` permission seeded; no UI yet.
- Audit data already exists: `AuditLog::record(...)` writes `audit_logs` everywhere. `view-audit-logs` permission seeded; no UI yet.
- Each module already has a `restore` action (e.g. `admin.users.restore`, `admin.organizations.restore`, `admin.ambulances.restore`) the Archive registry can link to.
- Dashboard = `DashboardController::index` → `resources/views/dashboard.blade.php`; sidebar `@can`-gated (`resources/views/layout/admin/partials/_sidebar.blade.php`).

---

## 1. SA landing & home

Reuse the role-aware dashboard approach from the LGU plan — no separate route.

- **Redirect:** `PortalRouter::homeRouteFor()` already returns `'dashboard'` for anyone with `access-admin` (SA qualifies). No change needed beyond what the LGU plan adds.
- **Home cards:** in `dashboard.blade.php`, the SA's cards (User management, Archive registry, Audit logs, System settings, Health) are wrapped in `@can('manage-users')`, `@can('manage-archive')`, `@can('view-audit-logs')`, `@can('manage-config')` — so the same dashboard serves SA and LGU; each sees only their cards. `DashboardController::index` already computes `users/active/pending/archived`; add `archived_total` and (optionally) recent-error count for the health card, guarded by permission.

---

## 2. Constrain SA to oversight-only — the core change

Careful, in two edits + a test sweep:

### 2a. Remove the wildcard from `hasPermission`
- Edit `app/Models/User.php:80`: change
  `return $this->isSuperAdmin() || $this->permissionCodes()->contains($code);`
  to
  `return $this->permissionCodes()->contains($code);`
- Keep `isSuperAdmin()` as a helper (it's used for display/labels), but it **no longer bypasses permissions**. SA access now comes entirely from its seeded role permissions.

### 2b. Narrow the seeded `super_admin` role
- Edit `RolePermissionSeeder.php:48-52`: replace `->sync(Permission::pluck('id'))` (all) with an explicit oversight set:
  - **Keep:** `access-admin`, `manage-users`, `manage-archive`, `view-audit-logs`, `manage-config`, `review-approvals`, `review-org-approvals`, plus read-only oversight `view-incidents`, `view-reports`.
  - **Exclude:** `dispatch-incidents`, `record-care`, `manage-hospitals`, `manage-fleet`, `drive-unit`.
  - *Confirm at build: keep `view-incidents`/`view-reports`? They're read-only oversight — recommend keep. `manage-fleet` excluded (belongs to Org Admin).*

### 2c. Break-glass tradeoff (state explicitly)
- Removing the wildcard removes the "SA can always get in" escape hatch. Options:
  - **(a)** keep a separate, **unseeded** `developer_root` account_type that retains the bypass (a true break-glass kept out of normal seeds), or
  - **(b)** accept that re-granting access is a seeder/DB change.
  - *Recommend (b) for simplicity now; note (a) as the upgrade path. ponytail: don't build a second privilege tier until it's actually needed.*

### 2d. Test fallout
- Any existing test asserting "super admin reaches X operational screen" must flip to **expect 403**. `Gate::before` is unchanged. Grep tests for `super` + dispatch/care/hospital/fleet assertions before changing the seeder so the suite is updated in the same pass.

---

## 3. Archive registry UI (`manage-archive`) — *new*

Read-only browse over `archival_logs` + restore links to existing per-module actions.

- **Route** (`routes/web.php`, new group `can.perm:manage-archive`): `GET /admin/archive` → `ArchiveController@index` (name `admin.archive.index`).
- **Controller:** `app/Http/Controllers/Admin/ArchiveController.php` — `index()`: `DB::table('archival_logs')->orderByDesc('archived_at')->paginate()` (or an `ArchivalLog` model if preferred), grouped/filterable by `table_name`. Pass each row's `table_name` + `record_id` so the view can link to the right module's `restore` route.
- **View:** `resources/views/admin/archive/index.blade.php` — datatable (CLAUDE.md pattern): columns = type, item, who archived, reason, when, + a 3-dot action with **Restore** (PATCH to the matching module restore route; confirm via feedback modal). Restore reuses the existing per-module `restore` controller methods — **no new restore logic.**

---

## 4. Audit & system logs UI (`view-audit-logs`) — *new*

Read-only viewer over `audit_logs`.

- **Route** (`can.perm:view-audit-logs`): `GET /admin/audit` → `AuditController@index` (name `admin.audit.index`).
- **Controller:** `app/Http/Controllers/Admin/AuditController.php` — `index()`: paginate `audit_logs` newest-first, filter by event/actor/date. (If a `system_logs` table exists, a second tab can surface it; optional.)
- **View:** `resources/views/admin/audit/index.blade.php` — datatable: actor, event, target type/id, time, optional detail JSON. **Read-only — no write actions.**

---

## 5. System configuration — shared, no duplication

- `manage-config` is on both SA and LGU. **Reuse the single `admin.config.*` screen** defined in the LGU technical plan — don't build a second one. The SA's dashboard "System settings" card links to `admin.config.edit`.

---

## 6. Health monitoring — *lightweight, ▲*

- Not a new route — a **read-only card group on the dashboard** (guarded by an oversight permission, e.g. `view-audit-logs`): key counts (users/active/archived, open incidents) and, if `system_logs` exists, the latest few error rows. Full monitoring/alerting is deferred. ponytail: a card, not a metrics stack.

---

## 7. Sidebar

- Add `@can`-gated items in `_sidebar.blade.php`: **Users** (`manage-users`, exists), **Archive** (`manage-archive` → `admin.archive.index`, `ti ti-archive`), **Logs** (`view-audit-logs` → `admin.audit.index`, `ti ti-list-details`), **System Settings** (`manage-config` → `admin.config.edit`, `ti ti-settings`).

---

## Files

**New:** `app/Http/Controllers/Admin/ArchiveController.php`, `app/Http/Controllers/Admin/AuditController.php`, `resources/views/admin/archive/index.blade.php`, `resources/views/admin/audit/index.blade.php` (+ optional `app/Models/ArchivalLog.php`, `AuditLogEntry`).
**Edit:** `app/Models/User.php` (remove wildcard), `database/seeders/RolePermissionSeeder.php` (narrow super_admin), `routes/web.php` (archive + audit groups), `resources/views/layout/admin/partials/_sidebar.blade.php` (cards/items), `resources/views/dashboard.blade.php` (SA cards + health), `app/Http/Controllers/Admin/DashboardController.php` (oversight counts).
**Reuse:** `app/Support/PortalRouter.php` and `admin.config.*` (from the LGU technical plan); existing per-module `restore` actions; `AuditLog`.

---

## Verification

1. **Tests** (`php artisan test`):
   - SA reaches `/admin/users`, `/admin/archive`, `/admin/audit`, `/admin/config` (200).
   - SA now **403** on `/admin/dispatch`, `/admin/hospitals`, a care route, a driver route, fleet store (proves the constraint).
   - SA still passes oversight: `view-incidents`/`view-reports` read pages (if kept).
   - LGU behavior unchanged; field permissions remain unassigned (only reachable once field roles are seeded).
   - **Regression:** flip any prior "super admin can do everything" assertion to expect 403.
2. **Manual:** `php artisan migrate:fresh --seed`; log in `superadmin@rescue.test / Password123!` → lands on dashboard with the five SA cards; open Archive (browse + restore one item), open Logs (read the trail), open System Settings; confirm Dispatch/Care/Hospital/Fleet are **gone** from the sidebar and 403 if hit directly.
3. **Cross-role check** (with LGU + Citizen plans applied): super admin → dashboard (oversight), LGU → dashboard (governance), citizen → citizen.home — three roles, three correct homes, none able to reach the others' operational screens.
