# Portal Fixes — SA / LGU / Citizen Process Correctness

> Remediation plan from the portal audit. Covers what to **add / change / remove** to make the whole process flow correct and docs-faithful.
> Two layers per item: **Non-technical** (what & why, plain language) and **Technical** (exact files/changes).
> Status: all three portals are implemented; this fixes one real bug, the flaky tests it hides, and two wiring gaps. No core logic is wrong.

---

## Summary table

| # | Severity | Area | What | Action |
|---|---|---|---|---|
| 1 | 🔴 Bug | Super Admin | Audit-log viewer crashes (wrong table names) | **Change** |
| 2 | 🔴 Bug | Tests | Suite is flaky / hides bug #1 | **Add** test + re-run |
| 3 | 🟡 Gap | Citizen | Lands on public form, not citizen home | **Change** |
| 4 | 🟡 Check | Citizen | Medical info has no storage column | **Add** (if missing) |
| 5 | 🟢 Cosmetic | Seeder | Stale comment contradicts the code | **Change** |

---

## Fix 1 — Super Admin Audit-log viewer (🔴 broken)

### Non-technical
The Super Admin's "Audit & Logs" screen — the read-only trail of who did what — **crashes with an error** when opened. The page is looking for a log table by the wrong name, so it finds nothing and fails. Once corrected, the Super Admin can open the logs and browse them normally. This is the only broken screen in the three portals; everything else on the SA side (user management, archive registry) works.

### Technical
- **File:** `app/Http/Controllers/Admin/AuditController.php`
- **Problem:** queries `tbl_audit_logs` and `tbl_users` (3 references). Those tables don't exist in the running app — the legacy `tbl_`-prefixed names only live in the reference dump `database/schema/rescue_platform.sql`, which Laravel never runs. The real migration tables are `audit_logs` and `users`, and `App\Services\AuditLog::record()` writes to `audit_logs`.
- **Change (rename, no logic change):**
  - `DB::table('tbl_audit_logs as a')` → `DB::table('audit_logs as a')`
  - `->leftJoin('tbl_users as u', ...)` → `->leftJoin('users as u', ...)`
  - `DB::table('tbl_audit_logs')->distinct()...` → `DB::table('audit_logs')->distinct()...`
- **Nothing to add or remove** — three string corrections.

---

## Fix 2 — Flaky test suite (🔴 hides bug #1)

### Non-technical
When all tests run together, they reported "all passing" — but the Audit-log screen test actually **fails when run on its own**. That means the green result couldn't be trusted; a real bug slipped through. We add a small, focused test for the audit screen so this exact failure can never hide again, then re-run the tests both all-together and one file at a time to confirm the results are now consistent.

### Technical
- **Root cause:** Fix 1 (the audit route 500s). Correcting the table names removes the failure.
- **Add:** an audit-viewer smoke test (e.g. in `tests/Feature/SuperAdminPortalTest.php`):
  - seed one row into `audit_logs` (or call `AuditLog::record(...)` while acting as a user),
  - `actingAs(superAdmin)->get(route('admin.audit.index'))->assertOk()->assertSee(<the action>)`.
- **Verify determinism after the fix:**
  - `php artisan test` (full) — green.
  - `php artisan test --filter SuperAdminPortalTest` and `--filter LguPortalTest` **individually** — green.
  - If any flakiness remains after Fix 1, investigate shared state / `RefreshDatabase` ordering separately (not expected — bug #1 is the cause).

---

## Fix 3 — Citizen lands on the wrong page (🟡 wiring gap)

### Non-technical
A logged-in citizen is currently sent to the **public request form** after login, instead of their own **citizen home** (which already exists, with profile, medical info, and request history). So the citizen's portal home is built but never shown as their landing page. The fix points the post-login redirect at the citizen home, so citizens land where they should.

### Technical
- **File:** `app/Support/PortalRouter.php`
- **Change:** in `homeRouteFor()`, the non-console branch returns `'request.create'`; change it to `'citizen.home'`.
  - `return $user->isSuperAdmin() || $user->hasPermission('access-admin') ? 'dashboard' : 'citizen.home';`
  - The citizen routes/controller/views already exist (`routes/web.php` `citizen.*`, `App\Http\Controllers\Citizen\CitizenController`, `resources/views/citizen/*`), and `CitizenController::home()` already bounces a misrouted staffer back to `/dashboard`, so this is safe.
- **Update the test:** `LguPortalTest::test_portal_router_routes_by_role` currently asserts `'request.create'` for the citizen — change the expected value to `'citizen.home'`.
- **Nothing to add or remove.**

---

## Fix 4 — Citizen medical info storage (🟡 verify, add only if missing)

### Non-technical
The citizen's "medical info" screen lets a registered user save details (blood type, allergies, conditions) so responders know them in advance. We need to confirm there's actually a place in the database to store this. If there isn't, we add one. Then a quick test confirms saved info can be read back.

### Technical
- **File:** `app/Http/Controllers/Citizen/CitizenController.php` — `updateMedical()` does `auth()->user()->update(['medical_info' => $data])` (an array).
- **Verify:** does the `users` table have a `medical_info` column, and does `App\Models\User` cast it to `array`/`json`?
  - **If present:** no change.
  - **If missing — Add:**
    - migration: `$table->json('medical_info')->nullable();` on `users` (or a separate `citizen_medical_profiles` table keyed by `user_id` if you prefer normalization — column is simpler for the few fields here).
    - `User::casts()`: add `'medical_info' => 'array'`.
    - `User::$fillable`: add `'medical_info'`.
- **Add test:** registered citizen saves medical info via `citizen.medical.update`, then `citizen.medical` shows it back (proves persistence + isolation to own user).

---

## Fix 5 — Stale seeder comment (🟢 cosmetic)

### Non-technical
A code comment still claims the Super Admin can reach every screen through a special bypass. That bypass was removed when the Super Admin was constrained to oversight-only, so the comment now describes behavior that no longer exists. Correct the comment so it matches reality. No behavior changes.

### Technical
- **File:** `database/seeders/RolePermissionSeeder.php` (around line 68, in the LGU block).
- **Change:** the comment saying operational perms are "reachable only via `Gate::before` for super admin" is false — the `isSuperAdmin()` wildcard was removed from `User::hasPermission()`. Reword to: those operational permissions are **unassigned** until field/org roles are built, and even the super admin no longer reaches them (oversight-only).

---

## What is already correct (no change needed)

These were audited and verified faithful to the documentation — **do not touch**:

- **LGU (governance)** — account approvals, organization approvals + per-document validation, organization registry, City Settings (`dss_timeout_seconds`, validated 5–600, persists and is read by dispatch), reports, safety. Correctly **403s** on dispatch/care/hospital. Matches `docs/ROADMAP/PROCESS AND FLOW.md §7`.
- **Super Admin (oversight-only)** — wildcard removed, role narrowed, **403s** on operational routes, user management + archive registry work. Matches `docs/DOCUMENTS/DOCUMENT ARCHITECTURE.md` + `PROCESS AND FLOW.md §8`. *(Only the audit viewer, Fix 1, was broken.)*
- **Citizen (consumer)** — guest request + unified tracking + call driver + soft-cancel (held for verification) all correct; registered home/profile/medical/history built and scoped to the logged-in user. Matches `docs/ROADMAP/PROCESS AND FLOW.md §2` and `docs/MIGRATION/02_PROCESS_AND_FLOW.md §1`. *(Only the landing target, Fix 3, and the medical column, Fix 4, need attention.)*

---

## Order of work & final verification

1. Fix 1 (audit table names) — unblocks the suite.
2. Fix 3 (citizen landing) + update its router test.
3. Fix 4 (medical column, if missing) + test.
4. Fix 2 (add audit smoke test).
5. Fix 5 (comment).

**Then verify end-to-end:**
- `php artisan test` (full) **and** `--filter SuperAdminPortalTest`, `--filter LguPortalTest`, citizen tests **individually** → all green, no 500s.
- Manual (`php artisan migrate:fresh --seed`):
  - `superadmin@rescue.test` → Audit viewer renders (not 500), Archive restore works.
  - `lgu@rescue.test` → City Settings saves; 403 on dispatch/hospital/care.
  - `citizen@rescue.test` → lands on **citizen home**; edit profile + medical; see only own history; 403 on any `/admin/*`.
- Confirm each role lands on its correct home and cannot reach another role's operational screens.

*This document is a plan. No code has been changed by writing it.*
