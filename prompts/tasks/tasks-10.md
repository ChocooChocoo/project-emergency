# Objective
## Citizen / User Portal — Dedicated Landing and Account Area for Registered Citizens

---

## Context Sources
If the context above is not enough, it should be checked at:
- `prompts\plans\citizen-portal-plan-technical.md`
- `prompts\plans\citizen-portal-plan.md`

---

## Description
Give registered citizens their own landing and account area, so a logged-in citizen never hits the staff console or gets a 403. Today a logged-in citizen is redirected to `route('dashboard')` and then blocked, because `/dashboard` needs `can.perm:access-admin` and citizens have no permissions. The public guest flow (`/request`, `/request/{code}`) is fully built and correct, and stays as-is. This plan adds the citizen-side portal: a proper `citizen.home` landing and the registered-only account screens — Profile, Medical info, and Incident history. It depends on the LGU plan's `PortalRouter` helper to send citizens to `citizen.home` instead of `dashboard`; if built before the LGU plan, `PortalRouter` is added here instead.

---

## Primary Objective
Deliver a citizen landing (`citizen.home`) and the registered-only account screens (Profile, Medical info, Incident history), scoped strictly to the logged-in citizen's own data, while leaving the existing guest request and tracking flow untouched.

---

## Secondary Objectives
- Citizen landing: a logged-in citizen's home is the request/tracking experience, not the staff admin console.
- Registered-citizen account screens: Profile, Medical info, and Incident history, each scoped to `auth()->id()`.
- Leave the guest flow as-is: One-Tap and Detailed requests, tracking, call, soft-cancel, strikes, quota, and grouping remain untouched.
- Note but do not build: Non-Emergency and Scheduled request types, Google OAuth, and guardian-consent linkage for minors.

---

## Supporting Tasks

### Citizen landing (`citizen.home`)
- Add a route inside the `['auth','account.active']` group, without any `can.perm` (citizens have no permission): `GET /home` → `CitizenController@home` (name `citizen.home`).
- Guard the page for citizens: console users should still go to `/dashboard`; reuse `PortalRouter::homeRouteFor()` so the redirect picks the right home, and optionally redirect to `dashboard` from `CitizenController@home` if `hasPermission('access-admin')` so a misrouted staffer doesn't see the citizen page.
- Wire `PortalRouter::homeRouteFor()` to return `'citizen.home'` for users without `access-admin`, replacing the `'request.create'` placeholder noted in the LGU plan.
- Build the view `resources/views/citizen/home.blade.php` extending a light citizen layout (`layout/citizen/app.blade.php`, which can start as a thin copy of the auth layout: header + `@yield('content')` + feedback modal, no admin sidebar), with cards/links for Request an ambulance (`request.create`), Track a request (`request.track` by code or their latest open one), and My account (profile / medical / history).

### Registered-citizen account screens
- Build a new `CitizenController` (or a small `Citizen/` namespace) under the same no-permission `auth` group, with every screen scoped to `auth()->id()` so a citizen only ever sees their own data.
- **Profile:** `GET /home/profile` → `profile()` (show form), `PUT /home/profile` → `updateProfile()`; validate name/phone/etc.; update the `users` row for self only; record `AuditLog::record('citizen.profile_updated', User::class, $id)`. View: `resources/views/citizen/profile.blade.php`.
- **Medical info:** `GET/PUT /home/medical` → `medical()/updateMedical()` (`updateOrCreate` on `user_id`); check for an existing model/table first, since the schema's patient/medical tables are per-incident, so a citizen's standing medical info likely needs a small `citizen_medical_profiles` table or a reused `users` JSON column. View: `citizen/medical.blade.php`.
- **Incident history:** `GET /home/history` → `history()`: `Incident::where('user_id', auth()->id())->latest()->paginate()`, a read-only datatable (CLAUDE.md datatable pattern) where each row links to that request's public `track` page, with no schema change. View: `citizen/history.blade.php`.

### Things to leave as-is
- Leave the guest flow (`/request*`) untouched, since it already correctly covers One-Tap and Detailed requests, tracking, call, soft-cancel, strikes, quota, and grouping.
- Do not build Non-Emergency and Scheduled request types, Google OAuth, or guardian-consent for minors; a one-line "planned" note in the UI is acceptable, with no logic behind it.

---

## Detailed Breakdown

### Medical info — table-vs-column decision to confirm at build time
- A citizen's standing medical info needs either a new `citizen_medical_profiles` table or a reused `users` JSON column; this is not yet decided and should be confirmed when building.
- If a table is needed, add a migration `create_citizen_medical_profiles` with columns `user_id`, `blood_type`, `allergies`, `conditions`, `notes`, and timestamps.
- Default to a JSON column if the fields stay few; medical info is the only possible new table, since everything else reuses `users` and `incidents`.

### Verification — Tests
- Confirm a citizen logging in is redirected to `citizen.home` (200), not 403.
- Confirm a citizen's `GET /home/history` shows only their own incidents (seed one for them and one for another user, then assert isolation).
- Confirm a citizen is still 403 on `/dashboard` and any `/admin/*` route, since they have no permissions, confirming separation holds.
- Confirm the guest `/request` flow tests still pass (regression).

### Verification — Manual
- Run `php artisan migrate:fresh --seed`; log in as `citizen@rescue.test / Password123!` and confirm landing on citizen home with no admin sidebar and no 403.
- Submit a request while logged in and confirm it appears in My History.
- Open Profile and Medical, edit, and confirm changes persist.

### Verification — Cross-check
- With the LGU plan also applied, confirm: super admin → dashboard, LGU → dashboard (governance), citizen → citizen.home — three roles, three correct homes.

---

## Files
**New:** `app/Http/Controllers/Citizen/CitizenController.php` (or `HomeController` + `AccountController`), `resources/views/citizen/{home,profile,medical,history}.blade.php`, `resources/views/layout/citizen/app.blade.php`; maybe `app/Models/CitizenMedicalProfile.php` plus a migration (only if medical info needs its own table).
**Edit:** `routes/web.php` (citizen group), `app/Support/PortalRouter.php` (return `citizen.home`), and — if the LGU plan isn't built first — `LoginController.php` and `VerifyEmailController.php` to use `PortalRouter`.