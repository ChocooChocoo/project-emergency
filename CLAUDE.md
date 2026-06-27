# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Functional app** — Laravel 13 + Tabler (Bootstrap 5 UI). City-scoped emergency ambulance dispatch system for Dasmariñas, Cavite ("ride-hailing for ambulances" + control room + hospital coordination). Roadmap phases **S0–S11 are implemented**: real auth (register → email OTP → approval gate → login), permission-based RBAC, and module controllers with validation, `DB::transaction`, audit logging, and tests.

Two front doors: public **guest/citizen intake** (`/request*`, no auth) and the **authenticated console** (`/dashboard` + `/admin/*`, permission-gated). Single admin console — every authed page extends `layout/admin/app.blade.php`; role differences are by permission, not separate layouts.

Real-time is **polling, not WebSockets** this phase (Reverb is the documented upgrade path). Deferred: queued auto-throw/countdown, Sanctum mobile API, Mapbox geometry, Google OAuth. See `prompts/tasks/summary/` for the full per-module process/flow docs (technical + non-technical).

## Commands

```bash
php artisan serve        # start dev server
php artisan test         # run tests
./vendor/bin/pint        # format code
php artisan pail         # tail logs
```

## Architecture

### Routing (`routes/web.php`)
Full GET/POST/PUT/PATCH routes with middleware. Controllers use `use` imports at the top (not inline FQNs). Structure:
- **Guest group** (`middleware('guest')`): login, register, verify-email, password reset — sensitive POSTs are rate-limited (`throttle:6,1` etc.).
- **Authenticated group** (`middleware(['auth', 'account.active'])`): each module nested under `can.perm:<code>` (e.g. `can.perm:manage-fleet`). Notifications need no extra permission.
- **Public intake** (no auth): `/request*` for guest/citizen emergency requests, rate-limited.

`account.active` = `EnsureAccountActive`; `can.perm:<code>` = `EnsurePermission`. Permission strings match the `@can('<code>')` directives in views — same string both places.

### Layout Trees

**Admin layout** — all dashboard/internal pages:
```
layout/admin/app.blade.php
  ├── partials/_head.blade.php     ← CSS + Tabler icons webfont CDN
  ├── partials/_sidebar.blade.php  ← dark vertical nav, Bootstrap collapse dropdowns
  ├── partials/_navbar.blade.php   ← dark/light toggle, notifications, user avatar
  └── partials/_scripts.blade.php  ← ApexCharts + tabler.min.js
```

`tabler-theme.min.js` is loaded inline in `app.blade.php` right after `<body>` (before `.page` div) to prevent theme flash.

`@yield('content')` sits directly inside `<div class="page-wrapper">` — pages are responsible for their own `page-header`, `page-body`, and `container-xl` structure.

**Auth layout** — login, register, forgot-password:
```
layout/auth/app.blade.php
  ├── partials/_head.blade.php
  └── partials/_scripts.blade.php  ← tabler.min.js only
```
Pages extend `layout.auth.app` with `@yield('logo')` and `@yield('content')`.

### Assets

```
public/tabler/css/tabler.min.css       ← main stylesheet
public/tabler/js/tabler.min.js         ← main JS
public/tabler/js/tabler-theme.min.js   ← dark/light theme toggle (loaded early in body)
public/tabler/libs/apexcharts/         ← charts (loaded globally in _scripts)
public/tabler/libs/jsvectormap/        ← maps (loaded per-page via @push('styles'/'scripts'))
```

Tabler Icons webfont (loaded in `_head.blade.php` via CDN):
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont/dist/tabler-icons.min.css"/>
```
Use as `<i class="ti ti-{icon-name}"></i>` — the sidebar uses icon font only, no inline SVGs.

### Feedback Modal (SweetAlert alternative)

One universal modal — the default feedback/confirm mechanism. `<x-feedback-modal />` is rendered once in `layout/admin/app.blade.php`; the JS API lives in `partials/_scripts.blade.php`. Available on all admin pages, no per-page setup. All content (type, icon, color, title, message, buttons) is set at call time:

```js
feedback.success('Saved', 'Your changes have been saved.');   // also .error/.warning/.info
feedback({ type, title, message, confirm, cancel, onConfirm }); // full form
confirmAction(() => doThing(), { type: 'danger', title: 'Delete?', confirm: 'Delete' });
```

Types: `success` `error` `danger` `warning` `info` `primary` — each maps to a status-bar color + Tabler icon + button color. Match the type to the action's intent (danger action → `danger` confirm). Modals open via Tabler's data-API (`tabler.min.js` does not expose `window.bootstrap`). Wired into both the admin and auth layouts (`<x-feedback-modal />` is in each).

### Template Reference Files (`template/`)

Over 100 browser-runnable HTML files from the Tabler preview site. These are the **design reference and source of truth** — always consult them when building new pages to keep the design consistent. Asset paths point to `../public/tabler/`.

### Sidebar Navigation Pattern

Collapsible items use Bootstrap collapse:
```html
<li class="nav-item">
    <a class="nav-link" href="#navbar-{id}" data-bs-toggle="collapse" role="button">
        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-{icon}"></i></span>
        <span class="nav-link-title">Label</span>
        <span class="nav-link-arrow ms-auto"><i class="ti ti-chevron-down"></i></span>
    </a>
    <div class="navbar-nav collapse" id="navbar-{id}">
        <a class="nav-link" href="#">Sub Item</a>
    </div>
</li>
```

### Controller & Request Conventions

- **Every POST/PUT/PATCH form** has `@csrf` and real `action="{{ route(...) }}"`; show `@error`/old input.
- **Validation:** dedicated Form Requests (`app/Http/Requests/...`) for non-trivial input; `$request->validate([...])` inline for simple ones. Never trust raw input.
- **State changes:** wrap multi-step writes in `DB::transaction(...)` and end with `AuditLog::record('<event>', Model::class, $id)`.
- **Notifications:** user-facing events go through `Notifier::send(...)` (mirrors `AuditLog::record`).
- **Archive, not delete:** set `is_archived`/`archived_at`/`archived_by`/`archive_reason` (+ `is_active=false`); User/Org/Hospital also snapshot to `archival_logs`. No Eloquent SoftDeletes. Restore clears the flags.
- **RBAC:** never check roles directly — gate routes with `can.perm:<code>` and views with `@can('<code>')`. `User::hasPermission()` + `Gate::before` resolve it. Super admin passes all gates.
- **Tenant/consistency invariants:** derive owning org from the entity (e.g. an assignment's org comes from its ambulance), never from user input.
- New pages: route (with correct middleware) → controller method → Form Request (if input) → blade view extending the appropriate layout. Run `php artisan test` — there are feature tests per module.

## UI/UX Rules (enforced — do not deviate)

### Feedback & Confirmations
Always use the SweetAlert alternative (`window.feedback` / `window.confirmAction`) for every user-facing message and action confirmation. Never use browser `alert()`, `confirm()`, or Bootstrap alert boxes. The modal is available on all admin pages via `layout/admin/app.blade.php`; it is also wired into `layout/auth/app.blade.php`. Use the correct type per intent (`success`, `warning`, `danger`, `info`, etc.).

### Badges
Always use the light style: `badge bg-{color}-lt`. Never use solid `bg-{color}` badges for status labels. Exception: the notification dot on the navbar bell icon stays solid (it is a pure indicator dot, not a label).

### Action Columns
Every table action column must use a 3-dot vertical icon (`ti-dots-vertical`) as a Bootstrap dropdown toggle (`btn-action dropdown-toggle`). Actions render as `dropdown-item` entries inside `dropdown-menu dropdown-menu-end`. The column header is `<th class="text-center">Actions</th>` and the cell is `<td class="text-center">`. Never use inline button lists in table rows.

### Table Style
All data tables must follow the datatable pattern from `template/datatables.html`:
- Wrap in `<div id="table-{name}" class="table-responsive">` inside `<div class="card-body p-0">`
- Sortable headers use `<button class="table-sort" data-sort="sort-{col}">Label</button>`
- `<tbody class="table-tbody">` with `sort-{col}` classes on each `<td>`
- Initialize with list.js per-page via `@push('scripts')`: `new List('table-{name}', { sortClass:'table-sort', listClass:'table-tbody', valueNames:[...] })`
- Load `tabler/libs/list.js/dist/list.min.js` per-page, not globally

### Design Source of Truth
Never invent or experiment with UI components. Always look up the pattern in `template/` first and port it directly. Only deviate from the template when the user explicitly requests it.

## Code Style

Lazy by default (ponytail): shortest diff that works. Reuse before adding, stdlib/native/Tabler before custom JS, one universal component before many. Delete dead examples once they've served their purpose.
