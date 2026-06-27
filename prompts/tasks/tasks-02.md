# Objective
## Read Docs and Follow the Instructions and Roadmap

---

## Description
Read the documentation located in the specified docs paths and follow the instructions and roadmap they contain. The step-by-step process in the Development Build Guide should not be one-shotted. The project already has an existing database, and migration from other tasks follows the same process with improvements. The phases to implement are S1 (Authentication Page), S2 (RBAC Core, before any admin side), and S3 (Super Admin Side, built module by module). Since there is no data yet, a seeder should be used for user accounts and their roles, with the accounts provided so they are known. After implementation, a summary covering every implemented detail must be provided in .md format at the specified location.

---

## Primary Objective
Read the docs and follow the instructions and roadmap, implementing phases S1, S2, and S3 step by step rather than one-shotting the tasks.

---

## Secondary Objectives
- Apply database migration improvements consistent with the existing database and prior task processes.
- Use a seeder for user accounts along with their roles, since there is no data yet, and provide the accounts so they can be known.
- Provide a summary (every detail that has been implemented, e.g. flow, process, accounts, etc.) in .md format at prompts\tasks\summary after implementation.

---

## Supporting Tasks

### Docs to Read
- docs\MIGRATION
- docs\ROADMAP
- docs\DOCUMENT ARCHITECTURE.md
- docs\INFORMATION CONTEXT.md
- docs\MAJOR TASKS.md
- docs\SYSTEM ANALYSIS REPORT.md

### Roadmap Step-by-Step
- Follow the step-by-step process in docs\ROADMAP\DEVELOPMENT BUILD GUIDE.md
- Do not one-shot the tasks

### Database Structure
- Use the already existing database, since the project already exists
- Perform migration from the other tasks
- Keep the process the same, with improvements

### Phases
- S1 — Authentication Page
- S2 — RBAC Core (before any admin side)
- S3 — Super Admin Side (build module by module)

### Seeder and Accounts
- Since there's no data yet, use the seeder for the user accounts along with their roles
- Provide the accounts so they can be known

### Post-Implementation
- After the implementation, provide a summary in .md format at prompts\tasks\summary
- Include every detail that has been implemented (e.g. flow, process, accounts, etc.)