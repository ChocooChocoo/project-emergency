# Web-Based Ambulance Rescue Platform with Decision Support System and Mobile Application

### Compiled System Documentation — Original Capstone Proposal + Post-Defense Panel Revisions

**Capstone Project — National College of Science and Technology, Computer Studies Department** **Researchers:** Tristan Kent Avendaño, Noriel Phillip Sarno, Beyonce Mae Binag, John Rodmar Magracia, Jose Miguel Arceño **Date Compiled:** June 2026

---

## How to Use This Document

This file consolidates two source materials supplied by the client:

1. **`CHAP-1-3-AVENDANO-Final.docx`** — the original capstone manuscript (Chapter 1: The Problem and Its Background, Chapter 2: Review of Related Literature and Studies, Chapter 3: Technical Background).
2. **`additional_context_and_revisions.docx`** — the panel's post-defense revision notes and the team's elaborated response to those notes.

The goal is to give a single reference that shows **(a)** what was originally proposed, **(b)** exactly what the panel asked to be changed, and **(c)** what the _current, revised_ system specification looks like once those changes are folded in. Section 5 ("Consolidated System Specification") is the part most relevant for actual development — it reflects the **post-revision** state of the system, not the original draft.

Where the source material was ambiguous or under-specified, this is flagged explicitly rather than guessed at (see Section 6).

---

## Table of Contents

1. [Original Proposal Summary (Chapters 1–2)](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#1-original-proposal-summary-chapters-12)
2. [Original Technical Background (Chapter 3, as defended)](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#2-original-technical-background-chapter-3-as-defended)
3. [Panel Revision Notes (Raw, As Given)](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#3-panel-revision-notes-raw-as-given)
4. [Elaborated Revisions (Team's Response to the Panel)](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#4-elaborated-revisions-teams-response-to-the-panel)
5. [Consolidated System Specification (Post-Revision)](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#5-consolidated-system-specification-post-revision)
6. [Open Items / Needs Clarification](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#6-open-items--needs-clarification)
7. [Traceability Matrix: Panel Note → Resolution](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#7-traceability-matrix-panel-note--resolution)
8. [Reference List (from original manuscript)](https://claude.ai/chat/e0a1b44e-9b90-4740-82f2-0b2a706debd2#8-reference-list-from-original-manuscript)

---

## 1. Original Proposal Summary (Chapters 1–2)

### 1.1 Project Title

**Development of a Web-Based Ambulance Rescue Platform in the City of Dasmariñas with a Decision Support System and Mobile Application**

### 1.2 Introduction & Project Context

The proposal addresses the fragmentation of emergency medical services (EMS) in Dasmariñas City, Cavite. Currently, ambulance requests rely on conventional phone/radio calls to hospitals or the CDRRMO, which causes:

- Delays in emergency response
- Inaccurate or unclear patient location reporting
- Lack of status updates for waiting families
- Inefficient use of available ambulances

The system is conceived as three integrated components:

- **Web-based platform** — the "central nervous system" for dispatchers and hospital administrators. Gives a real-time citywide map of ambulance locations and emergency requests, and lets hospitals report bed/capacity availability.
- **Decision Support System (DSS)** — the analytical layer. Scores available ambulances using variables such as traffic congestion (e.g., along Aguinaldo Highway), patient condition, and receiving-hospital capability, to recommend the best unit/destination.
- **Mobile Application** — the citizen-facing interface. Core function is a one-tap SOS that auto-shares GPS location, plus real-time status/ETA updates and storage of basic medical information for responding paramedics.

### 1.3 Statement of the Problem

Ambulance rescue operations are fragmented due to the lack of a centralized, real-time, intelligent management system. Specifically, the study investigates:

- Challenges/inefficiencies from manual and traditional dispatch communication
- Impact of lacking real-time tracking/monitoring on response time
- Causes of delay in dispatching the nearest, most appropriate ambulance
- Impact of lacking decision support on route optimization

### 1.4 Objectives

**General:** Develop a centralized, real-time, intelligent ambulance rescue management solution to improve dispatch efficiency, inter-agency coordination, and resource utilization — reducing response time and improving patient outcomes.

**Specific:**

1. Identify/analyze inefficiencies from traditional dispatch communication methods.
2. Develop a real-time tracking and monitoring solution.
3. Develop intelligent dispatch logic to assign the nearest ambulance.
4. Develop intelligent route optimization.
5. Improve coordination among ambulance services, hospitals, and responders via unified communication.

### 1.5 Original Scope and Limitations

- Web platform for admins/dispatchers: ambulance management, real-time tracking, emergency request handling.
- DSS recommends dispatch based on **proximity and urgency**.
- Mobile app (citizens): request services, auto-share location, track status, receive arrival notifications.
- Mobile app (drivers): communication and navigation to emergency sites.
- **Limitations (as originally written):**
    - Geographically limited to Dasmariñas City only.
    - Not applicable to medical diagnosis, treatment procedures, or hospital management.
    - Not integrated with police, fire department, or emergency hotlines.
    - Dependent on internet availability for real-time functions.
    - DSS logic is rule/data-bound and cannot account for unpredictable events (road closures, weather, unexpected traffic).

> **Note:** Section 5 below shows how the panel revisions tighten and partly change these boundaries (e.g., explicit domain limits, explicit infrastructure dependency framing).

### 1.6 Significance of the Study

Benefits identified for: Community/Patients, Emergency Responders/Ambulance Personnel, Hospitals and Healthcare Facilities, Public Safety/Community Awareness, Traffic Management/Navigation Efficiency, Operational Transparency/Monitoring, Communication Gap Reduction, and Technology Adoption in Healthcare.

### 1.7 Definition of Terms (as originally defined)

|Term|Original Definition|
|---|---|
|Ambulance Rescue Platform|The system managing ambulance requests and coordinating rescue operations.|
|Web-Based System|A platform accessible via browser, requiring no installed software.|
|Decision Support System (DSS)|Feature that helps responders/operators make faster, more accurate decisions (e.g., nearest ambulance, best response).|
|Mobile Application|Smartphone version for requesting services, tracking responses, receiving updates.|
|Emergency Response|Immediate actions taken when an accident/medical emergency occurs (receive request → dispatch → assist).|
|Real-Time Tracking|Live monitoring of ambulance location en route to the emergency site.|

### 1.8 Review of Related Literature — Synthesis

The literature review (local + foreign literature and studies) consistently found that:

- Philippine EMS research/infrastructure is still underdeveloped and largely descriptive rather than system-driven (Vista et al., 2023; Gundran et al., 2021; Gio et al., 2025).
- Existing Philippine mobile-first solutions (e.g., I Alerto, Reconnect, AlarmaPH, IRespond, Costales & Tanaya's app) demonstrate GPS-based alerting and routing but are mostly single-purpose, city-specific, and not built around a DSS.
- Foreign literature shows mature use of DSS, optimization algorithms, GIS, and even ML/DL (decision trees, SVM, CNN) for ambulance dispatch and relocation (Carvalho & Captivo, 2025; Gift et al., 2025; Slater, 2023; Selvan et al., 2025; Almalki et al., 2025; Avramidis & Pirkul, 2022; Chao et al., 2025; Sutherland & Chakrabortty, 2023; Alamri, 2023; Hajiali et al., 2022; Utami & Ramdani, 2022).
- **Conclusion drawn by the authors:** there is a clear capability gap between local and foreign EMS systems, which justifies building a web-based ambulance rescue platform with an integrated DSS and mobile app for Dasmariñas City specifically.

---

## 2. Original Technical Background (Chapter 3, as defended)

> This section documents what was actually presented at defense — i.e., the system **before** the panel's revisions. It is preserved here for traceability; **Section 5 supersedes it.**

### 2.1 Organizational Chart

Figure 1 — Organizational chart of the research and development team, identifying the research leader and the rest of the team responsible for project execution.

### 2.2 System Flowchart — Role-Based UI/Flow Inventory

The defended manuscript documented flows and screens per user role. The roles, as originally structured, were:

|Role|Key Screens / Flows Documented|
|---|---|
|**User (Citizen)**|Login (email/Google), Sign-up + OTP email verification, Dashboard, Emergency Request, Incident History, Live Tracking, Medical History, Profile|
|**Driver**|Login, Sign-up + OTP, Driver Dashboard, Unit Status (available/dispatched/en route/on scene/transporting/unavailable), Current Mission, Tactical Routing, Mission Logs, Profile|
|**Hospital Staff**|Login, Sign-up + OTP, Hospital Staff Dashboard, Facility Command, Incoming Admissions|
|**Dispatcher**|Login, Sign-up + OTP, Dispatcher Admin, Command Board, Tactical Map, Live Queue, Mission Timeline, Fleet Status, Identity Profile|
|**Organization Admin**|Login, Sign-up + OTP, Org Admin Dashboard, Institution Hub, Personnel Management, Fleet Inventory, Configuration, Identity Profile|
|**Admin**|Login, Sign-up + OTP, Admin Dashboard, Institution Hub, Personnel Management, Fleet Inventory, Configuration, Identity Profile|
|**Super Admin**|Login, Sign-up + OTP, Super Admin Dashboard (Dashboards/Active Schools/Audit Logs), Executive Overview, Live Operations, Fleet Governance, Trust & Safety, Audit Stream, Registration Review, Archive Registry, System Configuration, Ads Placement, Identity Profile|

Common patterns across all roles' login/sign-up flows: dual login (Google OAuth or registered credentials), email + OTP verification on sign-up, with re-entry required on invalid credentials/expired OTP.

> **Observation:** the original defended design used **seven separate role-specific dashboards** (User, Driver, Hospital Staff, Dispatcher, Organization Admin, Admin, Super Admin) with largely duplicated login/sign-up/profile flows for each. This is one of the things the panel revisions restructure — see Section 5.2.

### 2.3 Data Flow Diagrams (as defended)

- **Level 0 (Fig. 3.1):** Comprehensive system architecture — overall elements and their interrelations.
- **Level 1 (Fig. 3.2):** Connects citizen, hospital, command center, responder, ambulance unit, and admin through the platform's main processes; shows how data is stored/used to generate reports and support decisions.
- **Level 2 — Citizen (Fig. 3.3):** Emergency request validated and recorded in the patient database; system confirms dispatch and assigns the nearest ambulance.
- **Level 2 — Ambulance processing (Fig. 3.5):** Citizen details validated, processed through rescue assignment, and recorded; ensures secure transaction handling and unit assignment.
- **Level 2 — DSS (Fig. 3.6):** Transaction/incident data collected, processed, and analyzed to generate insights and reports for administrators.

### 2.4 System Architecture (Fig. 4)

Overall architecture diagram depicting the components and interactions between the web platform, mobile application, DSS, and database layer, across citizen, driver, dispatcher, and hospital-staff touchpoints.

---

## 3. Panel Revision Notes (Raw, As Given)

These are the unedited revision points the panel gave after the defense (preserved verbatim from the client's notes, for traceability):

- Heatmap feature for the DSS
- Organization classification
- Simplify the process
- Hierarchy of [roles/admin levels]
- Remove conditions
- DILG (Department of the Interior and Local Government)
- Schedule of dispatch
- Scheduled ambulance for transport of patients
- Emergency vs. non-emergency services
- Info needed for verification of each organization
- Removal of longitude and latitude in registration
- Fix of terminologies
- Dynamic roles per organization
- Sustainability (ad placement, non-obstructive, especially during emergencies)
- Distance (nearest availability)
- LGU (Local Government Unit)
- City Health Office

---

## 4. Elaborated Revisions (Team's Response to the Panel)

This is the team's fleshed-out response that translates the raw notes above into concrete system behavior. It is organized exactly as the client structured it in their notes document.

### 4.1 Project Identity & Scope Boundaries (Revised)

- **Geographic Limit:** Strictly bounded to **Dasmariñas City, Cavite**, explicitly mapping key arteries (Aguinaldo Highway, Governor's Drive, Congressional Avenue).
- **Domain Limits:** Strictly emergency medical routing and ambulance fleet dispatch. **Explicitly out of scope:** medical diagnostics, hospital bed management, fire/police department integration.
- **Infrastructure Dependency:** System explicitly requires active internet connectivity for near real-time tracking and DSS functions.

### 4.2 The 4-Tier Administrative Hierarchy

This directly addresses the panel's "hierarchy" note and restructures the original 7-dashboard model into four tiers:

|Tier|Who|Responsibilities|
|---|---|---|
|**1. Super Admin**|System Developer (the dev team)|Root access to codebase/databases. Read-only global health logs for troubleshooting. All sensitive citizen/medical data fully encrypted and masked for privacy compliance.|
|**2. Platform Executive Admin**|LGU Dasmariñas / City Controller (CDRRMO / City Health Office)|City-wide operational authority. Reviews/approves registering organizations, adjusts global system variables (e.g., DSS timeout countdown), monitors city-wide performance metrics.|
|**3. Organization Admin (Tenant Admin)**|Managers of local stations (e.g., Barangay Salitran Rescue, DLSUMC, Philippine Red Cross)|Manage own staff, configure a **Dynamic RBAC panel** to create custom roles for their organization.|
|**4. Field / Operation Users**|Custom roles created by the Tenant Admin (e.g., Dispatcher, Driver, Medic)|Hold granular, atomized permissions (e.g., `accept-incident`, `manage-fleet`).|

This addresses several panel notes at once: **hierarchy**, **dynamic roles per organization**, **LGU**, and **City Health Office**.

### 4.3 Multi-Tenant Onboarding & Fleet Logging

This addresses **organization classification** and **info needed for verification of each organization**:

- **Document Verification Workflow:** Organizations sign up via the web → enter a **Pending** state → must upload official legal documents (Barangay Council Resolutions, SEC registration, or DOH licenses) → manually reviewed and activated by the LGU (Platform Executive) Admin.
- **Ambulance Tiers** _(marked "tentative — if applicable" in the source)_: Fleet assets registered with official DOH credentials, categorized as:
    - **Type 1:** Basic Life Support (BLS)
    - **Type 2:** Advanced Life Support (ALS)
    - Plus checkable asset lists (e.g., `has_ventilator`, `has_ob_kit`).

### 4.4 Core System Features & Logic

**A. The Emergency Dispatch Workflow** — this is the direct response to the **heatmap**, **distance/nearest availability**, and **simplify the process** notes:

1. **Heatmap Aggregation:** When a crisis occurs, the system automatically merges separate citizen reports within a localized radius (**50m–150m**) into a single **"Master Incident Ticket"** to prevent duplicate fleet dispatches.
2. **The Headless DSS Engine:** On incident trigger, the backend queries all **Idle** ambulances and filters/scores them by case urgency, equipment tier, and **traffic-adjusted travel time** to determine the best unit.
3. **The "Automatic Throw":** A pop-up alert is pushed to the chosen organization's dispatch screen with a countdown (**30–90s**). If accepted, the case locks to that unit. If it times out, the headless DSS automatically reassigns the ticket to the next best-ranked team.

**B. Driver & Navigation Flow:**

- **Lightweight Mobilization:** On accepting a case, the driver's app receives a silent WebSocket token with destination coordinates and renders a simple Mapbox route.
- **Deep-Link External Navigation:** To avoid building expensive in-app turn-by-turn navigation, a "Navigate" button uses an OS `geo:` intent to hand off to **Waze or Google Maps** natively for actual turn-by-turn guidance.

**C. The Citizen Experience:**

- **Unified Tracking UI:** Both registered and guest users see the same real-time tracking screen (Mapbox ETA, vehicle plate number, crew details, vehicle tier badge).
- **Native Dialer Shortcut:** A "Call Driver" button uses the device's native `tel:` URI scheme rather than building in-app chat/calling infrastructure.

### 4.5 Anti-Abuse & Violation Rules

- **Verification Protocols:** Citizens cannot silently cancel/"ghost" an ambulance mid-route — cancellation requests are flagged **Pending** until the field driver/dispatcher visually inspects or manually resolves the scene.
- **Hardware Strike Restrictions:** The system tracks unique hardware device tokens (UUIDs) alongside registered IDs. **3 false alarms/pranks within a month** → system either disables the guest one-tap feature for that device, or attaches a "High Risk / False Alarm History" warning label to future dispatches for LGU review.

### 4.6 Sustainability

Directly addresses the panel's **sustainability / ad placement / non-obstructive** note:

- Non-obstructive ad placement within the platform (must not interfere with emergency-critical UI).
- Optional revenue paths: donation/fundraising features, or government-funded operation.

### 4.7 Items Mentioned But Not Elaborated in the Source

The notes list a few items that were **named but not expanded on** in the additional-context document:

- **Removal of longitude/latitude in registration** — listed only as a bullet; no replacement mechanism (e.g., pin-drop on map, address autocomplete, geocoding) was specified.
- **Schedule of dispatch / scheduled ambulance for transport of patients** and **emergency vs. non-emergency services** — listed only as bullets; the distinction between emergency dispatch and scheduled/non-emergency patient transport service is not yet defined in terms of workflow.
- **Fix of terminologies** — no specific terminology corrections were listed; this appears to be a general consistency pass across the manuscript and system labels.
- **DILG** — named as a keyword (alongside LGU and City Health Office) but its specific role/touchpoint in the system was not detailed.
- **"Remove conditions"** — ambiguous; not elaborated anywhere in the source notes (see Section 6).

---

## 5. Consolidated System Specification (Post-Revision)

This section merges Sections 1–4 into the **current intended system** — i.e., what should actually be scoped for development going forward.

### 5.1 System Identity

A web-based ambulance rescue platform for **Dasmariñas City, Cavite only**, combining:

- A **multi-tenant web platform** for the LGU and partner emergency-response organizations,
- A **headless Decision Support System (DSS)** for dispatch scoring and auto-assignment,
- A **citizen-facing mobile app** for SOS requests and live tracking,
- A **driver-facing mobile app** for mission handling and external (Waze/Google Maps) navigation.

**Out of scope (explicit):** medical diagnostics, hospital bed management, integration with police/fire/other emergency hotlines. **Hard dependency:** active internet connectivity (no offline mode for tracking/DSS).

### 5.2 Revised Role & Access Model

Replaces the original 7-dashboard design with a 4-tier RBAC model:

```
Super Admin (Dev Team)
   └── Platform Executive Admin (LGU Dasmariñas — CDRRMO / City Health Office)
          └── Organization Admin (Tenant Admin per partner org)
                 └── Field / Operation Users (Dispatcher / Driver / Medic — dynamic, org-defined)
```

Citizens/end-users sit outside this admin hierarchy as the consumer-facing layer (mobile app), interacting with whichever Organization/Field user is assigned to their incident.

> **Migration note for the dev team:** the original manuscript's "Dispatcher," "Hospital Staff," and "Organization Admin/Admin" dashboards map roughly onto **Organization Admin** + **Field/Operation User** roles in the new model, while "Super Admin" in the original maps onto the new **Platform Executive Admin** (LGU) tier — with a _new_, separate **Super Admin** tier introduced above it for the dev team's own root/infra access. This remapping should be confirmed with the client/panel before finalizing role permissions, since the original document did not explicitly state this correspondence.

### 5.3 Organization Onboarding (Revised)

1. Organization registers via web → status = **Pending**.
2. Uploads verification documents: Barangay Council Resolution, SEC registration, **or** DOH license (classification depends on org type — government/barangay rescue unit, private hospital, NGO, etc.).
3. Platform Executive Admin (LGU) manually reviews and activates.
4. Once active, Organization Admin registers fleet assets:
    - DOH credential reference number
    - Tier classification: **Type 1 (BLS)** or **Type 2 (ALS)**
    - Equipment checklist (`has_ventilator`, `has_ob_kit`, etc.)
5. **Location capture during registration no longer uses manual latitude/longitude entry** (panel revision). _Replacement input method (map pin-drop vs. address geocoding) still needs to be decided — see Section 6._

### 5.4 Emergency Dispatch Logic (Revised)

1. Citizen sends SOS (or scheduled/non-emergency request — distinction still pending, see Section 6) → GPS auto-attached.
2. **Heatmap Aggregation:** overlapping reports within 50–150m merge into one Master Incident Ticket.
3. **Headless DSS** scores all Idle ambulances on: case urgency, equipment tier match, and traffic-adjusted travel time (distance/"nearest availability" factored in, not just raw distance).
4. **Automatic Throw:** top-ranked org/unit gets a 30–90s accept window. No response → ticket auto-passes to next-ranked unit.
5. On accept: driver app gets a silent WebSocket push with destination + Mapbox route; "Navigate" button deep-links to Waze/Google Maps for actual turn-by-turn.
6. Citizen sees unified tracking screen (ETA, plate number, crew, tier badge) regardless of guest/registered status; can call driver via native `tel:` link.
7. Cancellation requests are not honored instantly — flagged Pending until field-verified.

### 5.5 Trust, Safety & Anti-Abuse (Revised)

- Device-level UUID tracking in addition to account IDs.
- 3 false alarms in a 30-day window → disable guest one-tap **or** flag account/device as High Risk for LGU review.

### 5.6 Monetization / Sustainability (Revised)

- Ads allowed but must be non-obstructive and not appear during active emergency flows.
- Alternate/parallel funding paths: donations, fundraising, or direct government funding — to be decided per deployment, not hard-coded into the architecture.

---

## 6. Open Items / Needs Clarification

The following panel notes are **not yet fully specified** in the source materials and should be confirmed with the client/panel before being treated as final requirements:

|Item|What's Unclear|
|---|---|
|**"Remove conditions"**|No elaboration anywhere in the source. Could mean removing certain medical pre-condition fields from citizen registration, removing eligibility conditions for guest dispatch, or something else entirely. **Needs direct confirmation from the client.**|
|**Lat/long removal — replacement method**|Confirmed that manual lat/long entry is removed from registration, but no replacement (map pin-drop, address autocomplete, automatic device geocoding) is specified.|
|**Scheduled / non-emergency dispatch**|Confirmed as a distinct service type from emergency dispatch, but no workflow, approval process, or scheduling UI has been described yet.|
|**DILG's specific role**|Listed alongside LGU and City Health Office as a keyword; unclear whether DILG is a reporting/compliance stakeholder, a verification authority for organization onboarding, or something else.|
|**"Fix of terminologies"**|No specific terms were flagged; recommend asking the panel which terms specifically were considered inconsistent or incorrect.|
|**Org-vs-field role mapping**|The mapping in Section 5.2 (Migration note) is this compiler's best inference from comparing both source documents — it was not stated explicitly by the client and should be verified.|

---

## 7. Traceability Matrix: Panel Note → Resolution

|Panel Note (Raw)|Addressed In|Status|
|---|---|---|
|Heatmap feature for DSS|§4.4-A, §5.4 step 2|Resolved — Master Incident Ticket logic defined|
|Organization classification|§4.3, §5.3|Resolved — verification doc type + ambulance tier|
|Simplify the process|§4.2 (role hierarchy), §4.4 (dispatch flow)|Resolved — 4-tier model + streamlined dispatch flow|
|Hierarchy of [roles]|§4.2, §5.2|Resolved — 4-Tier Administrative Hierarchy|
|Remove conditions|—|**Unresolved — see §6**|
|DILG|§4.2 (implied under LGU oversight)|**Partially resolved — see §6**|
|Schedule of dispatch|§4.7|**Named but not designed — see §6**|
|Scheduled ambulance for transport of patients|§4.7|**Named but not designed — see §6**|
|Emergency vs. non-emergency services|§4.7|**Named but not designed — see §6**|
|Info needed for verification of each organization|§4.3|Resolved — document list defined|
|Removal of longitude/latitude in registration|§4.7, §5.3 step 5|**Partially resolved — replacement method open, see §6**|
|Fix of terminologies|§4.7|**Unresolved — see §6**|
|Dynamic roles per organization|§4.2 (Field/Operation Users)|Resolved|
|Sustainability (ad placement)|§4.6, §5.6|Resolved|
|Distance (nearest availability)|§4.4-A (Headless DSS Engine)|Resolved — factored into DSS scoring, not raw distance alone|
|LGU|§4.2 (Platform Executive Admin)|Resolved|
|City Health Office|§4.2 (Platform Executive Admin)|Resolved|

---

## 8. Reference List (from original manuscript)

_Local Literature:_ Vista et al. (2023); Gundran et al. (2021); Gio et al. (2025); Reyes & Cruz (2022); Dela Cruz et al. (2024).

_Foreign Literature:_ Carvalho & Captivo (2025); Gift, Oladele & Carbell (2025); Slater (2023); Selvan et al. (2025); Almalki, Aldhahri & Aljojo (2025).

_Local Studies:_ Goh et al. (2023) — _I Alerto_; Padilla (2026) — _Reconnect_; Basabe et al. (2023) — _AlarmaPH_; Lagman et al. (2022) — _IRespond_; Costales & Tanaya (2022).

_Foreign Studies:_ Avramidis & Pirkul (2022); Chao, Hu & Zheng (2025); Sutherland & Chakrabortty (2023); Alamri (2023); Hajiali et al. (2022); Utami & Ramdani (2022).

_Full citations with DOIs/links are preserved in the original manuscript's References section and were intentionally omitted here to avoid duplicating large reference blocks; refer to `CHAP-1-3-AVENDANO-Final.docx` for the complete, formatted reference list._

---

_Compiled from `CHAP-1-3-AVENDANO-Final.docx` and `additional_context_and_revisions.docx`. This document is a synthesis for development planning purposes and does not replace the official capstone manuscript on file with the panel._