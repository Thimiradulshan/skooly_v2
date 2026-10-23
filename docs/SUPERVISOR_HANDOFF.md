# Skooly Supervisor Handoff

## Project

Skooly is a Laravel school-management system for a single school. This submission
covers Stage 1: academic setup, family and student registration, staff and teaching
configuration, fees and dues, manual payments and receipts, reversals, events, payment
reminders, promotion, audit logs, and role-scoped portals.

Repository: <https://github.com/Thimiradulshan/skooly_v2>

## Supported roles

- **Superadmin** — account administration only.
- **Admin** — every academic, registration, finance, event, promotion, and audit workflow.
- **Accountant** — exact-family payment collection, finance history, reversals requests,
  dues reporting, and reminder viewing.
- **Teacher** — read-only assigned classes, subjects, and active-year students.
- **Guardian** — read-only, explicitly linked students, dues, and allocation-scoped
  payment history.

## Documentation

- [Installation Guide](INSTALLATION_GUIDE.md)
- [User Manual](user-manual/SKOOLY_USER_MANUAL.md)
- [Demo Accounts Setup](DEMO_ACCOUNTS_SETUP.md)
- [Local Demo Setup](local-demo.md)
- [SRS Compliance Matrix](audits/SRS_COMPLIANCE_MATRIX.md)
- [Final Completeness Report](audits/FINAL_COMPLETENESS_REPORT.md)
- [UI Acceptance Checklist](audits/FINAL_UI_ACCEPTANCE_CHECKLIST.md)

## Running the demonstration

1. Follow the Installation Guide.
2. Provision the five demo accounts with a private local password.
3. Sign in as **Superadmin** and show Users and role boundaries.
4. Sign in as **Admin** and walk the dashboard, a family, student placement, fee setup,
   dues, an existing payment and receipt, event status, promotion draft, and audit log.
5. Sign in as **Accountant** and show exact family-code collection, payment/receipt
   history, the dues dashboard, and a reversal request.
6. Sign in as **Teacher** and show assigned classes, subjects, and students.
7. Sign in as **Guardian** and show the two linked students, dues, and allocation history.
   Attempt the unlinked third student to demonstrate the privacy denial.

Credentials are supplied privately, never in the repository or this document. The
ignored handoff file is `docs/private/DEMO_CREDENTIALS.local.md`.

## Verification status

The final verification run is recorded in the submission response and in
`docs/audits/FINAL_COMPLETENESS_REPORT.md`. Automated coverage includes authentication,
role boundaries, Guardian privacy, fees, payments, reversals, promotion atomicity,
reminders, audit logs, exports, and demonstration accounts.

## Known limitations

- Registration-fee automation and settlement-driven student activation are incomplete.
- Recurring dues are generated manually; no scheduler is configured.
- Combined-payment allocation is manual; no default allocation strategy is approved.
- Sibling discounts are not calculated automatically.
- Confirmed promotion reversal is unavailable; promotion is draft-then-confirm only.
- External reminder email delivery is disabled; only local log/array transport is safe.
- Grade-specific curriculum management, attendance, timetables, examinations, online
  payments, public APIs, and mobile apps are outside Stage 1.
- Teacher and Guardian portals are intentionally read-only where documented.
- Production deployment, TLS, backups, monitoring, and real-user acceptance are outside
  this local submission.

## Unresolved product decisions

- Partial combined-payment allocation strategy.
- Sibling discount percentage and eligibility rule.
- Promotion reversal safety window.
- Duplicate-family detection: warn or block.
- Registration-fee rules and when a pending student becomes active.
