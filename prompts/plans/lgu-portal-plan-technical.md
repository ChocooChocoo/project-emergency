# LGU Executive Portal — Technical Implementation Plan

> Companion to `lgu-portal-plan.md` (non-technical). This is the build-ready spec.
> Goal: give the LGU (`platform_executive`) a governance-only portal — their own landing + home, a City Settings screen, and a role tightened to remove field-operations access.
> Stack rules: follow `CLAUDE.md` (Tabler UI, `can.perm` gating, `AuditLog`, light badges, datatable pattern, feedback modal for confirms).

---

## Context

Today every console user — LGU included — is redirected to `route('dashboard')` after login (`LoginController::authenticate` and `VerifyEmailController::verify`), and the sidebar/dashboard are generic. The LGU role also holds operational permissions (`dispatch-incidents`, `record-care`, `manage-hospitals`, `manage-fleet`) it shouldn't. This plan delivers the four "still needed" items from the non-technical plan §6:

1. Role-aware landing (LGU lands on its own home).
2. LGU home dashboard (opens to the five governance areas with quick counts).
3. City Settings screen (edit `system_configurations`, e.g. `dss_timeout_seconds`).
4. Role cleanup (LGU = governance-only).

**Scope guard:** governance only — Approvals, Organizations/Org-Approvals, City Settings, Reports, Safety. No dispatch/care/hospital in the LGU portal.

---

## Verified facts (code-traced)

- Post-login redirect lives in **two** places: `app/Http/Controllers/Auth/LoginController.php:52` and `app/Http/Controllers/Auth/VerifyEmailController.php:49` — both hardcode `route('dashboard')`.
- `/dashboard` is gated by `can.perm:access-admin` (`routes/web.php:54-55`). LGU has `access-admin`; citizens don't (that's their separate 403 — handled in the Citizen plan).
- Permission check: `User::hasPermission($code)` (`app/Models/User.php:80`) → `isSuperAdmin() || permissionCodes()->contains($code)`.
- LGU role + permissions seeded in `database/seeders/RolePermissionSeeder.php:54-68` (the `platform_executive` `sync([...])` array).
- City config table exists: `system_configurations` (`scope`,`organization_id`,`config_key`,`config_value`,`config_type`,`description`,`updated_by`) — migration `database/migrations/2026_06_26_000070_create_system_tables.php:65`. `manage-config` permission is already seeded to the LGU. `DispatchController::dssTimeoutSeconds()` already reads `scope=global, config_key=dss_timeout_seconds` (`app/Http/Controllers/Admin/DispatchController.php:126-133`) — the City Settings screen writes the same row.
- Dashboard = `DashboardController::index` → `resources/views/dashboard.blade.php`. Sidebar = `resources/views/layout/admin/partials/_sidebar.blade.php` (items already wrapped in `@can`).

---

## 1. Role-aware landing

**Add one helper, reuse it in both redirects.** Don't duplicate logic.

- New: `app/Support/PortalRouter.php` — `homeRouteFor(User $user): string`. Logic (first match wins):
  - `isSuperAdmin()` or `hasPermission('access-admin')` → `'dashboard'` (console users: super admin + LGU).
  - else → `'request.create'` (citizens; see Citizen plan for their dedicated landing later).
  - *ponytail: a small match()-style helper, not a routing framework. Extend with field-role cases as those portals land.*
- Edit `LoginController::authenticate` — replace `redirect()->intended(route('dashboard'))` with `redirect()->intended(route(PortalRouter::homeRouteFor($user)))`.
- Edit `VerifyEmailController::verify` — replace `redirect()->route('dashboard')` with `redirect()->route(PortalRouter::homeRouteFor($user))`.

This alone makes the LGU land correctly today (they have `access-admin`) and stops the citizen 403 at the source (citizen → `request.create`).

---

## 2. LGU home dashboard

The LGU already lands on `/dashboard`. Make that dashboard **role-aware** rather than building a second route — least code, one landing.

- Edit `DashboardController::index`: in addition to the existing `$stats`, compute governance counts **only when the viewer can see them** (guard each with `hasPermission`), so the same view serves super admin and LGU:
  - `pending_accounts` = `User::where('account_status','awaiting_approval')->count()` (already partly there as `pending`).
  - `pending_orgs` = `Organization::where('organization_status','pending_review')->count()`.
  - `flagged_incidents` = `Incident::where('is_flagged_for_abuse',true)->count()`.
  - `blocked_devices` = `DeviceToken::where('is_blocked',true)->count()`.
- Edit `resources/views/dashboard.blade.php`: add a **governance quick-cards row** wrapped in `@can('review-approvals')`, `@can('review-org-approvals')`, `@can('manage-safety')` etc., each card linking to its module (`admin.approvals.index`, `admin.org-approvals.index`, `admin.safety.index`, `admin.reports.index`, the new `admin.config.edit`). Use Tabler card + light badge counts. Cards a user can't access simply don't render (`@can` hides them) — so super admin sees all, LGU sees governance, and the page stays one view.

*No new route/controller — the dashboard becomes the LGU home by showing their cards.*

---

## 3. City Settings screen (`manage-config`)

A small CRUD-of-one over `system_configurations` global rows. Reuse the existing module pattern (Form Request + `AuditLog` + redirect-with-status).

- **Route** (`routes/web.php`, new group): `can.perm:manage-config`
  - `GET /admin/config` → `ConfigController@edit` (name `admin.config.edit`)
  - `PUT /admin/config` → `ConfigController@update` (name `admin.config.update`)
- **Controller:** `app/Http/Controllers/Admin/ConfigController.php`
  - `edit()`: load the global settings rows (at minimum `dss_timeout_seconds`) → `view('admin.config.edit', ...)`.
  - `update()`: validate (`dss_timeout_seconds` → `integer, min:10, max:600`); `DB::table('system_configurations')->updateOrInsert(['scope'=>'global','organization_id'=>null,'config_key'=>'dss_timeout_seconds'], ['config_value'=>..., 'config_type'=>'int','updated_by'=>$request->user()->id])`; `AuditLog::record('config.updated','system_configurations',null,['key'=>'dss_timeout_seconds'])`; `back()->with('status', ...)`.
- **View:** `resources/views/admin/config/edit.blade.php` — Tabler form card, one numeric field (seconds) with help text "How long a unit has to respond before dispatch moves on." Submit confirms via the feedback modal (`confirmAction`) per CLAUDE.md.
- **Form Request (optional):** `app/Http/Requests/Config/UpdateConfigRequest.php` if validation grows; inline `$request->validate` is fine for one field.
- **Sidebar:** add a "City Settings" item in `_sidebar.blade.php` wrapped in `@can('manage-config')`, pointing to `admin.config.edit`, `ti ti-settings` icon.

*ponytail: one tunable today (`dss_timeout_seconds`). Add fields as more global vars become real — don't pre-build a generic settings engine.*

---

## 4. Role cleanup — LGU = governance-only

Tighten the seeded `platform_executive` permission set; move operational perms off the LGU.

- Edit `database/seeders/RolePermissionSeeder.php:58-68` — remove from the `platform_executive` `sync([...])` array: `dispatch-incidents`, `record-care`, `manage-hospitals`, `manage-fleet`. Keep: `access-admin`, `review-approvals`, `view-audit-logs`, `manage-config`, `manage-organizations`, `review-org-approvals`, `view-incidents` (read-only city oversight is fine), `manage-safety`, `view-reports`.
  - *Decision to confirm at build time: keep `view-incidents` (read-only) on the LGU? It's oversight, not operation — recommend keep. `manage-fleet` is borderline (fleet registry is arguably org-admin's job) — recommend remove from LGU now, give to Org Admin when that portal lands.*
- Those four perms now belong to **field/org roles** that don't exist yet. Add them when building those portals (Dispatcher→`dispatch-incidents`, Medic→`record-care`, Hospital→`manage-hospitals`, Org Admin→`manage-fleet`). Until then they're simply unassigned (only super admin reaches them via `Gate::before`).
- **Re-seed:** `php artisan migrate:fresh --seed` (dev) — `RolePermissionSeeder` uses `updateOrCreate` + `sync`, so the LGU role's perms are replaced cleanly.

---

## Files

**New:** `app/Support/PortalRouter.php`, `app/Http/Controllers/Admin/ConfigController.php`, `resources/views/admin/config/edit.blade.php` (+ optional `app/Http/Requests/Config/UpdateConfigRequest.php`).
**Edit:** `LoginController.php`, `VerifyEmailController.php`, `DashboardController.php`, `resources/views/dashboard.blade.php`, `resources/views/layout/admin/partials/_sidebar.blade.php`, `routes/web.php`, `database/seeders/RolePermissionSeeder.php`.

---

## Verification

1. **Tests** (`php artisan test`):
   - Update/add: LGU logs in → redirected to `dashboard` (has `access-admin`); LGU can `GET /admin/config` (200) and `PUT` a valid timeout; LGU is now **403** on `GET /admin/dispatch`, `/admin/hospitals`, and a care route (proves role cleanup).
   - Super admin still reaches everything; citizen redirect covered in Citizen plan.
   - Existing `DispatchTest` must still pass — confirm the timeout it reads matches what City Settings writes.
2. **Manual:** `php artisan migrate:fresh --seed`; log in `lgu@rescue.test / Password123!` → lands on dashboard showing governance cards; open City Settings, change timeout, confirm it persists and dispatch uses it; confirm Dispatch/Hospitals/Care are not in the sidebar and 403 if hit directly.
3. **Regression:** super admin sidebar unchanged (still sees all); no view references a removed permission for the LGU that breaks layout.
