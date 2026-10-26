# System Inventory

## Runtime and delivery surface

* Laravel 13.17 (`composer.json:14`), PHP constraint `^8.3`, current project target PHP 8.4.
* Blade server-rendered UI; local static `public/css/admin.css` and `public/js/admin-ui.js`; Vite build remains present for app assets.
* MySQL is the configured default (`.env.example:27-32`); queues, cache, and sessions default to database (`config/queue.php:16`, `config/session.php:21`).
* 185 routes are declared in `routes/web.php`; no API route file is configured in `bootstrap/app.php:9-14`.
* One scheduled command: `reminders:schedule-delivery` hourly with overlap and one-server locks (`routes/console.php:5`).

## Module inventory

| SRS module | Main implementation | UI / route evidence |
| --- | --- | --- |
| M1 Academic years / terms | `AcademicYear`, `Term`, `SchoolSetting`; academic controllers | `academic-years.*`, `terms.*`, `school-settings.*` |
| M2 Grades / sections | `Grade`, `Section`, `SectionYearAssignment` | `grades.*`, `sections.*`, `section-year-assignments.*` |
| M3 Identity / teaching | `User`, `Role`, `Subject`, teacher models/policies | `users.*`, `subjects.*`, qualification/assignment routes, `/teacher/*` |
| M4 Fees | `FeeCategory`, `FeeStructure`, fee actions | `fee-categories.*`, `fee-structures.*` |
| M5 Families / guardians | `Family`, `Guardian`, guardian privacy actions | `families.*`, `guardians.*`, `/guardian/*` |
| M6 Students / enrollments | `Student`, `Enrollment`, placement history | `students.*`, registration and placement routes |
| M7 Payments | `Payment::recordManual`, allocations, reversals | `/payments/*`, family payment routes |
| M8 Events | events, charges, participation, generation | `events.*`, `due-generation.events.*` |
| M9 Discounts | `Discount`, action and lifecycle controller | student discount routes |
| M10 Recurring dues | `GenerateRecurringDueItems` | recurring generation route |
| M11 Dues dashboard | `BuildDuesDashboardReport` | `dues-dashboard.index` |
| M12 Receipts/history | receipt snapshot/PDF | `payments.*`, `receipts.*`, student history |
| M13 Reminders | outbox, delivery attempts, job/command | `payment-reminders.*` |
| M14 Audit | `RecordAuditLog`, audit export controller | `audit-logs.*` |
| M15 Promotion | draft, item editing, atomic confirm/export | `promotion-batches.*` |

## Roles discovered

| Principal | Source | Effective workspace |
| --- | --- | --- |
| Superadmin | `Role::SUPERADMIN`; `User::homeRouteName()` | user administration; can also hold Admin |
| Admin | role middleware | all Stage 1 operational/configuration pages |
| Accountant | role + financial policies | constrained collection, payment/receipt/reversal history, dashboard, reminder read access |
| Teacher | role middleware + portal controller | assigned class/subject/student directory pages |
| Guardian-linked User | nullable `users.guardian_id` | explicitly linked students, dues, payment allocations |
| Unroled authenticated user | auth only | profile/security; no business workspace |

Guardian links are explicit (`GuardianPortalController.php:39`, `ListGuardianVisibleStudents`); family membership does not grant sibling access.

## Supporting implementation assets

* 53 migrations, factories for major models, `DatabaseSeeder` and production-guarded demo seed data.
* 41 actions; no events/listeners/observers/notification classes were present in application directories at audit time. Mail classes are `GuardianEmailVerificationMail` and `PaymentReminderEmail`; queued reminder delivery is `DeliverPaymentReminderEmail`.
* Private student photos use the local disk and an Admin-only controller route (`StudentController.php:83-91`); public storage was not evidenced.
* Documentation includes installation, demo, deployment, security, workflow, data-flow, and delivery-operation guides.

## Inventory limitations

The historic `docs/audits/*` reports named in the audit request are deleted in the working tree. They were not treated as evidence. The current untracked `docs/commercial-audit/evidence` assets and `CommercialAuditEvidenceTest` were preserved and independently executed.
