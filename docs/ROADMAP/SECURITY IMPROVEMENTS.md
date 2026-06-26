# Security Improvements

*Common, sensible security for a capstone build on Laravel MVC. Scope is intentionally
**basic, not enterprise** — covers the usual web-app risks without complex measures.
Generated 2026-06-25.*

> **Guiding rule:** prioritize the common essentials only. No advanced/expensive security
> (no HSM, SIEM, pen-test program, zero-trust, etc.) — this is a student capstone.

---

## 1. What Laravel Gives for Free (just use it)

| Protection | How |
|------------|-----|
| **CSRF** | `@csrf` in every Blade form; Laravel verifies automatically |
| **SQL injection** | Use Eloquent / query builder bindings — never raw concatenated SQL |
| **XSS** | Use `{{ }}` (auto-escaped) in Blade; avoid `{!! !!}` unless sanitized |
| **Password hashing** | `Hash::make` / bcrypt by default — never store plaintext |
| **Session security** | Framework session handling; set secure cookie flags in `.env` |
| **Mass assignment** | Define `$fillable` on every model |

These cost nothing — the rule is simply *don't bypass them*.

---

## 2. Common Essentials to Add

### 2.1 Authentication
- Email **OTP verification** before an account becomes active (already in the spec).
- Password reset via signed, expiring links/codes.
- **Rate limiting** on login, OTP, and password-reset routes (Laravel throttle middleware)
  to stop brute force and OTP spam.
- Reasonable password rule (min length); lock/throttle after repeated failures.

### 2.2 Authorization (most important for this system)
- **Role/permission checks on every action** via Policies/Gates (spatie).
- **Tenant isolation:** every org-scoped query filtered by `organization_id` so one
  organization can never see/modify another's data.
- Server-side authorization always — never trust the UI to hide a button.

### 2.3 Input Validation
- **Form Request validation on every write endpoint** (type, range, required fields).
- Validate file uploads: allowed types (images/PDF), max size, store on a **private disk**,
  never in the public web root.

### 2.4 Sensitive Data
- Keep secrets in `.env` (never commit it).
- Serve uploaded IDs/medical docs only through an **authorized controller**, not a public URL.
- Mask/limit exposure of personal and medical data to only the roles that need it.
- Use **HTTPS** in deployment.

### 2.5 Anti-Abuse (from the spec)
- **Device UUID strike tracking:** 3 false alarms in 30 days → disable guest one-tap for
  that device or flag it for LGU review.
- **Cancellations are not silent:** flagged Pending until field-verified.

### 2.6 Auditing
- Log critical actions (login, approvals, status changes, deletions) to `audit_logs`.
- Keep error logs server-side; don't leak stack traces to users (`APP_DEBUG=false` in prod).

---

## 3. Simple Checklist (per feature)

- [ ] Form has `@csrf`
- [ ] Write request has Form Request validation
- [ ] Action has a Policy/permission check
- [ ] Query is tenant-scoped where applicable
- [ ] No raw SQL with user input
- [ ] Output escaped in Blade
- [ ] Uploads validated + stored privately
- [ ] Sensitive route rate-limited

---

## 4. Explicitly Out of Scope (capstone)

To keep effort proportional, these are **not** required:
- Advanced threat monitoring / intrusion detection
- Penetration testing program
- End-to-end encryption beyond HTTPS + hashed passwords
- Multi-factor auth beyond the email OTP already specified
- Formal compliance certification

> If the panel later asks for more, these can be added — but they are not needed for the
> capstone's "common security" requirement.

---

*Companion documents: `TECHNICAL ROADMAP.md`, `NON-TECHNICAL ROADMAP.md`,
`EXISTING FEATURES + NEW FEATURES.md`, `PROCESS AND FLOW.md`.*
