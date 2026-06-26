# 02 — Updated Process & Flow (Revised Spec)

*Planning document only. Flows reflect the **post-defense revised specification**.
Derived solely from the provided documentation. Branches the sources leave undefined are
marked **[TBD]**. Generated 2026-06-25.*

---

## 1. End-to-End Process (Narrative, Start → End)

1. **Request intake.** A citizen (registered or guest) submits one of four request types
   (Source 3): **One-Tap Emergency**, **Detailed Emergency**, **Non-Emergency**, or
   **Scheduled Rescue**. GPS is auto-attached for emergencies.
2. **Heatmap aggregation.** Overlapping reports within a **50–150m** radius merge into a
   single **Master Incident Ticket** to prevent duplicate dispatch (Source 5).
3. **Headless DSS scoring.** The backend queries all **Idle** ambulances and scores them by
   **case urgency, equipment tier match, and traffic-adjusted travel time** (Source 5).
4. **Automatic Throw.** The top-ranked organization's dispatch screen gets a pop-up with a
   **30–90s countdown**. Accept → case locks to that unit. Timeout → DSS auto-passes the
   ticket to the next best-ranked team (Source 5).
5. **Driver mobilization.** On accept, the driver app receives a silent **WebSocket** push
   with destination coordinates and a **Mapbox** route; the "Navigate" button **deep-links**
   to Waze/Google Maps for turn-by-turn (Source 5).
6. **Unified tracking.** Registered and guest citizens see the same live tracking UI (ETA,
   plate number, crew, tier badge) and can **call the driver via native `tel:`** (Source 5).
7. **On-scene & handoff.** Crew provides care; patient is endorsed to and accepted by a
   hospital, then physically handed off (baseline lifecycle, Source 1).
8. **Completion.** Incident closes; unit returns to Idle/available.
   *Cancellation is not instant:* requests are flagged **Pending** until field-verified
   (Source 5).

---

## 2. Master Flow — Emergency Request to Completion

```mermaid
flowchart TD
    A[Citizen / Guest opens app] --> B{Request type}
    B -->|One-Tap| C[Auto-attach GPS, minimal input]
    B -->|Detailed| D[GPS + situation details]
    B -->|Non-Emergency| E["[TBD] non-emergency intake"]
    B -->|Scheduled| F["[TBD] scheduled booking intake"]
    C --> G[Heatmap aggregation 50-150m]
    D --> G
    G --> H{Existing Master Ticket nearby?}
    H -- Yes --> I[Merge into Master Incident Ticket]
    H -- No --> J[Create new Master Incident Ticket]
    I --> K[Headless DSS scores Idle ambulances]
    J --> K
    K --> L[Rank by urgency + equipment tier + traffic-adjusted time]
    L --> M[Automatic Throw to top org: 30-90s countdown]
    M --> N{Accepted in window?}
    N -- No / timeout --> O[Reassign to next best-ranked unit]
    O --> M
    N -- Yes --> P[Case locks to unit]
    P --> Q[Driver mobilization: WebSocket push + Mapbox route]
    Q --> R[Deep-link Waze / Google Maps for navigation]
    R --> S[Unified live tracking for citizen/guest]
    S --> T[On-scene care]
    T --> U[Hospital endorsement + handoff]
    U --> V[Incident completed, unit returns to Idle]
```

---

## 3. Request-Type Intake (4 types)

```mermaid
flowchart TD
    A[Start request] --> B{Type}
    B -->|One-Tap Emergency| C[Immediate, minimal input, GPS auto]
    B -->|Detailed Emergency| D[Richer situation info, GPS auto]
    B -->|Non-Emergency| E["Assistance, not urgent — workflow [TBD]"]
    B -->|Scheduled Rescue| F["Book transport in advance — workflow [TBD]"]
    C --> G[Enter emergency dispatch pipeline]
    D --> G
    E --> H["[TBD] routing: triage vs queue vs schedule"]
    F --> I["[TBD] scheduling + approval + reminder"]
```

> **[TBD] (Source 2 §6):** Non-emergency and scheduled flows are named in the sources but
> their detailed workflow, approval steps, and scheduling UI are **not yet designed**.

---

## 4. Heatmap Aggregation → Master Incident Ticket

```mermaid
flowchart TD
    A[New emergency report w/ GPS] --> B[Find active reports within 50-150m]
    B --> C{Match found?}
    C -- Yes --> D[Attach report to existing Master Incident Ticket]
    C -- No --> E[Create new Master Incident Ticket]
    D --> F[Single dispatch decision per Master Ticket]
    E --> F
    F --> G[Prevents duplicate fleet dispatch]
```

---

## 5. Headless DSS + Automatic Throw

```mermaid
sequenceDiagram
    participant SYS as System (incident trigger)
    participant DSS as Headless DSS
    participant ORG as Org Dispatch Screen
    participant U as Next-ranked Org

    SYS->>DSS: Master Incident Ticket created
    DSS->>DSS: Query Idle ambulances
    DSS->>DSS: Score by urgency + equipment tier + traffic-adjusted time
    DSS->>ORG: Automatic Throw (pop-up + 30-90s countdown)
    alt Accepted within window
        ORG-->>DSS: Accept
        DSS->>SYS: Lock case to unit
    else Timeout
        DSS->>U: Reassign to next best-ranked team
        U-->>DSS: (repeat accept/timeout)
    end
```

---

## 6. Organization Onboarding (Revised — LGU-approved, multi-tenant)

```mermaid
flowchart TD
    A[Organization signs up via web] --> B[Status = Pending]
    B --> C[Upload legal docs:<br/>Barangay Resolution / SEC / DOH license]
    C --> D[Platform Executive Admin - LGU reviews]
    D -- Rejected --> E[Resubmit corrections] --> D
    D -- Approved --> F[Organization activated]
    F --> G[Register fleet: DOH credential + Type 1 BLS / Type 2 ALS<br/>+ equipment checklist has_ventilator/has_ob_kit]
    F --> H[Org Admin configures Dynamic RBAC:<br/>create custom roles + atomized permissions]
    G --> I[Org operational]
    H --> I
```

> **Pre-req (Source 3):** an organization must own/operate **at least one ambulance** to
> register. **[TBD]** exact verification criteria/documents pending facility interviews.

---

## 7. 4-Tier Administrative Hierarchy (Access Flow)

```mermaid
flowchart TD
    SA[Super Admin - Dev Team<br/>root, read-only global health logs, masked PII] --> PE[Platform Executive Admin - LGU/CDRRMO/CHO<br/>approve orgs, set global vars e.g. DSS timeout, city metrics]
    PE --> OA[Organization Admin - Tenant<br/>manage staff, configure dynamic RBAC]
    OA --> FU[Field / Operation Users<br/>org-defined roles: Dispatcher / Driver / Medic<br/>atomized perms: accept-incident, manage-fleet]
    C[Citizens / Guests] -. consumer-facing app .-> FU
```

> **[TBD] (Source 2 §6):** mapping of original Dispatcher/Hospital-Staff/Org-Admin roles
> onto the new Org-Admin + Field-User tiers, and **DILG's** specific touchpoint, require
> client confirmation.

---

## 8. Driver & Navigation Flow

```mermaid
flowchart TD
    A[Case locked to unit] --> B[Silent WebSocket token w/ destination coords]
    B --> C[Render Mapbox route geometry]
    C --> D{Driver taps Navigate?}
    D -- Yes --> E[OS geo: intent -> Waze / Google Maps turn-by-turn]
    D -- No --> F[Follow in-app route]
    E --> G[Proceed to scene]
    F --> G
    G --> H[On-scene -> care -> transport]
    H --> I[Hospital endorsement + handoff]
    I --> J[Complete -> return to Idle]
```

---

## 9. Anti-Abuse & Cancellation Flow

```mermaid
flowchart TD
    A[Citizen requests cancellation mid-route] --> B[Flag cancellation as Pending]
    B --> C[Field driver / dispatcher visually inspects scene]
    C --> D{Legitimate?}
    D -- Yes --> E[Resolve / close incident]
    D -- No / prank --> F[Record false alarm against device UUID + account]
    F --> G{3 false alarms in 30 days?}
    G -- Yes --> H[Disable guest one-tap for device<br/>OR attach High Risk / False Alarm label]
    G -- No --> I[Continue normal service]
```

---

## 10. Flows Pending Definition (TBD register)

> *Flow-specific view of the open items. Canonical list: `01_MIGRATION_PLAN.md` §8.*

| Flow | Status | Source |
|------|--------|--------|
| Non-emergency request handling | Named, not designed | Source 2 §6, Source 3 |
| Scheduled rescue (booking, approval, reminders) | Named, not designed | Source 2 §6, Source 3 |
| lat/lng registration replacement (pin-drop vs geocode) | Removal confirmed; replacement open | Source 2 §6 |
| "Remove conditions" | Meaning undefined | Source 2 §6 |
| DILG touchpoint | Stakeholder role unclear | Source 2 §6 |

---

*See also: `01_MIGRATION_PLAN.md`, `03_RECOMMENDATIONS.md`, `04_SYSTEM_ARCHITECTURE.md`.*
