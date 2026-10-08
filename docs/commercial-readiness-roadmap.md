# Skooly Commercial Readiness Roadmap

**Status date:** 2026-10-08  
**Current baseline:** `1a7ddb8` on `main`  
**Purpose:** A practical plan to move Skooly from a verified Stage 1 school-management application to a commercially operated system.

## 1. Executive summary

Skooly has a strong, tested Stage 1 foundation. The core Admin workflows for school setup, registration, billing, payment collection, correction, promotion, reporting, audit, and security hardening are implemented.

It is **not ready for real production data today**. The remaining work is primarily operational: production hosting and backups, real-user acceptance testing, notification delivery and scheduling, selected staff and guardian experiences, and a small set of unresolved business rules.

## 2. What is built and verified

### School setup and registration

- Academic years, terms, grades, sections, subjects, staff users, teacher qualifications, teaching assignments, and class-in-charge assignments have Admin web workflows.
- Academic years, terms, grades, and sections can be archived and restored without deleting history. Archived records are hidden only from new selections.
- The active academic year is enforced for new year-scoped workflows. Historical data remains available.
- Families, guardians, students, explicit Guardian-Student links, student enrollment placement history, discounts, and fee subscriptions are managed in the web application.

### Fees, dues, and events

- Fee categories, structures, opt-in subscriptions, and per-student discounts exist.
- Recurring and event due generation is transactional, duplicate-safe, and snapshots amounts and discounts.
- Fee structures and event charges can be corrected only before they have produced dues. Generated amounts are never rewritten.
- The dues dashboard reports stored balances without recalculating money in Blade.

### Payments, receipts, and corrections

- Manual family-level payment collection supports explicit per-due-item allocation.
- Payments, allocations, and receipts are immutable historical records.
- Payment and receipt history support search, sorting, pagination, printable views, and Admin-only PDF receipt download.
- Accountants can collect payments through a finance-only family-code entry flow; they do not receive general family/student browsing access.
- Full and itemized partial reversals are supported: Accountant requests, an independent Admin approves, exact selected allocation amounts reopen atomically, and correction receipts/audit records are created.

### Promotion, reminders, and audit

- Promotion is draft-then-confirm and atomic. Draft items can be edited for promote, retain, exclude, graduate, or a custom valid target placement. Confirmed/discarded batches are immutable.
- Payment reminders are an internal outbox with generation, preview, and cancellation. No messages are sent externally.
- Audit logs are append-only, Admin-only, filterable by action/actor/record type/date, and exportable as CSV and PDF. Retention is forever.

### Security and quality

- Login throttling, password reset, email verification, and authenticator-app TOTP two-factor authentication with recovery codes are implemented.
- TOTP secrets are encrypted; recovery codes are stored hashed and are single-use.
- Database checks protect StudentDueItem money invariants: values cannot be negative and `paid_amount + balance_amount = net_amount`.
- The latest verified baseline passed **520 tests / 2622 assertions**, PHPStan with zero errors, Pint, `npm run build`, Composer audit, and `git diff --check`.

## 3. Current roles and access

| Role | Current access |
| --- | --- |
| Superadmin | User-account management according to safeguards. |
| Admin | Full Stage 1 operational and configuration access. |
| Accountant | Finance history, receipt history, payment collection through family-code lookup, payment-reversal requests, dues dashboard, and reminder read access. Cannot approve reversals or browse student/family records. |
| Teacher | No operational web access yet. |
| Guardian | No login yet. Privacy is enforced internally by explicit Guardian-Student links. |

## 4. Production blockers: complete before real school data

### Phase P1 — Production platform and recovery

1. Provision production hosting, MySQL, domain, TLS, and process supervision.
2. Set production environment values: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, real database credentials, a real mail transport, and a real `APP_URL`.
3. Create a real verified Admin account; ensure demo accounts do not exist in production.
4. Implement automated encrypted database backups with retention.
5. Write and rehearse a restore procedure using a non-production restore environment.
6. Establish monitoring for failed jobs, failed mail, login throttling, and application exceptions.

**Definition of done:** a backup can be restored successfully, an Admin can sign in with verified email + TOTP, and a deployment/rollback rehearsal is documented.

### Phase P2 — School-user acceptance testing

1. Seed a safe staging environment with realistic but non-production data.
2. Run a staff-led workflow test: academic setup, family registration, enrollment, due generation, payment collection, partial reversal, promotion, reminders, audit review, and archive/restore.
3. Record workflow defects, unclear terminology, missing reports, and required approvals.
4. Fix only confirmed defects before launch.

**Definition of done:** school office staff sign off on daily workflows and finance staff sign off on payment/reversal controls.

### Phase P3 — Notification delivery and automation

1. Choose channels: email, SMS, WhatsApp, or a combination.
2. Choose a delivery provider and configure credentials in production only.
3. Define reminder templates, timing, retries, failed-delivery handling, and opt-out/legal requirements.
4. Add a queue worker and scheduler/cron for due generation and reminders.
5. Add delivery status tracking without changing historical reminder snapshots.

**Decision required:** delivery provider, channels, timing, consent, and retry policy.

## 5. Product completion roadmap

### Phase F1 — Guardian portal and student media

1. Add an optional Guardian-to-User link with verified email and TOTP-capable login.
2. Build Guardian pages that query only explicit Guardian-Student links.
3. Show only the Guardian's linked students and their permitted due/receipt data; never expose siblings through family billing.
4. Add private student photo upload: JPG/PNG/WebP only, size/dimension limits, generated storage names, authorization checks, and non-public delivery.

**Decision required:** guardian registration/invitation process and exact Guardian financial visibility.

### Phase F2 — Teaching configuration corrections

1. Add archive-style correction for teacher assignments and class-in-charge assignments.
2. Preserve historical assignments; never silently delete past teaching records.
3. Add audit entries for discount deactivation and subscription ending.
4. Add standalone enrollment and Guardian-Student link reports.

**Decision required:** the precise archive/replacement behavior for teaching assignments.

### Phase F3 — Operational dashboard and reports

1. Add an attention queue with explicit rules, for example overdue due items, requested payment reversals, draft promotion batches, and failed notification deliveries.
2. Add charts only after agreeing the operational questions each chart answers.
3. Add dues/report exports (CSV/PDF) using stored report values; do not recalculate money during export.
4. Add report access controls by role.

**Decision required:** dashboard attention rules and report formats.

### Phase F4 — Role refinement

1. Define per-resource Accountant permissions beyond current finance access.
2. Define Teacher section scope before exposing any student data.
3. Implement Teacher operational pages only after scope can be proven through assignments/enrollments.
4. Avoid granting Guardian or Teacher access based only on family membership or broad school role.

**Decision required:** per-resource permissions and Teacher student-data scope.

## 6. Deferred finance and academic decisions

These must not be guessed:

1. Automatic payment allocation strategy: oldest-first, even split, or another explicit rule.
2. Sibling discount rule and percentage.
3. Promotion reversal safety window and approval controls.
4. Duplicate-family detection: warning or blocking behavior.
5. Receipt numbering policy: manual controlled sequence, automatic sequence, prefixes, fiscal-year reset, and correction-number format.
6. Whether a real cash refund requires a separate payment-provider/cash-drawer workflow beyond the current accounting reversal.

## 7. Optional future modules

These are outside the current Stage 1 scope and require SRS/product definition before work starts:

- Attendance
- Timetables
- Exams, marks, report cards, and transcripts
- API/mobile applications
- Parent communication beyond payment reminders
- Online payment gateways
- Multi-school/tenant support

## 8. Recommended execution order

1. **P1: Production platform, backups, mail transport, monitoring, real Admin account.**
2. **P2: Staging UAT with school office and finance staff.**
3. **P3: Reminder delivery provider and scheduler.**
4. **F1: Guardian portal and private photo upload.**
5. **F2: Assignment correction, lifecycle audit entries, and operational reports.**
6. **F3: Attention queue, charts, and exports.**
7. **F4: Refined Accountant/Teacher permissions.**
8. Define any new major module in the SRS before implementation.

## 9. Commercial launch checklist

- [ ] Production environment has HTTPS, secure cookies, real secrets, and no demo accounts.
- [ ] A real mail provider is configured and reset/verification mail has been tested.
- [ ] At least two verified Admin accounts have enabled TOTP and stored recovery codes safely.
- [ ] Automated backups and a restore drill have passed.
- [ ] Monitoring and error alerting are active.
- [ ] School staff UAT has passed for registration, billing, collection, reversals, promotion, and audit review.
- [ ] Reminder delivery and scheduler behavior are approved and operational, or staff accept the documented manual process.
- [ ] Open business decisions are resolved or explicitly excluded from launch scope.
- [ ] Legal/privacy requirements for student data, guardian access, and notifications have been reviewed locally.
