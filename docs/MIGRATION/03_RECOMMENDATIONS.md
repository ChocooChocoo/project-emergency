# 03 — Recommendations

*Planning document only. Recommendations are explicitly labeled as such; they are the
suggested way to realize the revised spec in Laravel MVC. Anything not stated in the
sources is marked as a **recommendation/default**, not a confirmed requirement. Generated
2026-06-25.*

---

## 1. Stack & Package Recommendations (per revised feature)

| Revised-spec need (source) | Recommended Laravel approach | Why |
|----------------------------|------------------------------|-----|
| Dynamic roles/permissions per org (Sources 2, 3, 5) | `spatie/laravel-permission` scoped per `organization_id`, exposed via an org-facing role builder | Mature, supports atomized permissions like `accept-incident`, `manage-fleet` |
| Multi-tenant isolation (Source 2) | Global Eloquent query scope on `organization_id` (single-DB multi-tenant) | Matches existing org-scoped model; simplest correct option |
| Headless DSS + Automatic Throw 30–90s (Source 5) | Dedicated `DssService` + queued `AutomaticThrowJob` with delayed reassignment | Keeps DSS isolated and testable; queue handles countdown/timeout |
| Scheduled / non-emergency dispatch (Sources 2, 3) | Laravel Scheduler + queued jobs | Native scheduling once workflow is defined |
| Real-time push (replaces polling, Sources 1, 5) | Laravel broadcasting via **Reverb** (self-hosted WebSockets) | First-party, avoids paid realtime vendor; replaces current polling |
| Mobile + web API auth (Source 1) | **Sanctum** tokens | Lightweight for mobile/API clients; Blade consoles use session auth |
| Google OAuth (Source 1) | **Socialite** | First-party social login |
| Email OTP (Source 1) | Laravel Notifications + Mail | Reuses existing OTP concept |
| Org document uploads (Sources 2, 5) | Laravel filesystem (private disk) + validation | Matches current private-upload model |
| Audit logging (Source 1) | Model observers writing to `audit_logs` | Centralizes the existing audit behavior |
| Anti-abuse device strikes (Source 5) | `DeviceToken` model + strike-counting service | Implements UUID strike rule directly |
| Maps (Source 5) | **Mapbox** for in-app route geometry; deep-link to Waze/Google Maps | Spec explicitly names Mapbox + deep-link nav |

> **Front-end recommendation:** classic **Laravel MVC with Blade** for the four web
> consoles — **no Inertia, no Vue**. Views are server-rendered Blade templates driven by
> controllers; interactivity uses Blade + minimal JS (e.g. Alpine.js) and Laravel
> broadcasting for realtime. The current Vue SPA is not reused; its screens are
> re-implemented as Blade views. The citizen and driver **mobile apps consume the Laravel
> JSON API** (Sanctum), keeping one backend for all clients.

---

## 2. Recommended Resolutions for Open Items

These six items are **unresolved in the sources** (canonical list: `01_MIGRATION_PLAN.md`
§8; origin `DOCUMENT ARCHITECTURE.md` §6). Below are recommended defaults **to be confirmed
with the client/panel** before building the affected phase. *This section is the single
source for the recommended resolutions; other docs point here.*

| # | Open item | Recommended default | Confirm before |
|---|-----------|---------------------|----------------|
| 1 | **"Remove conditions"** | Interpret as removing medical pre-condition fields from *citizen registration* (collect medical info optionally at request time instead). | P2 |
| 2 | **Lat/long removal — replacement** | Replace manual lat/lng with **map pin-drop + reverse-geocoded address**; device GPS for emergencies. | P3 |
| 3 | **Scheduled / non-emergency workflow** | Model as a separate request type with a booking time, org acceptance, and reminder job; route through DSS only at activation time. | P5 |
| 4 | **DILG role** | Treat as an **oversight/compliance stakeholder** with read-only reporting access (not an onboarding approver — keep approval with LGU/Platform Executive). | P1 |
| 5 | **"Fix of terminologies"** | Run a single naming-consistency pass mapping current labels → revised terms (e.g., role names, "incident" vs "ticket"); maintain a glossary. | P0 |
| 6 | **Org↔field role remapping** | Adopt the §5.2 inference: original Dispatcher/Hospital-Staff/Org-Admin → Org-Admin + Field roles; original Super Admin (LGU) → Platform Executive; new dev Super Admin on top. | P1 |

---

## 3. Sequencing & Quick Wins

- **Start with P0/P1 (foundation + tenancy + RBAC).** Everything else depends on the
  4-tier hierarchy and dynamic roles; building these first de-risks the rest.
- **Build the DSS as an isolated service early (P2)** with unit tests for scoring and the
  auto-throw/timeout logic — it is the project's analytical core and highest-risk logic.
- **Reuse the existing relational schema** as Laravel migrations (quick win — schema design
  already exists and is proven, per Source 1).
- **Defer ads/sustainability (P5)** — lowest risk, no dependency, and must never obstruct
  emergency UI (Source 5).

---

## 4. What to Validate via the Pending Interviews

The sources (Source 3 §7) explicitly flag two interview-dependent areas. **Do not finalize
these phases until interviews are done:**

1. **Organization verification (P1):** exact legitimacy criteria, required documents, and
   approval authority — confirm with ambulance-owning facilities.
2. **Ambulance transport protocol (P4):** validate the on-scene → transport → handoff steps
   with real ambulance drivers; current protocol is based on standards, not field practice.

---

## 5. Constraints to Keep Visible to Stakeholders

From the manuscript scope/limitations (Sources 2, 4):
- **City-bounded:** Dasmariñas City only.
- **Domain-bounded:** emergency medical routing/dispatch only — **no** diagnostics, bed
  management, or police/fire integration.
- **Internet-dependent:** no offline mode for tracking/DSS.
- **DSS limits:** rule/data-bound; cannot account for unpredictable road/weather/traffic
  events beyond its inputs.

---

*See also: `01_MIGRATION_PLAN.md`, `02_PROCESS_AND_FLOW.md`, `04_SYSTEM_ARCHITECTURE.md`.*
