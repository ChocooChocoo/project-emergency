# Objective
## LGU Executive Portal — Governance-Only Portal for the Platform Executive Role

---

## Context Sources
If the context above is not enough, it should be checked at:
- `prompts\plans\citizen-portal-plan-technical.md`
- `prompts\plans\citizen-portal-plan.md`

---

## Description
Give the LGU (`platform_executive`) a governance-only portal: their own landing and home, a City Settings screen, and a role tightened to remove field-operations access. Today every console user, LGU included, is redirected to the generic `dashboard` after login, and the LGU role also holds operational permissions (`dispatch-incidents`, `record-care`, `manage-hospitals`, `manage-fleet`) it shouldn't have. The plan delivers four "still needed" items so the LGU portal contains only the five governance areas — Approvals, Organizations/Org-Approvals, City Settings, Reports, Safety — with no dispatch, care, or hospital access. By design the LGU governs (approvals, settings, reports, oversight) and does not dispatch ambulances, record patient care, or run hospital handoffs, since those are field-user jobs.

---

## Primary Objective
Deliver a role-aware landing, an LGU home dashboard, a City Settings screen, and a role cleanup so that the `platform_executive` role's access becomes a governance-only portal — scoped strictly to Approvals, Organizations/Org-Approvals, City Settings, Reports, and Safety.

---

## Secondary Objectives
- Role-aware landing: the LGU goes to their own home after login, not a generic dashboard.
- LGU home dashboard: opens to the five governance areas with quick counts (pending approvals, flags, etc.).
- City Settings screen: adjust shared rules (e.g. response-timeout) without touching data directly.
- Role cleanup: the LGU portal is governance-only, with operational permissions moved to field roles as those portals are built.

---

## Supporting Tasks

### Role-aware landing
- Add one helper, `app/Support/PortalRouter.php` with `homeRouteFor(User $user): string`, and reuse it in both redirects rather than duplicating logic.
- Logic (first match wins): `isSuperAdmin()` or `hasPermission('access-admin')` → `'dashboard'` (console users: super admin + LGU); else → `'request.create'` (citizens).
- Edit `LoginController::authenticate` to replace `redirect()->intended(route('dashboard'))` with `redirect()->intended(route(PortalRouter::homeRouteFor($user)))`.
- Edit `VerifyEmailController::verify` to replace `redirect()->route('dashboard')` with `redirect()->route(PortalRouter::homeRouteFor($user))`.

### LGU home dashboard
- Make the existing `/dashboard` role-aware rather than building a second route.
- Edit `DashboardController::index` to compute governance counts only when the viewer can see them (guard each with `hasPermission`): `pending_accounts`, `pending_orgs`, `flagged_incidents`, `blocked_devices`.
- Edit `resources/views/dashboard.blade.php` to add a governance quick-cards row wrapped in the relevant `@can(...)` checks, each card linking to its module, using Tabler card + light badge counts.
- Cards a user can't access simply don't render, so super admin sees all, LGU sees governance, and the page stays one view.

### City Settings screen (`manage-config`)
- Add routes gated by `can.perm:manage-config`: `GET /admin/config` → `ConfigController@edit` (`admin.config.edit`); `PUT /admin/config` → `ConfigController@update` (`admin.config.update`).
- Build `app/Http/Controllers/Admin/ConfigController.php` with `edit()` loading the global settings rows (at minimum `dss_timeout_seconds`) and `update()` validating and writing via `updateOrInsert`, recording an `AuditLog` entry, and redirecting back with status.
- Build the view `resources/views/admin/config/edit.blade.php` as a Tabler form card with one numeric field (seconds) and help text, with submit confirmed via the feedback modal (`confirmAction`).
- Optionally add `app/Http/Requests/Config/UpdateConfigRequest.php` if validation grows; inline `$request->validate` is fine for one field.
- Add a "City Settings" item in `_sidebar.blade.php` wrapped in `@can('manage-config')`, pointing to `admin.config.edit`, with the `ti ti-settings` icon.

### Role cleanup — LGU = governance-only
- Edit `database/seeders/RolePermissionSeeder.php` to remove from the `platform_executive` `sync([...])` array: `dispatch-incidents`, `record-care`, `manage-hospitals`, `manage-fleet`.
- Keep on the LGU: `access-admin`, `review-approvals`, `view-audit-logs`, `manage-config`, `manage-organizations`, `review-org-approvals`, `view-incidents`, `manage-safety`, `view-reports`.
- The four removed permissions belong to field/org roles that don't exist yet (Dispatcher → `dispatch-incidents`, Medic → `record-care`, Hospital → `manage-hospitals`, Org Admin → `manage-fleet`); add them when those portals are built. Until then they are unassigned, reachable only via `Gate::before` for super admin.
- Re-seed with `php artisan migrate:fresh --seed` (dev); `RolePermissionSeeder` uses `updateOrCreate` + `sync`, so the LGU role's perms are replaced cleanly.

---

## Detailed Breakdown

### Role cleanup — decision to confirm at build time
- Keep `view-incidents` (read-only) on the LGU: recommended to keep, since it is oversight, not operation.
- `manage-fleet` is borderline (fleet registry is arguably org-admin's job): recommended to remove from LGU now, and give it to Org Admin when that portal lands.

### Verification — Tests
- Update/add tests confirming: LGU logs in and is redirected to `dashboard` (has `access-admin`); LGU can `GET /admin/config` (200) and `PUT` a valid timeout; LGU is now 403 on `GET /admin/dispatch`, `/admin/hospitals`, and a care route (proves role cleanup).
- Confirm super admin still reaches everything; citizen redirect is covered in the Citizen plan.
- Confirm the existing `DispatchTest` still passes, and that the timeout it reads matches what City Settings writes.

### Verification — Manual
- Run `php artisan migrate:fresh --seed`; log in as `lgu@rescue.test / Password123!` and confirm landing on dashboard with governance cards showing.
- Open City Settings, change the timeout, confirm it persists and dispatch uses it.
- Confirm Dispatch/Hospitals/Care are not in the sidebar and return 403 if hit directly.

### Verification — Regression
- Confirm the super admin sidebar is unchanged (still sees all).
- Confirm no view references a removed permission for the LGU that would break layout.

---

## Files
**New:** `app/Support/PortalRouter.php`, `app/Http/Controllers/Admin/ConfigController.php`, `resources/views/admin/config/edit.blade.php` (+ optional `app/Http/Requests/Config/UpdateConfigRequest.php`).
**Edit:** `LoginController.php`, `VerifyEmailController.php`, `DashboardController.php`, `resources/views/dashboard.blade.php`, `resources/views/layout/admin/partials/_sidebar.blade.php`, `routes/web.php`, `database/seeders/RolePermissionSeeder.php`.