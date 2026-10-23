# Skooly Final Submission Guide

## System overview

Skooly is a single-school Laravel application for academic setup, family and student
registration, fee collection, school events, payment reminders, promotion, and audit
history. It is prepared for a **local demonstration** with deterministic fictitious
data. It is not presented as production-ready.

## Completed modules

- Authentication, email verification, password reset, and authenticator-app two-factor
  authentication.
- Academic years, terms, grades, sections, subjects, archive/restore, and active-year
  settings.
- Users, fixed roles, Teacher qualifications, teaching assignments, and class-in-charge
  assignments.
- Families, Guardians, Students, explicit Guardian–Student links, enrollments, and
  placement history.
- Fee categories and structures, discounts, subscriptions, recurring/event due
  generation, dashboard reporting, payments, receipts, and append-only corrections.
- Events, promotion drafts and confirmation, reminder outbox records, and audit logs.
- Admin, Guardian, and Teacher portals with role- and record-scoped access.

## Stack

- PHP 8.4 and Laravel 13
- MySQL for the local application database; SQLite is used by the automated tests
- Blade, local CSS/JavaScript, Vite, Pest, PHPStan, Pint, and Composer Audit

## Local startup

Follow [Local Demo Setup](local-demo.md) for requirements and demo accounts. For an
existing local database, do not reset it. Use:

```bash
php artisan migrate --no-interaction
php artisan db:seed --class=DemoDataSeeder --no-interaction
php artisan serve
```

Open the local URL reported by Artisan (normally `http://localhost:8000`). The demo
seeder is idempotent and refuses to run in production. Do not use destructive reset
commands for a demonstration database with records you need to retain.

## Demonstration sequence

1. **Admin:** sign in using the Admin account documented in `local-demo.md`, open the
   dashboard, Student Directory, a Family and its Guardians, academic setup, Teacher
   assignments, fees/dues, an existing payment/receipt, and the reminder outbox.
2. **Admin:** open the existing promotion draft and the audit-log index. Use create/edit
   pages to explain workflows; avoid confirming promotion, recording a new payment, or
   generating duplicate operational records during a short demonstration.
3. **Guardian:** sign in using the linked Guardian account from `local-demo.md`. Show
   the linked students, enrollment history, dues, and allocation-scoped payment history.
   Explain that unlinked students are excluded by explicit Guardian–Student links.
4. **Teacher:** sign in using the Teacher account from `local-demo.md`. Show My Classes,
   My Subjects, My Students, and an authorized student academic profile. Explain that
   other sections and Guardian financial data are not available.

## Automated verification

Latest verification passed:

- `php artisan test --compact` — 585 tests, 2,904 assertions
- PHPStan — 0 errors
- Pint — passed
- Composer audit — no security advisories
- `npm run build` — passed (optional Fontaine warning only)
- `git diff --check` — passed

## Intentionally unavailable or limited

- External reminder email delivery is not enabled. Local delivery is limited to Laravel's
  log or array mailer; queued transport acceptance does not prove inbox delivery.
- Payment refunds, direct payment deletion, and historical-record deletion are not
  available. Payment corrections use the append-only reversal workflow.
- Attendance, timetables, examinations/report cards, public APIs, mobile applications,
  online payments, and multi-school tenancy are outside this scope.
- The app has no public-upload workflow, so `public/storage` is not required for this
  demonstration.
- Production deployment still requires hosting, TLS, backups, monitoring, real-user
  acceptance testing, and approved operational policies.

## Readiness assessment

Skooly is ready for the local final demonstration: migrations are applied, deterministic
demo data supports the Admin, Guardian, and Teacher flows, and the automated quality
gates pass. It remains a local/demo and internal-test-ready system, not a production
deployment.
