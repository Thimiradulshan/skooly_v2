# Skooly School Management System

Skooly is a Laravel 13 application for running the core office workflows of a single
school: academic setup, family and student registration, teachers, fees and dues, manual
payment collection, receipts and corrections, events, payment reminders, promotion, and
audit history.

This repository is the **Stage 1 supervisor submission**. It is prepared for a local,
synthetic-data demonstration, not for production deployment.

## Technology

- PHP 8.4 and Laravel 13
- MySQL 8 for local use; SQLite for the automated test suite
- Blade templates, plain CSS/JavaScript, and Vite
- Pest, PHPStan/Larastan, Laravel Pint, and Composer Audit

## Supported roles

| Role       | Workspace                                                        |
| ---------- | ---------------------------------------------------------------- |
| Superadmin | User-account administration                                      |
| Admin      | Full academic, registration, fee, event, promotion, and audit UI |
| Accountant | Exact-family payment collection and finance reporting            |
| Teacher    | Read-only assigned classes, subjects, and students               |
| Guardian   | Read-only portal for explicitly linked students                  |

## Stage 1 features

- Authentication with login throttling, password reset, email verification, and
  authenticator-app two-factor authentication.
- Academic years, terms, grades, sections, subjects, archive/restore, and active-year
  settings.
- Staff accounts, fixed roles, Teacher qualifications, teaching assignments, and
  class-in-charge assignments.
- Families, Guardians, Students, explicit Guardian-to-Student links, private photo
  uploads, contact overrides, enrollments, and placement history.
- Fee categories and structures, discounts, opt-in subscriptions, recurring and event
  due generation, and stored-balance reporting.
- Manual family payments with itemized allocations, immutable receipts, PDF receipts, and
  append-only reversal requests with independent Admin approval.
- Events, participation and payment-status reporting, promotion drafts and atomic
  confirmation, CSV/PDF exports, reminder records, and audit-log viewing/export.
- Admin, Accountant, Teacher, and Guardian workflows with role- and record-scoped access.

## Quick local setup

Requirements: PHP 8.4, Composer, Node.js 22 LTS, npm, MySQL 8, and Git.

```bash
git clone https://github.com/Thimiradulshan/skooly_v2.git
cd skooly_v2
composer install
copy .env.example .env
php artisan key:generate
```

Create the MySQL database, then configure `.env` and set a private demo password:

```dotenv
DB_DATABASE=skooly
SKOOLY_DEMO_PASSWORD=choose-a-private-local-password
```

Finish the local demonstration setup:

```bash
php artisan config:clear
php artisan migrate --no-interaction
php artisan db:seed --class=DemoDataSeeder --no-interaction
npm install
npm run build
php artisan serve
```

Open <http://127.0.0.1:8000>. Demo accounts are synthetic and use only the private local
password above:

| Role       | Email                    | Landing page        |
| ---------- | ------------------------ | ------------------- |
| Superadmin | `superadmin@skooly.test` | `/users`            |
| Admin      | `admin@skooly.test`      | `/admin`            |
| Accountant | `accountant@skooly.test` | `/payments/collect` |
| Teacher    | `teacher@skooly.test`    | `/teacher`          |
| Guardian   | `alice@skooly.test`      | `/guardian`         |

Never commit `.env`, credentials, database dumps, or student images.

## Documentation

- [Installation Guide](docs/INSTALLATION_GUIDE.md) — Windows/WAMP step by step.
- [User Manual](docs/user-manual/SKOOLY_USER_MANUAL.md) — role-by-role procedures.
- [Demo Accounts Setup](docs/DEMO_ACCOUNTS_SETUP.md) — safe provisioning and boundaries.
- [Local Demo Setup](docs/local-demo.md) — quick seeding notes.
- [Supervisor Handoff](docs/SUPERVISOR_HANDOFF.md) — review checklist and limitations.
- [SRS Compliance Matrix](docs/audits/SRS_COMPLIANCE_MATRIX.md)
- [Final Completeness Report](docs/audits/FINAL_COMPLETENESS_REPORT.md)

## Testing and quality

```bash
php artisan test --compact
php -d xdebug.mode=off vendor/bin/phpstan analyse --memory-limit=2G
php vendor/bin/pint --test
composer audit
npm run build
git diff --check
```

## Safety and limitations

- Demo data is local-only and contains no real personal information.
- External reminder delivery is not enabled. No email/SMS/WhatsApp is sent.
- Payment reversals use append-only approval; direct deletion/refund is not available.
- Promotion is draft-then-confirm; confirmed promotion reversal is not implemented.
- Recurring dues are generated manually; no scheduler is configured.
- Combined-payment allocation is manual; sibling discounts are not automated.
- Registration-fee automation and settlement-driven activation are incomplete.
- Production deployment, TLS, backups, monitoring, and real-user acceptance are out of scope.
- Guardian access is strictly through explicit `User`-to-`Guardian` links, never by role.
