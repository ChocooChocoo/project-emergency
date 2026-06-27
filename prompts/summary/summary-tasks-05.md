# Summary — Tasks-05: S11 Reports + Hardening

> Final roadmap phase. Per `docs/ROADMAP/DEVELOPMENT BUILD GUIDE.md`, S11 is exactly four items:
> (1) LGU performance reports, (2) Notifications module, (3) Security pass against
> `SECURITY IMPROVEMENTS.md`, (4) status/terminology normalization (R12) + cleanup.
> **Done when:** the system is presentable, consistent, and passes the security checklist.
>
> Scope confirmed with user before build: **roadmap S11 exactly** (no extra audit/config/archive
> UIs) with **all four report metric groups** (response-time, volume/outcomes, fleet, safety).

---

# Technical Information

## Decisions taken (confirmed before build)

| Concern | Choice | Rationale |
|---------|--------|-----------|
| Reports data source | Read-only aggregation over **existing** tables | All tables (`incidents`, `dispatch_assignments`, `device_tokens`, `hospital_endorsements`, `notifications`, `audit_logs`) already shipped in S0 migrations. S11 is a read + hardening layer — **no new migration**. |
| Median computation | PHP-side on the result collection | Dataset is small; `// ponytail: push to SQL percentile if rows grow`. |
| Date range UI | Native `<input type="date">`, GET form | No JS date-picker dependency; Laravel parses the strings. |
| Notifications | DB-table model + tiny `Notifier::send()` writer | Mirrors `AuditLog::record`; `// ponytail: swap to Laravel's notification system if mail/SMS/push channels are ever needed`. |
| Notification access | Personal/owner-scoped, **no permission** | Notifications belong to the signed-in user; gated by `account.active` only. |
| Security pass | Audit-and-document, fix only real gaps | The checklist was already enforced per-phase; the pass is verification, not a rewrite. |
| R12 normalization | Conservative — fix only real drift | Stored statuses are already lowercase snake_case; display already uniform. No churn. |

## Schema

**No migrations added.** All consumed tables pre-existed:
- `notifications` (id, user_id, type, title, message, data_json, is_read, read_at, created_at) — `..._000070_create_system_tables.php`
- `incidents`, `dispatch_assignments` milestone timestamps, `request_outcome_logs`, `driver_completion_reports` — `..._000050_create_incident_dispatch_tables.php`
- `device_tokens`, `hospital_endorsements`, `audit_logs` — existing.

## Part 1 — Reports module (LGU performance metrics)

- **Controller:** `app/Http/Controllers/Admin/ReportController.php` — single `index(Request)`. Optional
  `from`/`to` window (default last 30 days, swaps if reversed). Four private aggregators, all builder/Eloquent
  (bound params, no raw concat):
  - `responseKpis()` — avg + median minutes across `dispatch_assignments` legs (assign→accept,
    assign→on-scene, on-scene→hospital, assign→hospital total). Negative clock-skew diffs dropped.
  - `volumeOutcomes()` — incident counts by status / request_type / severity; completed, resolved-on-scene,
    cancelled, flagged-false-alarm.
  - `fleetUtilization()` — dispatches per ambulance (top 15) + live ambulance status snapshot.
  - `safetyAbuse()` — flagged incidents, blocked devices, devices-with-strikes, total strikes, hospital
    handoff accepted/declined + acceptance rate.
- **View:** `resources/views/admin/reports/index.blade.php` — headline KPI cards (reuses the dashboard
  card markup), then summary tables/lists. Light badges `bg-{color}-lt` per CLAUDE.md. Print button.
- **Route:** `can.perm:view-reports` group → `GET /admin/reports` → `admin.reports.index`.
- **Permission:** new `view-reports` added to `RolePermissionSeeder` + granted to `platform_executive`.
- **Sidebar:** `@can('view-reports')` nav item (`ti-report-analytics`).

## Part 2 — Notifications module

- **Model:** `app/Models/Notification.php` — `$fillable`, `data_json`→array cast, `const UPDATED_AT = null`
  (table has no `updated_at`).
- **Service:** `app/Services/Notifier.php` — static `send(userId, title, message, type='system', data=null)`.
- **Controller:** `app/Http/Controllers/Admin/NotificationController.php` — `index()` (own rows, paginated),
  `markRead()` (403 unless owner), `markAllRead()`.
- **View:** `resources/views/admin/notifications/index.blade.php` — list with unread highlight + per-row /
  bulk "mark read".
- **Navbar:** `_navbar.blade.php` bell rewired from static demo markup to the current user's real unread
  count (solid red dot — the CLAUDE.md indicator-dot exception) + 5 most recent, queried inline so every
  admin page has it without per-controller code.
- **Routes:** `GET /admin/notifications`, `PATCH .../read-all`, `PATCH .../{notification}/read` under
  `['auth','account.active']`.
- **Proof call sites:** `ApprovalController::approve/reject` now call `Notifier::send` so the affected user
  gets an in-app notice.

## Part 3 — Security pass (checklist results)

Ran `SECURITY IMPROVEMENTS.md §3` across every module. Result: **already compliant — no fixes required.**

| Checklist item | Result |
|----------------|--------|
| Form has `@csrf` | ✅ 30/30 POST-form files carry `@csrf` (grep: matched counts). |
| Write request has Form Request / validation | ✅ Every `store/update/...` either type-hints a Form Request or calls `$request->validate([...])`. Action-only state changes (`flag`, `block`, `reassign`, `advance`, `toggleAd`) take no user fields — gated by permission + route-model binding. |
| Action has a permission check | ✅ Every admin route sits behind `can.perm:*`; new S11 report route gated by `view-reports`. |
| Query tenant/owner-scoped | ✅ New notification queries scoped by `auth()->id()`; report queries are LGU-wide by design (platform executive). |
| No raw SQL with user input | ✅ Aggregations use builder + `selectRaw` with constant column names only. |
| Output escaped in Blade | ✅ Zero `{!! !!}` in the codebase. |
| Uploads validated + stored privately | ✅ Unchanged from S4 (org documents). |
| Sensitive route rate-limited | ✅ Auth/intake/location throttled; no new sensitive public route added. |

Deployment note (not a code change): set `APP_DEBUG=false` and HTTPS in production per §2.4/§2.6.

## Part 4 — Status casing / terminology normalization (R12)

Audited model status constants and Blade badge maps. **No drift found** — stored values are already
lowercase snake_case everywhere, and every view renders them via `ucwords(str_replace('_',' ',$s))`.
R12 is satisfied; no destructive change made (conservative per plan).

## Tests added

| File | Coverage |
|------|----------|
| `tests/Feature/ReportsTest.php` | Report page loads with computed KPI/volume sections for `platform_executive`; 403 for a role without `view-reports`. |
| `tests/Feature/NotificationTest.php` | `Notifier::send` writes a row; user sees only own notifications; `markRead` flips `is_read`/`read_at`; cannot mark another user's notification (403). |

**Verification run:** `php artisan test` → **42 passed, 145 assertions**. `db:seed --class=RolePermissionSeeder`
seeds `view-reports`. `./vendor/bin/pint` clean.

## Patterns followed (unchanged from prior phases)

Permission-gated route group → `Admin\` controller → Blade under `layout/admin/app.blade.php` →
`@can` sidebar link → seeder permission + `platform_executive` grant. Dashboard stat-card markup,
light badges, `AuditLog`-style service writer all reused, not reinvented.

## Deferred (named, with owner)

| Item | Why | Lands |
|------|-----|-------|
| Audit-log / system-config / archive-registry **UIs** | Permissions seeded (`view-audit-logs`, `manage-config`, `manage-archive`) but out of confirmed S11 scope | Future admin-console phase |
| CSV/PDF report export | View + print covers the demo | When stakeholders ask |
| Charts on reports | Tables/cards are sufficient and dependency-free | Optional polish |
| Broadcast/real-time notifications | DB-table + page refresh covers the flow | Reverb / mobile phase |

---

# Non-Technical Information

## What S11 delivered

S11 is the **final polish-and-protect phase**. The platform already did everything operationally — taking
911 requests, dispatching ambulances, tracking them, recording care, handing patients to hospitals, and
curbing misuse. S11 makes it **presentable and trustworthy**: a reports screen for the city, in-app
notifications, and a confirmed clean bill of health on security.

## Reports — performance at a glance

The LGU / City Health Office now has a **Reports** page. Pick a date range (defaults to the last 30 days)
and see, on one screen:

- **How fast we respond** — average and middle (median) minutes from dispatch to reaching the patient and
  the hospital.
- **What happened** — how many requests came in, how many were completed, resolved on the spot, cancelled,
  or flagged as false alarms, broken down by status, type, and severity.
- **How busy the fleet is** — which ambulances ran the most calls, and how many units are available right now.
- **Safety** — flagged incidents, blocked abusive devices, and how often hospitals accepted patient handoffs.

It reads existing records — nothing new is collected — and has a Print button for meetings and reports.

## Notifications — staying informed

The bell icon in the top bar is now real. Each user sees their **own** notifications with an unread count,
can open the full list, and mark items read (one at a time or all at once). As a working example, when an
admin approves or rejects an account, that person receives an in-app notice automatically. Other parts of
the system can post notifications the same way with a single line of code.

## Security — a clean pass

We walked the whole system against a plain-language security checklist (the kind expected of a capstone —
common, sensible protections, not enterprise overkill). Every form is protected against forgery, every
data-entry point is validated, every admin action is permission-checked, and no page exposes unsafe output.
**Nothing needed fixing** — the protections were built in from the start. The only remaining items are
deployment switches (turn off debug mode, use HTTPS), noted for go-live.

## Consistency — terminology check

We checked that status labels read consistently across the app (e.g. "On scene", "Arrived at hospital").
They already did, so no changes were needed.

## What was intentionally left for later

Screens for browsing raw audit logs, editing system configuration, and the archive registry exist as
permissions but weren't part of this phase's agreed scope. Exporting reports to PDF/Excel, adding charts,
and live push notifications are nice-to-haves deferred until asked for. The system as it stands is
complete, consistent, and passes its security checklist — the definition of done for S11.
