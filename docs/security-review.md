# Security Review

Status: reviewed at Phase 10C-2. This documents the **current** security posture,
what is deliberately not built yet, and what to do next.

## Authentication state

- Session-based authentication using Laravel's default `web` guard. No packages.
- `LoginController` regenerates the session on successful login and invalidates it
  on logout, then regenerates the CSRF token. This prevents session fixation.
- Failed logins return a single generic validation message on the `email` field, so
  the response does not reveal whether an account exists.
- No password reset flow.
- No email verification.
- No two-factor authentication.
- No `remember me`.

## Authorization state

- All admin pages are wrapped in `auth` plus the `role:Admin` middleware, registered
  as the `role` alias in `bootstrap/app.php`.
- `EnsureUserHasRole` redirects unauthenticated users to login and aborts with 403
  for authenticated users without the role.
- Web Form Requests repeat the Admin check, so protection does not depend on route
  configuration alone.
- Only three roles exist: Admin, Accountant, Teacher.
- Accountant and Teacher are **denied every admin page** today. Their fine-grained
  permissions are an open decision, not an oversight.
- Simple resource policies exist for Student, StudentDueItem, Payment, Receipt,
  PaymentReminder, and PromotionBatch, but they are not yet wired to the web layer
  beyond the role middleware.

## Guardian privacy state

- `Guardian` has no `User` link, so **no Guardian can sign in**. This is deliberate.
- Guardian access to a Student is only ever granted through an explicit
  `guardian_student` row.
- `AuthorizeGuardianStudentAccess`, `ListGuardianVisibleStudents`, and
  `ListGuardianVisibleDueItems` exist to enforce this.
- Family membership alone never grants visibility, and combined billing never
  widens it. Demo data includes a Guardian linked to only one of two siblings so
  this can be checked by hand.

## Attack surface

- No API routes and no mobile endpoints exist.
- No online payment gateway, so no card data is handled by this application.
- No SMS, WhatsApp, or email sending exists. Reminders are internal outbox records.
- No delete or destructive routes exist by design. There is no way to remove a
  family, student, event, payment, receipt, reminder, or promotion batch through the
  web UI. This protects history and is covered by tests.
- No queue jobs or scheduled tasks run.
- Money is never recalculated for reporting. The dashboard reads stored
  `StudentDueItem` snapshots.

## Data protection at rest

- Historical and financial tables are protected by the database using
  `restrictOnDelete` foreign keys rather than application code.
- Receipts, due item discounts, and audit metadata are immutable snapshots.
- Audit logs are append-only.
- Payment, promotion, and reminder generation all run inside database
  transactions, so a failure leaves no partial records and no false audit entries.

## Seeding and secrets

- `.env` is ignored by git. Only `.env.example` is tracked, and it contains
  placeholders with an empty `APP_KEY` and `DB_PASSWORD`.
- `.env.example` was corrected during this review: it now ships `DB_CONNECTION=mysql`
  with active MySQL keys, matching the actual application, and documents
  `SESSION_SECURE_COOKIE`.
- `DemoDataSeeder` returns early when `APP_ENV=production`. This is covered by a
  test that sets the environment to production and asserts nothing is created.
- `DatabaseSeeder` creates only the three roles and one Admin, is also inert in
  production, and never calls the demo seeder.
- `migrate:fresh --seed` therefore creates a usable local login without any demo
  content.

## Open risks

| Risk | Severity | Note |
| --- | --- | --- |
| No login rate limiting | High | Brute force is currently unmitigated. |
| Default demo password | High | `admin@skooly.test` / `password` must not exist in production. |
| No password reset | Medium | Recovery requires server access. |
| No email verification | Medium | Any known email with the password is accepted. |
| Coarse single-role model | Medium | Accountant and Teacher cannot use the app at all yet. |
| Receipt numbering rule unresolved | Medium | Receipt numbers are entered manually, so uniqueness is operator-controlled. |
| Automatic payment allocation unresolved | Medium | Manual only by design; no automatic strategy exists. |
| Promotion reversal safety window unresolved | Low | Reversal is deliberately not implemented. |
| No database money constraints | Low | Balances are guarded in application code, not by a check constraint. |
| No HTTPS enforcement in app | Low | Depends on `SESSION_SECURE_COOKIE=true` and the web server. |

## Recommended next security steps

1. Add login throttling and a temporary lockout on repeated failures.
2. Remove or replace the demo Admin account on any real environment, and add a
   deployment check that fails if `admin@skooly.test` exists.
3. Add a password reset flow, then email verification.
4. Decide Accountant and Teacher permissions, then replace the coarse Admin-only
   model with per-resource policies.
5. Enforce `SESSION_SECURE_COOKIE=true` in production and confirm TLS termination.
6. Add database check constraints for non-negative money columns.
7. Resolve the receipt numbering rule so numbers are generated, not typed.
