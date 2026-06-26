# Existing Features + New Features

*What the current system already does, and what the revised capstone adds to make the
architecture better. Basis: the system analysis of the current build + the post-defense
revised spec. Generated 2026-06-25.*

---

## 1. Existing Features (current build — baseline to carry forward)

These already work in the current Vue/PHP/MySQL system and define the proven baseline:

| Area | Existing capability |
|------|---------------------|
| **Accounts** | Registration, login, email OTP, Google login, ID-document upload, admin approval |
| **Roles** | Fixed roles: super_admin, org_admin, dispatcher, driver, hospital_staff, citizen, guest |
| **Emergency requests** | Registered + guest requests; incident lifecycle (pending → … → completed) |
| **Dispatch** | Dispatcher assigns ambulance; driver accept/reject; timeout reassignment |
| **DSS** | Ambulance + hospital recommendation (distance + suitability), but **weights hardcoded** |
| **Fleet** | Ambulances, locations, fuel logs, maintenance logs, readiness checks |
| **Medical** | Vitals, treatments, pre-hospital notes, hospital endorsement + handoff |
| **Maps** | Leaflet + OpenStreetMap; external Waze/Google deep-links |
| **Realtime** | **Polling** (no websockets) |
| **Admin** | Approvals, audit logs, system logs, basic analytics |
| **Security** | Session auth, CSRF, role scoping, PII masking |

---

## 2. New Features & Improvements (revised spec — what makes it better)

Each item states **why it's an improvement** over the current architecture.

### 2.1 Architecture & Roles
| New / improved | What changes | Why it's better |
|----------------|--------------|-----------------|
| **4-tier hierarchy** | Super Admin → Platform Executive (LGU) → Org Admin → Field users | Clear chain of authority; replaces 7 ad-hoc dashboards |
| **Dynamic RBAC** | Orgs define their own roles + atomized permissions | Fits each organization's real structure instead of fixed roles |
| **True multi-tenancy** | Strict `organization_id` isolation everywhere | Safer data separation between partner organizations |
| **Laravel MVC + Blade** | Structured framework replaces procedural PHP | Maintainable, testable, less custom plumbing |

### 2.2 Dispatch Intelligence
| New / improved | What changes | Why it's better |
|----------------|--------------|-----------------|
| **Heatmap aggregation** | Merge reports within 50–150m into one Master Incident Ticket | Prevents multiple ambulances racing to the same incident |
| **Headless DSS** | System auto-scores Idle units (urgency + equipment tier + traffic-adjusted time) | Faster, less manual dispatcher guesswork |
| **Automatic Throw** | 30–90s countdown offer; auto-reassign on timeout | No stalled incidents waiting on a non-responding crew |
| **Configurable DSS variables** | LGU can adjust e.g. timeout (vs current hardcoded weights) | Tunable without code changes |

### 2.3 Requests & Services
| New / improved | What changes | Why it's better |
|----------------|--------------|-----------------|
| **Four request types** | One-Tap, Detailed, Non-Emergency, Scheduled | Covers more real situations than emergency-only |
| **Equipment-aware matching** | Ambulance tiers (BLS/ALS) + equipment flags | Sends the *right* vehicle, not just the nearest |

### 2.4 Realtime & Navigation
| New / improved | What changes | Why it's better |
|----------------|--------------|-----------------|
| **WebSocket push** | Replaces polling | Instant updates, less server load |
| **Mapbox route + deep-link nav** | Route geometry in-app, turn-by-turn handed to Waze/Google | Good UX without building costly navigation |
| **Unified tracking** | Same live screen for guests and registered users | Consistent experience; encourages registration |

### 2.5 Trust, Safety & Sustainability
| New / improved | What changes | Why it's better |
|----------------|--------------|-----------------|
| **Device-UUID anti-abuse** | Strike tracking for false alarms | Reduces pranks that waste ambulances |
| **Verified cancellation** | Cancellations Pending until field-verified | Prevents mid-route "ghosting" |
| **Non-obstructive ads / donations** | Optional revenue, never on emergency UI | Sustainability without harming the core mission |

---

## 3. Summary — Why the New Architecture Is an Improvement

- **Smarter:** dispatch becomes automatic and equipment-aware, not manual.
- **Faster:** real-time WebSockets + auto-throw remove waiting and polling lag.
- **Safer:** strict tenancy, dynamic permissions, and anti-abuse controls.
- **Cleaner:** Laravel MVC structure replaces hand-rolled procedural PHP.
- **More flexible:** organizations model their own roles; LGU tunes system variables.
- **More complete:** four request types instead of emergency-only.

---

## 4. Carried-Forward Open Items

Some additions depend on decisions still pending (do not finalize until confirmed):
non-emergency & scheduled workflows, lat/lng registration replacement, "remove conditions",
DILG role, terminology fixes, org verification documents (interviews), transport protocol
(interviews).

> *Canonical list: `MIGRATION/01_MIGRATION_PLAN.md` §8; recommended resolutions:
> `MIGRATION/03_RECOMMENDATIONS.md` §2.*

---

*Companion documents: `TECHNICAL ROADMAP.md`, `NON-TECHNICAL ROADMAP.md`,
`PROCESS AND FLOW.md`, `SECURITY IMPROVEMENTS.md`.*
