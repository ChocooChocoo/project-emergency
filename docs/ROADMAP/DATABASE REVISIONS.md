# Database Revisions

*Current MySQL schema → target Laravel migration checklist. Based on a direct read of
`api/_sql/rescue_platform_clean_copy.sql` (50 tables, InnoDB/utf8mb4, 105 FKs) and the
post-defense revised spec. Generated 2026-06-25.*

> **Verdict:** the existing schema is good and ~80% reusable. **Revise, don't redesign.**
> Apply these changes *during* the SQL→migration conversion so you end up with clean
> migrations reflecting the target — not legacy + patches.

---

## 0. Conversion Approach

1. Import the existing SQL into a scratch MySQL database.
2. Reverse-engineer a baseline:
   ```bash
   composer require --dev kitloong/laravel-migrations-generator
   php artisan migrate:generate
   ```
3. Fold the revisions in this document into the generated migrations table-by-table.
4. Keep status vocabularies as **PHP enum casts** in models (not DB enums) where they may
   evolve, to avoid Doctrine enum-alter issues.

### Mechanical mapping (reference)
| SQL | Laravel |
|-----|---------|
| `bigint UNSIGNED` PK | `$table->id()` |
| FK + `REFERENCES` | `$table->foreignId('x')->constrained()` |
| `ON DELETE SET NULL` / `CASCADE` | `->nullOnDelete()` / `->cascadeOnDelete()` |
| composite `UNIQUE KEY` | `$table->unique(['a','b'])` |
| `enum(...)` | `$table->enum(...)` *(or model cast)* |
| `decimal(10,8)` | `$table->decimal('lat',10,8)` |
| `datetime` + `ON UPDATE` | `$table->timestamps()` / `->dateTime()` |

**FK ordering:** create parent tables before children, or add all foreign keys in a final
migration. The generator handles this automatically.

---

## 1. Priority Legend

- 🔴 **Blocker** — required for the revised spec to work.
- 🟡 **Important** — correctness/consistency; do during conversion.
- 🟢 **Cleanup** — tidy-up; low risk, do opportunistically.

---

## 2. Structural Revisions (the ones that matter)

### 🔴 R1 — Make roles organization-owned (dynamic per-org RBAC)
**Current:** `roles.name` is globally unique (`uq_roles_name`); permissions attach to a
role globally. Two orgs cannot each define their own "Medic" with different permissions.
**Target:**
- Add `roles.organization_id` (nullable → NULL = platform/global role).
- Change uniqueness to `(organization_id, name)`.
- FK `roles.organization_id → organizations.id` `ON DELETE CASCADE`.
**Laravel:** or adopt `spatie/laravel-permission` with the **teams** feature, using
`organization_id` as the team key. Either is fine; pick one and be consistent.
**Why:** without this, "dynamic roles per organization" (panel requirement) is impossible.

### 🔴 R2 — Master Incident Ticket (heatmap grouping)
**Current:** every report is a standalone `incidents` row; no grouping.
**Target (choose one):**
- **Option A (simple):** add `incidents.master_incident_id` self-FK (nullable;
  `ON DELETE SET NULL`). First report = master; later nearby reports point to it.
- **Option B (cleaner):** new `incident_reports` child table (raw citizen reports) that
  roll up into one `incidents` (the master ticket).
**Recommendation:** Option A for capstone scope (less surface area).
**Why:** implements the 50–150m merge that prevents duplicate dispatch.

### 🔴 R3 — Structured ambulance tiers & equipment
**Current:** `ambulances.vehicle_type` (varchar) + `equipment_notes` (free text) — not
queryable by the DSS.
**Target:**
- `ambulances.tier` enum/string: `BLS` (Type 1) / `ALS` (Type 2).
- `ambulances.doh_credential_ref` varchar (nullable).
- Equipment flags: either boolean columns (`has_ventilator`, `has_ob_kit`, …) or a
  normalized `ambulance_equipment` table (`ambulance_id`, `equipment_key`, `present`).
**Recommendation:** boolean columns for a fixed known set; table only if the list is open.
**Why:** DSS must match equipment tier to case urgency.

### 🔴 R4 — Device-UUID anti-abuse
**Current:** no device table (`account_flags`/`guest_sessions` only).
**Target:** new `device_tokens` table: `id`, `device_uuid` (unique), `user_id` (nullable FK
SET NULL), `false_alarm_count`, `last_flagged_at`, `is_blocked`, timestamps.
**Why:** implements the "3 false alarms / 30 days" strike rule.

---

## 3. Relationship & Integrity Revisions

### 🟡 R5 — FK on `user_extra_permissions.permission_code`
**Current:** `permission_code` is a loose varchar (soft reference). The role path uses a
real `permission_id` FK; this override path does not — typos/deleted permissions slip through.
**Target:** reference `permissions` by `permission_id` FK (or FK on `code`), matching
`role_permissions`. Keep the existing `(user, org, permission)` uniqueness.

### 🟡 R6 — Enforce single requester on incidents
**Current:** `incidents.user_id` and `incidents.guest_id` both nullable (either/or) — but
nothing prevents both NULL or both set.
**Target:** add a CHECK that exactly one is set (MySQL 8 supports CHECK), or enforce in a
model observer. (Capstone: observer is fine.)

### 🟡 R7 — Cross-table org consistency
**Current:** `organization_id` is denormalized across `incidents`, `ambulances`,
`dispatch_assignments`, `hospitals`, `user_roles`, `user_extra_permissions` (good for
tenant-scoped queries) — but an assignment's org could drift from its ambulance's org.
**Target:** no schema change required; enforce consistency in the Laravel layer (model
observer or service) when creating `dispatch_assignments`. Document the invariant.

---

## 4. Additive Tables (new features)

### 🟡 R8 — Request types & scheduling
**Current:** `incidents.request_mode` is a loose varchar; no scheduling field.
**Target:**
- `incidents.request_type` enum: `one_tap` / `detailed` / `non_emergency` / `scheduled`.
- `incidents.scheduled_for` datetime (nullable; for Scheduled Rescue).
**Note:** non-emergency & scheduled **workflows are still [TBD]** (panel) — add the columns,
defer the process logic.

### 🟢 R9 — Sustainability / ads
**Target:** new `ad_placements` table: `id`, `slot`, `content`, `active`,
`emergency_safe` (bool, must be excluded from emergency UI), timestamps. Low priority.

---

## 5. Cleanup (tidy during conversion)

### 🟢 R10 — Remove duplicate timestamp columns
`dispatch_assignments` carries legacy duplicates:
- `arrived_on_scene_at` **and** `arrived_at_scene_at`
- `arrived_at_facility_at` **and** `arrived_at_hospital_at`
Keep one of each; drop the other.

### 🟢 R11 — Split the wide `organizations` table (~50 columns)
It mixes identity + operations + subscription + coverage. `org_subscriptions` and
`organization_coverage_areas` already exist — push subscription/coverage fields there and
keep `organizations` to identity + status. Optional but improves clarity.

### 🟢 R12 — Normalize status casing (panel "fix terminologies")
Inconsistent: `incidents.status` is lowercase enum, but `users.account_status`=`PENDING_OTP`
and `ambulances.status`=`AVAILABLE` are uppercase. Pick **one** convention (lowercase
snake_case recommended) across all status columns + model casts.

### 🟢 R13 — Laravel naming conventions
- `users.password_hash` → `password` (Laravel auth expects this).
- `users` already has `email_verified_at`, `created_at`, `updated_at` — keep (match Eloquent).
- Denormalized `users.full_name` alongside `first/middle/last` — keep only if a real read
  optimization; otherwise drop and compute via accessor.

---

## 6. Keep As-Is (already good — do not change)

- InnoDB + utf8mb4 everywhere; 105 FKs with deliberate SET NULL vs CASCADE split.
- Org-scoped uniqueness: `uq_ambulances_org_plate(organization_id, plate_no)`.
- Junction composite uniques: `uq_role_permission`, `uq_user_role_scope`, `uq_user_org_perm`.
- 1:1 unique FKs: `citizen_profiles↔user`, `handoff_summary↔incident`, `patients↔incident`,
  `org_subscriptions↔org`, `user_medical_history↔user`, `driver_duty↔driver`.
- Natural-key uniques: email, `incidents.request_code`, `permissions.code`, `plans.code`,
  google_sub, session token_hash.
- Forward-looking fields already present: `dispatch_routing_state`, `dss_org_queue_json`,
  `dss_rank`, `alert_attempts`, `response_deadline_at`, `flagged_for_abuse`,
  `auto_progression_radius_m`, `response_target_minutes`.

---

## 7. Execution Checklist

| # | Pri | Change | Touches |
|---|-----|--------|---------|
| R1 | 🔴 | Org-owned roles | `roles` (+ uniqueness, FK) |
| R2 | 🔴 | Master Incident Ticket self-FK | `incidents` |
| R3 | 🔴 | Ambulance tier + equipment + DOH ref | `ambulances` (+ maybe `ambulance_equipment`) |
| R4 | 🔴 | Device-UUID strikes | new `device_tokens` |
| R5 | 🟡 | FK on user_extra_permissions | `user_extra_permissions` |
| R6 | 🟡 | Single-requester check | `incidents` |
| R7 | 🟡 | Org consistency invariant | app layer (no schema) |
| R8 | 🟡 | Request type + scheduled_for | `incidents` |
| R9 | 🟢 | Ad placements | new `ad_placements` |
| R10 | 🟢 | Drop duplicate timestamps | `dispatch_assignments` |
| R11 | 🟢 | Split wide organizations table | `organizations` (+ existing children) |
| R12 | 🟢 | Normalize status casing | multiple |
| R13 | 🟢 | Laravel naming (`password`, etc.) | `users` |

> Optional/skip for capstone: `POINT` + `SPATIAL INDEX` for lat/lng. Haversine over
> `decimal` is fine at city scale; add only if proximity/heatmap queries measurably slow.

---

*Companion documents: `TECHNICAL ROADMAP.md`, `EXISTING FEATURES + NEW FEATURES.md`,
`PROCESS AND FLOW.md`, `SECURITY IMPROVEMENTS.md`, `NON-TECHNICAL ROADMAP.md`.*
