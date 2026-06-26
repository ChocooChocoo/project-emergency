# Process and Flow (Per Module)

*Process and flow for every module/role of the revised system. Flowcharts reflect the
post-defense capstone spec on Laravel MVC. Branches the sources leave undefined are marked
**[TBD]**. Generated 2026-06-25.*

---

## Module Index

1. [Authentication & Account](#1-authentication--account-module-all-users)
2. [Citizen / Guest](#2-citizen--guest-module)
3. [Field Crew — Dispatcher](#3-field-crew--dispatcher-module)
4. [Field Crew — Driver](#4-field-crew--driver-module)
5. [Field Crew — Medic + Hospital Handoff](#5-medic--hospital-handoff-module)
6. [Organization Admin](#6-organization-admin-module)
7. [Platform Executive (LGU)](#7-platform-executive-lgu-module)
8. [Super Admin](#8-super-admin-module)
9. [Decision Support System (cross-cutting)](#9-decision-support-system-cross-cutting)

---

## 1. Authentication & Account Module (all users)

```mermaid
flowchart TD
    A[Sign up: citizen / org admin / field user] --> B[account = pending_otp]
    B --> C{Email OTP verified?}
    C -- No --> D[Resend / re-enter OTP] --> C
    C -- Yes --> E{Role needs approval?}
    E -- Citizen --> F[Active]
    E -- Org/Field/Hospital --> G[Awaiting approval]
    G --> H{Approved?}
    H -- No --> I[Edit & resubmit] --> H
    H -- Yes --> F
    F --> J[Login - session for consoles / token for mobile]
    J --> K[Routed to role's dashboard]
```

Alternate entry: **Google login (Socialite)**. Password reset via expiring signed code.

---

## 2. Citizen / Guest Module

```mermaid
flowchart TD
    A[Open app] --> B{Request type}
    B -->|One-Tap| C[Auto GPS, minimal input]
    B -->|Detailed| D[GPS + situation details]
    B -->|Non-Emergency| E["[TBD] non-emergency intake"]
    B -->|Scheduled| F["[TBD] scheduled booking"]
    C --> G[Submit -> enters dispatch pipeline]
    D --> G
    G --> H[Live tracking: ETA, plate, crew, tier badge]
    H --> I[Call driver via native tel:]
    H --> J{Want to cancel?}
    J -- Yes --> K[Cancellation flagged Pending - needs field verification]
    H --> L[Ambulance arrives -> handoff -> case closed]
```

Guests get the same tracking screen; reduced features otherwise (incentive to register).
Minors register with **guardian consent/linkage**.

---

## 3. Field Crew — Dispatcher Module

```mermaid
flowchart TD
    A[Master Incident Ticket created] --> B[DSS Automatic Throw arrives on org screen]
    B --> C[Countdown 30-90s]
    C --> D{Dispatcher accepts?}
    D -- Yes --> E[Case locks to org/unit]
    D -- No / timeout --> F[Ticket auto-reassigned to next-ranked org]
    E --> G[Assign / confirm specific ambulance + driver]
    G --> H[Monitor incident on live queue + map]
    H --> I[Coordinate hospital endorsement if needed]
```

---

## 4. Field Crew — Driver Module

```mermaid
flowchart TD
    A[Driver sets duty: on-duty/Idle] --> B[Receives assignment via WebSocket]
    B --> C{Accept?}
    C -- Reject --> D[Reassigned to next unit]
    C -- No response --> E[timed_out -> reassigned]
    C -- Accept --> F[Mapbox route to scene]
    F --> G[Navigate -> Waze/Google deep-link]
    G --> H[en_route -> arrived_on_scene]
    H --> I[On-scene care with medic]
    I --> J[transporting -> approaching_hospital]
    J --> K[arrived_hospital -> handoff]
    K --> L[Completion report -> clear_for_duty / Idle]
```

---

## 5. Medic + Hospital Handoff Module

```mermaid
flowchart TD
    A[On scene] --> B[Record vitals]
    B --> C[Record treatments + pre-hospital notes]
    C --> D{Transport needed?}
    D -- No --> E[resolved_on_scene -> completion]
    D -- Yes --> F[DSS recommends hospital - capacity/ER/proximity]
    F --> G[Endorse patient -> handoff_status = pending]
    G --> H{Hospital responds}
    H -- Declined --> F
    H -- Accepted --> I[Arrive at hospital]
    I --> J[Hospital confirms receipt -> handoff_status = completed]
    J --> K[Incident completed]
```

---

## 6. Organization Admin Module

```mermaid
flowchart TD
    A[Org signs up] --> B[status = pending]
    B --> C[Upload legal docs: Barangay Resolution / SEC / DOH license]
    C --> D[Wait for LGU approval]
    D -- Approved --> E[Org active]
    E --> F[Register fleet: tier BLS/ALS + equipment + DOH ref]
    E --> G[Configure Dynamic RBAC: create roles + assign permissions]
    E --> H[Add/manage members within plan limits]
    F --> I[Org operational - receives dispatch throws]
    G --> I
    H --> I
```

> Pre-req: org must own ≥1 ambulance. **[TBD]** exact verification docs (pending interviews).

---

## 7. Platform Executive (LGU) Module

```mermaid
flowchart TD
    A[LGU / CDRRMO / City Health Office login] --> B[Review pending organizations]
    B --> C{Documents valid?}
    C -- No --> D[Reject -> org resubmits]
    C -- Yes --> E[Approve & activate org]
    A --> F[Adjust global variables - e.g. DSS timeout countdown]
    A --> G[Monitor city-wide performance metrics]
    A --> H[Review High-Risk / false-alarm device flags]
```

> **[TBD]** DILG's specific touchpoint (oversight vs verification) — confirm with panel.

---

## 8. Super Admin Module

```mermaid
flowchart TD
    A[Super Admin - dev team] --> B[Root access to codebase + database]
    A --> C[Read-only global health logs - troubleshooting]
    A --> D[Sensitive citizen/medical data encrypted + masked]
    A --> E[System configuration + archival + monitoring]
```

Super Admin is infrastructure/oversight only; day-to-day city operations belong to the LGU
tier.

---

## 9. Decision Support System (cross-cutting)

```mermaid
sequenceDiagram
    participant SYS as Incident trigger
    participant AGG as Heatmap Aggregator
    participant DSS as Headless DSS
    participant ORG as Org dispatch
    participant NXT as Next-ranked org

    SYS->>AGG: New report (GPS)
    AGG->>AGG: Merge within 50-150m
    AGG-->>DSS: Master Incident Ticket
    DSS->>DSS: Query Idle ambulances
    DSS->>DSS: Score = urgency + equipment tier + traffic-adjusted time
    DSS->>ORG: Automatic Throw (30-90s)
    alt Accepted
        ORG-->>DSS: Accept -> lock case
    else Timeout
        DSS->>NXT: Reassign to next best
    end
```

---

## TBD Register (flows not yet fully defined in the sources)

> *Module-flow view of the open items. Canonical list: `MIGRATION/01_MIGRATION_PLAN.md` §8;
> recommended resolutions: `MIGRATION/03_RECOMMENDATIONS.md` §2.*

| Flow | Status |
|------|--------|
| Non-emergency request handling | Named, not designed |
| Scheduled rescue (booking/approval/reminders) | Named, not designed |
| lat/lng registration replacement | Removal confirmed; replacement open |
| "Remove conditions" | Meaning undefined |
| DILG touchpoint | Role unclear |

---

*Companion documents: `TECHNICAL ROADMAP.md`, `NON-TECHNICAL ROADMAP.md`,
`EXISTING FEATURES + NEW FEATURES.md`, `SECURITY IMPROVEMENTS.md`.*
