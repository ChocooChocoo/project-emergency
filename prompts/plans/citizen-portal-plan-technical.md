# Citizen / User Portal — Technical Implementation Plan

> Companion to `citizen-portal-plan.md` (non-technical, same folder). This is the build-ready spec.
> Goal: give registered citizens their own landing + account area, so a logged-in citizen never hits the staff console/403. Guest request + tracking already work and stay as-is.
> Stack rules: follow `CLAUDE.md` (Tabler UI, `@csrf`/validation, `AuditLog` for writes, feedback modal for confirms).

---

## Context

Today a logged-in citizen is redirected to `route('dashboard')` (by both `LoginController::authenticate` and `VerifyEmailController::verify`) and then 403s, because `/dashboard` needs `can.perm:access-admin` and citizens have no permissions. The public guest flow (`/request`, `/request/{code}`) is fully built and correct. This plan adds the **citizen-side portal**: a proper landing and the registered-only account screens (Profile, Medical info, Incident history) from the non-technical plan §4.5 and §8.

**Depends on** the LGU plan's `PortalRouter` helper (the role-aware redirect) — it sends citizens to `route('citizen.home')` instead of `dashboard`. If built before the LGU plan, add `PortalRouter` here instead.

**Out of scope (▲TBD per docs):** Non-Emergency & Scheduled request types, Google OAuth, guardian-consent linkage for minors. Note them; don't build.

---

## Verified facts (code-traced)

- Citizen accounts: `account_type='citizen'`, `account_status='active'` after OTP, **no role/permissions** (`RegisterController::store` `app/Http/Controllers/Auth/RegisterController.php:25`; `VerifyEmailController::verify` `:38-49`). `isSuperAdmin()=false`, `permissionCodes()` empty → fails `access-admin`.
- Both redirects hardcode `dashboard`: `LoginController.php:52`, `VerifyEmailController.php:49`.
- Public intake already built: `Intake/RequestIntakeController` (`create/store/track/status/cancel`) + views `resources/views/request/create.blade.php`, `track.blade.php`. Incident model has `user_id` for registered requesters, so **incident history is just a scoped query** — no schema change.
- Auth layout exists (`layout/auth/app.blade.php`, feedback modal already wired). No citizen layout yet.

---

## 1. Citizen landing (`citizen.home`)

A logged-in citizen needs a home that is **not** the admin console.

- **Route** (`routes/web.php`): inside the `['auth','account.active']` group but **without** any `can.perm` (citizens have no permission), add:
  - `GET /home` → `CitizenController@home` (name `citizen.home`).
- **Guard:** the page is for citizens; console users should still go to `/dashboard`. Reuse `PortalRouter::homeRouteFor()` so the redirect picks the right home. Optionally, in `CitizenController@home`, if `hasPermission('access-admin')` redirect to `dashboard` (so a misrouted staffer doesn't see the citizen page).
- **Redirect wiring:** `PortalRouter::homeRouteFor()` returns `'citizen.home'` for users without `access-admin` (replaces the `'request.create'` placeholder noted in the LGU plan).
- **View:** `resources/views/citizen/home.blade.php` extending a light citizen layout (`layout/citizen/app.blade.php` — can start as a thin copy of the auth layout: header + `@yield('content')` + feedback modal, no admin sidebar). Cards/links: **Request an ambulance** (`request.create`), **Track a request** (`request.track` by code / their latest open one), and **My account** (profile / medical / history below).

```text
citizen logs in / verifies email > PortalRouter > citizen.home
   home cards: Request ambulance | Track request | My account (profile, medical, history)
```

---

## 2. Registered-citizen account screens

All scoped to `auth()->id()` — a citizen only ever sees their own data. New `CitizenController` (or split into a small `Citizen/` namespace) under the same no-permission `auth` group:

- **Profile** — `GET /home/profile` → `profile()` (show form), `PUT /home/profile` → `updateProfile()`. Validate name/phone/etc.; update the `users` row (only self); `AuditLog::record('citizen.profile_updated', User::class, $id)`. View `resources/views/citizen/profile.blade.php`.
- **Medical info** — stored for responders. Check for an existing model/table first; the schema has patient/medical tables (`patients`, etc.) but those are per-incident. A citizen's *standing* medical info likely needs a small `citizen_medical_profiles` (or reuse a `users` JSON column) — **confirm at build time**; if a table is needed, add a migration `create_citizen_medical_profiles` (`user_id`, `blood_type`, `allergies`, `conditions`, `notes`, timestamps). `GET/PUT /home/medical` → `medical()/updateMedical()` (`updateOrCreate` on `user_id`). View `citizen/medical.blade.php`.
- **Incident history** — `GET /home/history` → `history()`: `Incident::where('user_id', auth()->id())->latest()->paginate()`. Read-only datatable (CLAUDE.md datatable pattern), each row links to that request's public `track` page. View `citizen/history.blade.php`. **No schema change** — pure scoped query.

*ponytail: medical info is the only possible new table; everything else reuses `users` + `incidents`. Decide table-vs-JSON-column when building, default to a column if fields stay few.*

---

## 3. Things to leave as-is

- **Guest flow** (`/request*`) — untouched; already correct, already covers One-Tap + Detailed, tracking, call, soft-cancel, strikes, quota, grouping.
- **▲TBD, do not build:** Non-Emergency & Scheduled request types, Google OAuth, guardian-consent for minors. Add a one-line "planned" note in the UI if helpful, no logic.

---

## Files

**New:** `app/Http/Controllers/Citizen/CitizenController.php` (or `HomeController`+`AccountController`), `resources/views/citizen/{home,profile,medical,history}.blade.php`, `resources/views/layout/citizen/app.blade.php`; **maybe** `app/Models/CitizenMedicalProfile.php` + migration (only if medical info needs its own table).
**Edit:** `routes/web.php` (citizen group), `app/Support/PortalRouter.php` (return `citizen.home`), and — if the LGU plan isn't built first — `LoginController.php` + `VerifyEmailController.php` to use `PortalRouter`.

---

## Verification

1. **Tests** (`php artisan test`):
   - Citizen logs in → redirected to `citizen.home` (200), **not** 403.
   - Citizen `GET /home/history` shows only their own incidents (seed one for them + one for another user; assert isolation).
   - Citizen still **403** on `/dashboard` and any `/admin/*` (they have no permissions — confirms separation holds).
   - Guest `/request` flow tests still pass (regression).
2. **Manual:** `php artisan migrate:fresh --seed`; log in `citizen@rescue.test / Password123!` → lands on citizen home (no admin sidebar, no 403); submit a request while logged in → appears in My History; open Profile/Medical, edit, confirm persists.
3. **Cross-check:** with the LGU plan also applied, super admin → dashboard, LGU → dashboard (governance), citizen → citizen.home — three roles, three correct homes.
