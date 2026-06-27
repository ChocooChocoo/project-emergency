# Summary — Tasks-03: S4 Organizations & Onboarding, S5 Fleet, S6 Citizen/Guest Intake

## What was built

### S4 — Organizations & Onboarding

**Models:** `Organization`, `Plan`, `OrganizationSubscription`, `OrganizationDocument`, `OrganizationCoverageArea` — fillable + casts + relations only.

**Seeders:** `PlanSeeder` (3 plans: `lgu_free` unlimited, `partner_basic` cap 3/10, `partner_pro` cap 10/40). Registered in `DatabaseSeeder` between `RolePermissionSeeder` and `UserSeeder`.

**Permissions added** (seeded in `RolePermissionSeeder`, attached to `platform_executive`):
- `manage-organizations` — org registry CRUD + archive/restore
- `review-org-approvals` — org approval/rejection + document validation

**Controllers:**
- `OrganizationController` — index (search/filter/paginate), create/store (org created with `organization_status='pending_review'`, `is_active=false` + subscription row), show, edit/update, archive/restore (copies UserController archive pattern: `archival_logs` snapshot).
- `OrgApprovalController` — index (pending_review orgs), show (review docs), approve (sets `organization_status='active'`, `is_approved=true`, `is_active=true`, `approved_by/approved_at` + `approval_records` insert + AuditLog), reject (`rejected_reason` + record), `updateDocumentStatus` (validates/rejects individual org documents).

**Routes:** under `['auth','account.active']` → `can.perm:manage-organizations` and `can.perm:review-org-approvals` subgroups.

**Views:** `admin/organizations/` — index (datatable), create, edit, show (profile + docs table + subscription card); `admin/org-approvals/` — index, show (review + document validation actions).

**Sidebar:** Organizations (`@can('manage-organizations')`), Org Approvals (`@can('review-org-approvals')`).

**Tests:** `OrganizationTest.php` — super admin creates org as pending, LGU approve activates org, citizen forbidden (3 pass).

---

### S5 — Fleet Module

**Models:** `Ambulance` (with `EQUIPMENT` constant for 6 boolean flags: ventilator/oxygen/AED/spine board/OB kit/stretcher), `FuelLog`, `MaintenanceLog`.

**Permissions added:**
- `manage-fleet` — ambulance CRUD + logs

**Controller:** `AmbulanceController` — index (filter by org/tier/status/search), create/store with **plan cap guard** (`planCapExceeded()` checks `plan->max_ambulances` before insert), show (profile + equipment grid + fuel/maintenance log tables + inline POST forms), edit/update, archive/restore, `storeFuelLog`, `storeMaintenanceLog` (nested POSTs on the show page).

**Form Requests:** `Fleet/StoreAmbulanceRequest`, `Fleet/UpdateAmbulanceRequest` — plate uniqueness scoped per `organization_id`.

**Routes:** `can.perm:manage-fleet` group; `POST /admin/ambulances/{ambulance}/fuel-logs` and `.../maintenance-logs` colocated on the ambulance resource.

**Views:** `admin/ambulances/` — index (datatable with tier/serviceable badges), create/edit (`_form` with equipment checkboxes looping over `Ambulance::EQUIPMENT`), show (Bootstrap modals for inline fuel/maintenance log add forms).

**Sidebar:** Fleet nav (`@can('manage-fleet')`).

**Tests:** `FleetTest.php` — ambulance registers with tier + equipment, plate unique per org enforced, plan cap blocks 4th ambulance on `partner_basic`, citizen forbidden (4 pass).

---

### S6 — Citizen/Guest Request Intake

**Models:** `Incident` (with `OPEN_STATUSES` constant, self-ref `masterIncident`/`childReports` for R2 grouping), `GuestSession` (`hasQuotaRemaining()`), `IncidentUpdate` (`$timestamps = false`).

**Service:** `GuestSessionService` — `resolveOrCreate(Request): GuestSession` (cookie `guest_key` lookup or create new session with explicit defaults: `requests_limit=2`, `requests_used=0`, `is_active=true`), `consume(GuestSession)` (increments `requests_used`).

**Permissions added:**
- `view-incidents` — admin incident list/detail (read-only triage; dispatch is S7)

**Controllers:**
- `Intake/RequestIntakeController` — public, no auth required. `create` shows intake form. `store` (R6): resolves user or guest (never both), checks quota, finds master ticket (R2 Haversine scan), creates `Incident` with `REQ-`+ULID code and initial `IncidentUpdate`, consumes guest slot, sets cookie, redirects to track page. `track` shows reference + status.
- `Admin/IncidentController` — index (search/filter/paginate by type/status/org), show (loads organization, user, guest, masterIncident, childReports, updates).

**R2 — Master Incident Ticket Grouping:** `findMasterTicket()` does an O(n) Haversine scan over open incidents within 150m and 15-minute window. Marked with `ponytail:` comment; upgrade path is `HeatmapAggregator` + spatial index in S7.

**R6 — Requester invariant:** exactly one of `user_id`/`guest_id` set, enforced in controller (MySQL can't CHECK + SET NULL FK on same column).

**Request code:** `REQ-` + `strtoupper(Str::ulid())` — lexicographically sortable, URL-safe.

**Form Request:** `Intake/StoreIncidentRequest` — requires `pickup_lat/lng/address`; `patient_name`/`contact_number` required only for `request_type=detailed`.

**Routes:** Public (outside auth group): `GET /request`, `POST /request` (throttle:10,1), `GET /request/{code}`. Admin (inside auth group, `can.perm:view-incidents`): `GET /admin/incidents`, `GET /admin/incidents/{incident}`.

**Views:**
- `request/create.blade.php` — extends auth layout; GPS button captures coords to hidden fields; accordion expand upgrades `request_type` to `detailed`.
- `request/track.blade.php` — reference number, status badge, pickup address, "live tracking once dispatched" notice.
- `admin/incidents/index.blade.php` — datatable with status/type badges, grouped indicator, list.js sort.
- `admin/incidents/show.blade.php` — detail cards (status/type/severity/org/dates) + requester info (user or guest key + quota) + location + grouping (master + children links) + updates timeline.

**Sidebar:** Incidents nav (`@can('view-incidents')`).

**Tests:** `IntakeTest.php` — guest submit creates incident + session, exhausted quota blocks and returns error, authed citizen sets `user_id` not `guest_id`, two nearby reports group under same master, admin list requires `view-incidents`, super admin can view list (6 pass).

**SmokeViewsTest** updated: now seeds `PlanSeeder` and hits all new admin pages + `/request` (all 3 tests pass).

**Total: 21 tests, 21 pass.**

---

## Patterns followed

| Pattern | Where used |
|---|---|
| `DB::transaction` + `AuditLog::record` after each write | All controllers |
| `archive/restore` with `archival_logs` snapshot | Org, Ambulance |
| `approval_records` insert + AuditLog on approve/reject | OrgApprovalController |
| `badge bg-{color}-lt` (light badges) | All status labels |
| 3-dot `ti-dots-vertical` dropdown for table actions | All index views |
| `window.feedback`/`confirmAction` for UI messages | All action views |
| List.js datatable pattern from `template/datatables.html` | All index views |
| `@can('<perm>')` sidebar gating | Sidebar for all 4 new modules |
| `can.perm:<code>` middleware | All new route groups |

## R-revisions touched

| Revision | What | Where |
|---|---|---|
| R2 | Master Incident Ticket grouping (Haversine) | `RequestIntakeController::findMasterTicket` |
| R3 | Ambulance `tier` (`bls`/`als`) + equipment flags + `doh_credential_ref` | `Ambulance` model + AmbulanceController + form |
| R6 | Requester invariant (user XOR guest) | `RequestIntakeController::store` |
| R8 | `request_type` (`one_tap`/`detailed`) + `scheduled_for` | `Incident` model + StoreIncidentRequest |

---

## Deferred items (explicitly out of scope for S4–S6)

| Item | Reason deferred | Owning step |
|---|---|---|
| Public org self-signup wizard | User chose LGU-managed console only | S9 |
| Org-admin console (tenant layout, `org_admin` self-service) | User chose platform-consoles-only | S9 |
| Dynamic role builder per org | Requires org-admin console | S9 |
| Member management (invite/remove) | Same | S9 |
| Billing engine / `subscription_payments` | Schema present, no payment provider wired | S9 |
| Driver duty toggle UI | Driver console is a separate surface | S8 |
| Unit readiness checks UI | Driver console | S8 |
| Full HeatmapAggregator + spatial index | O(n) scan sufficient at current intake volume | S7 |
| Dispatch DSS (smart org queue, ETA) | Read-only triage only this phase | S7 |
| Live tracking page (ETA, plate, crew) | Tracking placeholder only | S8 |
| JSON/REST API + Sanctum | Mobile intake + driver app | S8 |
| Guardian/minor flow | `guardian_links` schema present; full flow deferred | S10 |
| Scheduled / non-emergency request type | Schema column present (`scheduled_for`); no UI | S7/S8 |
| Citizen profile / medical history screens | Partial fields in intake; full profile-management deferred | S10 |
| Anti-abuse / strikes system | Quota-only this phase | S10 |

---

## Plain-language summary (non-technical)

### What this phase built — in plain terms

This phase added three major capabilities to the platform:

1. **Organization management** — how ambulance groups join and get approved
2. **Fleet management** — how ambulances are registered and tracked
3. **Emergency requests** — how someone calls for an ambulance

---

### S4 — How organizations join the platform

**The flow:**

1. A platform administrator fills in a registration form for an ambulance organization (name, type, service area, contact details, which subscription plan they want).
2. The system saves the organization as **"Pending Review"** — it is not yet active. No ambulances can be dispatched from it yet.
3. A reviewer (a different administrator with the "Org Approvals" role) opens the organization's profile and checks the submitted documents.
4. The reviewer either **Approves** or **Rejects** the organization.
   - **Approved** → the organization becomes active and can now register ambulances and receive dispatch assignments.
   - **Rejected** → the organization is told why and stays inactive.
5. All approval decisions are recorded automatically (who approved, when, what reason).

**What administrators can do with organizations:**
- Browse and search all registered organizations in a table
- View an organization's full profile: contact info, service coverage, subscription plan, and uploaded documents
- Edit organization details
- Archive organizations that are no longer operating (archived ones are hidden but not deleted, and can be restored)

**Subscription plans** control limits — for example, a basic plan may only allow up to 3 ambulances. These limits are enforced automatically when ambulances are registered.

---

### S5 — How ambulances are registered and managed

**The flow:**

1. Once an organization is active, an administrator can register its ambulances.
2. Each ambulance record includes: the license plate, what level of care it provides (Basic Life Support or Advanced Life Support), what medical equipment it carries (ventilator, oxygen, AED, stretcher, etc.), and which driver is currently assigned.
3. Before the ambulance is saved, the system checks if the organization's plan allows more ambulances. If the limit is reached, the registration is blocked with a clear message.
4. Once registered, the ambulance appears in the Fleet list and is available for dispatch (the actual dispatching is built in the next phase).

**What administrators can do with fleet:**
- Browse ambulances by organization, type, or availability status
- View an individual ambulance's full profile, equipment list, and service history
- Log fuel refills (date, liters, cost, station)
- Log maintenance events (what was done, scheduled date, next service due)
- Archive ambulances that are decommissioned

---

### S6 — How someone requests an ambulance

This is the public-facing part of the platform — the part a regular person or bystander uses.

**Two types of people can submit a request:**

- **Registered users** (e.g. citizens with an account) — their identity is known.
- **Guests** (anyone without an account) — the system gives them a temporary anonymous identity via a cookie stored in their browser. Guests can submit up to 2 requests before they are prompted to register.

**The request flow:**

1. A person visits the request page (`/request`) on any device — no login required.
2. They tap **"Use my location"** to share their GPS coordinates, or they type their address manually.
3. Optionally, they can expand a section to add more details: patient name, contact number, nature of the emergency, a brief description.
4. They tap **"Send emergency request"**.
5. The system:
   - Assigns a unique reference code (e.g. `REQ-01J…`) to the request.
   - Checks if there is already an open nearby request within 150 meters submitted in the last 15 minutes. If yes, it links the new report to that existing request — so multiple people calling about the same accident are grouped together rather than treated as separate incidents.
   - Records who submitted it (registered user or guest).
   - Saves the first status update: "Request received."
6. The person is redirected to a **tracking page** showing their reference code, current status, and pickup address. A message explains that live tracking (ambulance ETA, plate number, crew) will appear once a unit is dispatched — that feature is built in the next phase.

**What administrators can see:**
- A full list of all incoming requests, filterable by status, type, organization, or search by code/patient name
- Each request's detail page: status, requester (name or anonymous guest ID), location, any grouped related reports, and a chronological log of status updates

**Guest limits:**
- Guests can submit 2 requests. After that, they see a message asking them to register for a full account if they need to submit more.
- Each guest is identified by a key stored in their browser — closing and reopening the browser on the same device keeps their session intact for up to a year.

---

### What was intentionally left for later

| What | Plain explanation | When |
|---|---|---|
| Organizations registering themselves | Right now only platform admins create orgs. A self-service signup wizard is planned but not yet built. | Later phase |
| Org admins managing their own team | Each org will eventually have its own admin who can invite members and assign roles. Not built yet. | Later phase |
| Billing and payments | Subscription plans exist and limits are enforced, but no payment processing is wired in. | Later phase |
| Driver app | Drivers will have their own screen to toggle their duty status and accept assignments. Not built yet. | Later phase |
| Live tracking | The tracking page shows a placeholder message right now. Real-time ambulance location and ETA requires the dispatch system to be built first. | Next phase |
| Smart dispatch | The system does not yet automatically pick which ambulance to send. Administrators will see the requests but manual/smart dispatching is the next phase. | Next phase |
| Ambulance app / mobile API | The request form works in a mobile browser, but a native app or dedicated mobile API is a later phase. | Later phase |
| Full patient profiles | Basic patient name and contact are captured. Full medical history and guardian/minor flows are deferred. | Later phase |
