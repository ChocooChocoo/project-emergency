# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Static prototype** — Laravel 13 + Tabler (Bootstrap 5 UI). No backend logic: no form processing, no auth middleware, no session handling. All routes return views only.

## Commands

```bash
php artisan serve        # start dev server
php artisan test         # run tests
./vendor/bin/pint        # format code
php artisan pail         # tail logs
```

## Architecture

### Routing (`routes/web.php`)
All routes are static — controllers only call `return view(...)`. No middleware, no POST routes. Route class references use fully-qualified inline syntax (`\App\Http\Controllers\Auth\LoginController::class`), not `use` imports at the top.

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

Types: `success` `error` `danger` `warning` `info` `primary` — each maps to a status-bar color + Tabler icon + button color. Match the type to the action's intent (danger action → `danger` confirm). Modals open via Tabler's data-API (`tabler.min.js` does not expose `window.bootstrap`). Not wired into the auth layout — add `<x-feedback-modal />` + the script there if auth pages need it.

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

### Prototype Constraints

- Forms use `action="#"`, no `@csrf`, no `@error`
- No `Auth::`, `auth()`, or middleware references
- Controllers have one method: `showForm(): View`
- New pages: route → controller method → blade view extending the appropriate layout

## Code Style

Lazy by default (ponytail): shortest diff that works. Reuse before adding, stdlib/native/Tabler before custom JS, one universal component before many. Delete dead examples once they've served their purpose.
