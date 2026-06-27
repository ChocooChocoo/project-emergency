# Summary — Tasks-04: S7 DSS+Dispatch, S8 Driver+Tracking, S9 Medical+Handoff, S10 Anti-abuse+Sustainability

This phase continues `summary-tasks-03.md` (S0–S6). All four surfaces are built **inside the
existing admin console** (`layout/admin/app.blade.php`), gated by new `can.perm:<code>` middleware
and `@can` sidebar entries — the same pattern S3–S6 used. **No new Composer dependencies** were
added; realtime is polling, not Reverb (a deliberate, documented choice).

**Verification:** `36 tests, 36 pass` (18 prior + 18 new); `pint` clean.

---

# Technical Information

## Decisions taken (confirmed with user before build)

| Concern | Choice | Rationale |
|---|---|---|
| Realtime (S8) | **Polling** — driver POSTs location, track page `fetch()`-polls JSON every 10s | No new deps/daemon; matches the prototype-pragmatic pattern. Reverb is the upgrade path. |
| Surfaces | **Single admin console**, permission-gated | Consistent with S3–S6; no separate driver layout this phase. |
| DSS (S7) | **Synchronous scoring** — `DssService::rank()`, dispatcher picks | No queued auto-throw/countdown/reassign jobs; demoable + testable. |

## Schema

No new migration shipped. The plan assumed `device_tokens` (R4) was missing, but it was already
present in `2026_06_26_000010_create_identity_access_tables.php` (with an extra `blocked_at`
column) — so the redundant migration was **deleted** and the existing table reused. Every other
table this phase needs (`dispatch_assignments`, `driver_duty_states`, `ambulance_locations`,
`hospitals`, `hospital_endorsements`, `handoff_summaries`, `patients`, `vitals_entries`,
`treatment_records`, `prehospital_notes`, `ad_placements`, `system_configurations`) was already
migrated in `…000040/050/060/070`.

## S7 — DSS + Dispatch

- **`app/Services/DssService.php`** — `rank(Incident): Collection`. Pulls `available` + serviceable
  ambulances from active/approved orgs, scores each by **proximity (Haversine km) + severity-weighted
  tier/equipment bonus**, returns ranked rows (`score`, `distance_km`, `eta_minutes`, `dss_rank`).
  `ponytail:` upgrade path = queued AutomaticThrowJob + countdown.
- **`DispatchController`** — `index` (queue / scheduled tabs + active-assignment board),
  `show` (incident + ranked-unit table), `store` (assign), `reassign` (manual release).
- **`store`** enforces **R7 org-consistency**: the assignment's `organization_id` is taken from the
  **ambulance**, never user input, so it can't drift. In one `DB::transaction`: creates the
  `dispatch_assignment` (`response_deadline_at = now() + dss_timeout_seconds`), flips incident →
  `dispatched` + sets its org, flips ambulance → `dispatched`, writes a public `IncidentUpdate`,
  `AuditLog::record('dispatch.assigned')`.
- **`DispatchAssignment` model** carries the `FLOW` array and `MILESTONES` map (status → timestamp
  column → mirrored incident status) used by S8.
- DSS countdown is read from `system_configurations` (`dss_timeout_seconds`, default 60) — LGU-tunable.
- **Form Request:** `Dispatch/StoreAssignmentRequest`. **Views:** `admin/dispatch/{index,show}`.
- **Perm:** `dispatch-incidents`.

## S8 — Driver + Live Tracking (polling)

- **`DriverController`** — `duty` / `updateDuty` (`driver_duty_states` via `updateOrCreate`),
  `assignment` (detail), `advance` (state machine), `pushLocation` (JSON).
- **`advance`** steps one stage along `DispatchAssignment::FLOW`
  (`assigned→accepted→en_route→arrived_on_scene→transporting→arrived_at_hospital→completed`),
  stamps the matching `*_at` column, mirrors the milestone onto the incident, writes an
  `IncidentUpdate`, and **frees the ambulance on `completed`**. Illegal jumps are impossible
  (it only ever moves to `nextStatus()`); `authorizeDriver()` restricts to the assigned driver or a
  dispatcher.
- **`pushLocation`** inserts an `ambulance_locations` row and updates the ambulance's
  `last_lat/last_lng/last_seen_at` (throttle:60,1).
- **Public tracking:** `RequestIntakeController::status(code)` returns JSON (status, ETA, assignment
  plate/tier/driver/last-location). `request/track.blade.php` polls it every 10s, reveals the live
  unit block, and shows a native `tel:` call button. `ponytail: polling; Reverb is the upgrade path.`
- **Models:** `DriverDutyState`, `AmbulanceLocation` (`$timestamps=false`). **Views:**
  `admin/driver/{duty,assignment}` (status stepper + GPS-push demo button via `geo:` deep-link).
- **Perm:** `drive-unit` (field role — deliberately **not** granted to `platform_executive`).

## S9 — Medical + Hospital Handoff

- **`CareController`** (`record-care`) — `show`, `storeVitals` / `storeTreatment` / `storeNote`
  (inline-form nested POSTs mirroring `AmbulanceController::storeFuelLog`), `upsertPatient`
  (`updateOrCreate` on `incident_id`), `resolveOnScene` (no-transport completion path).
- **`HospitalController`** (`manage-hospitals`) — hospital CRUD-lite (`index/create/store/show`),
  plus the handoff state machine: `endorse` (creates `hospital_endorsement` pending), `respond`
  (accept/decline), `confirmHandoff` (writes `handoff_summaries`, completes incident, frees unit).
- **Models:** `Hospital`, `HospitalEndorsement`, `HandoffSummary`, `Patient`, `VitalsEntry`,
  `TreatmentRecord`, `PrehospitalNote` (`$timestamps=false` where the table has only a single
  timestamp, matching `IncidentUpdate`).
- **Form Request:** `Medical/StoreVitalsRequest` (clinical range bounds — BP/pulse/SpO₂/GCS/etc.).
- **Views:** `admin/care/show` (patient panel, vitals datatable + modal, treatments, notes,
  endorsement panel, resolve-on-scene), `admin/hospitals/{index,create,show}` (registry + endorsement
  accept/decline/confirm actions). Incident detail page gained **Dispatch** + **Care** buttons.

## S10 — Anti-abuse, Scheduling, Sustainability

- **`StrikeService`** (R4) — `recordFalseAlarm(uuid)` (rolling 30-day counter, blocks at 3),
  `isBlocked(uuid)`, `setBlocked()`. `ponytail:` single-counter approximation of the sliding window.
- **Intake wiring** — `RequestIntakeController::store` rejects blocked devices up front; a persistent
  `device_uuid` cookie (1 year, like `guest_key`) ties strikes to the device.
- **Cancellation = held pending (anti-abuse)** — `RequestIntakeController::cancel` never hard-cancels;
  it returns the incident to `pending` and logs `care_status='needs_field_verification'`.
- **`SafetyController`** (`manage-safety`) — `index` (device strikes + flagged incidents),
  `flag` / `block` / `unblock`; **ads:** `ads` (toggle `ad_placements`).
- **Scheduling** — surfaced via the **Scheduled tab** on the dispatch queue (`request_type='scheduled'`
  by `scheduled_for`). No new workflow engine — column-backed list only. `ponytail: scheduler job deferred.`
- **Sustainability (R9)** — `admin/ads` index toggles `ad_placements`; the note enforces that ads
  render only where `is_emergency_safe` and never on the public request/track screens.
- **Models:** `DeviceToken`. **Views:** `admin/safety/index`, `admin/ads/index`.

## Seeders

- `RolePermissionSeeder` — 5 new permissions (`dispatch-incidents`, `drive-unit`, `record-care`,
  `manage-hospitals`, `manage-safety`); all synced to `super_admin`, and all except `drive-unit`
  attached to `platform_executive`.
- `HospitalSeeder` (new, registered in `DatabaseSeeder`) — 3 Dasmariñas hospitals for demoable handoff.

## R-revisions touched

| Revision | What | Where |
|---|---|---|
| R4 | Device-UUID strike anti-abuse (3 / 30 days) | `StrikeService`, intake guard, `SafetyController` |
| R7 | Dispatch org consistency (org from ambulance) | `DispatchController::store` |
| R8 | Scheduled request surfacing | Dispatch "Scheduled" tab |
| R9 | Ad placements, emergency-safe only | `admin/ads`, `SafetyController::ads/toggleAd` |
| R10 | DSS timeout via `system_configurations`; deduped `*_at` columns reused | `DispatchController::dssTimeoutSeconds`, assignment timestamps |

## Patterns followed (unchanged from prior phases)

`DB::transaction` + `AuditLog::record` on every write · `badge bg-{color}-lt` light badges ·
`ti-dots-vertical` dropdown table actions · list.js datatables · `window.feedback`/`confirmAction`
modals · `@can` sidebar gating · `can.perm:<code>` route groups · `$timestamps=false` for
single-timestamp tables.

## Tests added

`DispatchTest`, `DriverTrackingTest`, `MedicalTest`, `AntiAbuseTest` (+ `SmokeViewsTest` extended to
the 5 new admin pages). Cover: DSS ranking, assign flips statuses, R7 org follows ambulance, duty
toggle, full status-machine walk + unit freed on completion, location push, public status JSON,
vitals store + range rejection, endorse→accept→handoff completes incident, 3rd strike blocks device,
blocked-device intake rejected, cancellation held for field verification.

## Deferred (named, with owner)

| Item | Why | Lands |
|---|---|---|
| Reverb / true WebSockets | Polling covers the demo | Hardening / P6 |
| Queued auto-throw + countdown + auto-reassign | Sync DSS + manual reassign covers the flow | P6 |
| Separate field/driver mobile layout + Sanctum API | Single console this phase | Mobile phase |
| Mapbox route geometry / turn-by-turn | `geo:`/`tel:` deep-links only | P6 |
| Non-emergency & scheduled **workflows** | Panel TBD; surfacing only | When rules confirmed |
| Org-admin self-service RBAC / member mgmt | Carried from S4 | Org-console phase |
| Billing / `subscription_payments` | No payment provider | Later |

---

# Non-Technical Information

This phase took the platform from "a request has been received" all the way to "the patient has been
handed off at a hospital and the case is closed." Four new capabilities were added.

## S7 — Dispatching an ambulance

When an emergency request comes in, a dispatcher opens it and sees a **ranked list of recommended
ambulances**. The system does the thinking: it favours units that are **closer**, have the **right
level of care** (Advanced vs Basic Life Support), and carry the **right equipment** for how serious
the case is. The top recommendation is badged "Top pick." The dispatcher clicks **Assign**, confirms,
and the case locks to that organization and unit. If something changes, the dispatcher can **release**
the assignment and the request goes back into the queue.

## S8 — The driver and live tracking

A driver sets their **duty status** (on duty / on break / off duty) and sees their active
assignments. On an assignment they **advance the status** step by step — accepted → en route →
arrived on scene → transporting → arrived at hospital → completed — and can **push their GPS location**
and open the destination in their phone's map app.

Meanwhile, the person who made the request watches a **live tracking page** that refreshes itself
every few seconds. Once a unit is dispatched, the page reveals the **plate number, care level, crew,
and a one-tap button to call the driver**. (This uses simple periodic refreshing rather than a live
socket connection — a deliberate, lighter choice for this build.)

## S9 — Medical care and hospital handoff

On scene, a medic records the patient's **vitals** (blood pressure, pulse, oxygen, etc.),
**treatments given**, **notes**, and **patient details**. If no transport is needed, they can mark the
case **resolved on scene**. If transport is needed, they **endorse the patient to a hospital**. The
hospital **accepts or declines**; on acceptance and arrival, staff **confirm the handoff**, which
writes a handoff summary, **closes the incident**, and frees the ambulance for the next call.

## S10 — Preventing misuse, scheduling, and sustainability

- **Anti-abuse:** each device is tracked. **Three false alarms within 30 days blocks the device** from
  submitting more requests (an administrator can review and unblock). Crucially, when someone tries to
  **cancel** a request, the system does **not** simply delete it — it holds the case as pending and
  asks a responder to verify on the ground, because a "cancelled" call could still be a real emergency.
- **Scheduling:** scheduled (non-urgent) requests appear in their own tab on the dispatch console.
- **Sustainability:** administrators can manage **ad placements**, which are only ever shown in
  approved, non-emergency areas — **never** on the emergency request or tracking screens.

## What was intentionally left for later

Real-time socket tracking, fully automatic dispatch with countdown timers, a dedicated mobile driver
app, turn-by-turn navigation, the detailed rules for non-emergency/scheduled bookings, organizations
managing their own staff, and payment processing — all named and scheduled for later phases.
