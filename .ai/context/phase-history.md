# Phase History

## Phase 1: Academic Foundation
Status: complete.

## Phase 2: Users, Roles & Teachers
Status: complete.

## Phase 3: Families & Guardians
Status: complete.

Includes:
- Family model
- Guardian model
- families migration
- guardians migration
- FamilyGuardian feature tests

## Phase 4: Students & Enrollments
Status: complete. Verified on 2026-09-30.

Includes:
- Student model and factory
- Enrollment model and factory
- EnrollmentPlacement model and factory
- students migration
- guardian_student migration
- enrollments migration
- enrollment_placements migration
- explicit Guardian-to-Student relationships
- year-specific Enrollment records
- transactional placement history via Enrollment::placeIn()
- database constraints for admission_no uniqueness, annual enrollment uniqueness, and grade-section validity
- Phase 4 feature tests

## Phase 5: Fees, Dues & Discounts
Status: complete. Verified on 2026-09-30.

Includes:
- FeeCategory, FeeStructure, StudentFeeSubscription, Discount, StudentDueItem, and DueItemDiscount models and factories.
- fee_categories, fee_structures, student_fee_subscriptions, discounts, student_due_items, and due_item_discounts migrations.
- Student-level fee due and discount snapshot foundations.
- Fee structure versioning and per-academic-year subscription uniqueness.
- Phase 5 feature tests.

Deferred:
- Scheduled recurring due generation
- Payment-driven student registration activation
- Dashboards
- Reminders
- Events
- Attendance
- UI/controllers/routes
- Photo upload behavior
- Promotion
- Audit

## Phase 6: Payments & Receipts
Status: complete. Verified on 2026-09-30.

Includes:
- Payment, PaymentAllocation, and Receipt models and factories.
- payments, payment_allocations, and receipts migrations.
- paid_amount, balance_amount, and status on student_due_items.
- Manual payment allocation with DB transaction.
- Receipt snapshot with family, payment, and allocation details.
- Phase 6 feature tests.

## Phase 7A: Recurring Fee Due Generation
Status: complete. Verified on 2026-09-30.

Includes:
- app/Actions/Fees/GenerateRecurringDueItems.php.
- FeeCategory.is_opt_in flag, default false.
- Generation from recurring FeeStructures for students enrolled in the same year and grade.
- Active, in-date-range discount application with DueItemDiscount snapshots.
- Deterministic generation_key duplicate prevention.
- Phase 7A feature tests.

Deferred:
- Scheduled automation or cron command (not implemented in 7A)
- Automatic payment allocation
- Automatic sibling discount rule
- Payment-driven student registration activation
- Family combined billing aggregation
- Frontend dashboards
- Event notifications
- UI/controllers/routes
- Promotion
- Audit

## Phase 9A: Authorization & Guardian Privacy Hardening
Status: complete. Verified on 2026-09-30.

Includes:
- User::hasAnyRole() alongside the existing hasRole().
- StudentPolicy, StudentDueItemPolicy, PaymentPolicy, ReceiptPolicy, PaymentReminderPolicy, and PromotionBatchPolicy.
- Admin manages all covered records; Accountant manages finance records; Teacher is denied everywhere.
- AuthorizeGuardianStudentAccess, ListGuardianVisibleStudents, and ListGuardianVisibleDueItems.
- guardian_student enforced as the only Guardian access rule, with family membership and combined billing never sufficient.
- Phase 9A feature tests.

Deferred:
- Audit logs (Phase 9B)
- Guardian login and a Guardian-to-User link
- Teacher section-scoped student access
- Accountant student visibility decision
- Promotion reversal (safety window unresolved)
- CSV/PDF export
- Class-in-charge reassignment
- Scheduled automation or cron command
- Automatic payment allocation
- UI/controllers/routes

## Phase 9B: Audit Logs
Status: complete. Verified on 2026-09-30.

Includes:
- AuditLog model and factory.
- audit_logs migration with actor, action, polymorphic auditable, metadata, and occurred_at.
- app/Actions/Audit/RecordAuditLog.php as the single append-only writer.
- Payment and payment allocation audit entries.
- Promotion batch creation and confirmation audit entries.
- Recurring, event, and reminder generation audit entries, one per run.
- Phase 9B feature tests.

Deferred:
- Audit UI, audit browse screens, and CSV/PDF export
- Audit entry update or delete workflows
- student_registered, family_created, family_updated, and discount_applied integration, pending those workflows existing
- Guardian login and a Guardian-to-User link
- Teacher section-scoped student access
- Accountant student visibility decision
- Promotion reversal (safety window unresolved)
- Scheduled automation or cron command
- Automatic payment allocation
- UI/controllers/routes

## Phase 9C: Backend Hardening & Final Foundation Review
Status: complete. Verified on 2026-09-30.

Focus:
- Destructive-action protections
- Financial invariants
- Promotion atomicity
- Guardian privacy
- Snapshot immutability
- Duplicate prevention
- Audit rollback safety

Outcome:
- No application code defects were found, so no code changes were made.
- Added tests/Feature/BackendHardeningTest.php covering destructive protections, sequential overpayment, snapshot immutability, and audit metadata history.
- Existing Phase 4-9 coverage was confirmed rather than duplicated.

Deferred:
- DB-level CHECK constraint for non-negative balances (open decision)
- Guardian login and a Guardian-to-User link
- Teacher section-scoped student access
- Accountant student visibility decision
- Promotion reversal (safety window unresolved)
- Automatic payment allocation strategy
- Automatic sibling discount rule
- CSV/PDF export
- Real notification channels
- Scheduled cron setup
- Audit UI and export
- UI/controllers/routes

## Phase 10A: Backend Workflow Actions / Service Layer Completion
Status: complete. Verified on 2026-09-30.

Implemented:
- CreateFamily and UpdateFamily.
- LinkGuardianToStudent, with same-Family validation and idempotency.
- RegisterStudent, with optional initial Enrollment and explicit Guardian linking.
- ApplyStudentDiscount.
- CreateFeeStructure.
- Wired the previously deferred audit constants: student_registered, family_created, family_updated, discount_applied.
- Phase 10A feature tests.

Deferred:
- ActivateStudentAfterRegistrationPaid, because registration mandatory Due Items are not identifiable without a new business rule or a new FeeCategory flag.
- Fee structure creation audit, no existing audit constant covers it.
- Guardian-student linking audit, no existing audit constant covers it.
- Guardian login and a Guardian-to-User link
- Teacher section-scoped student access
- Accountant student visibility decision
- Promotion reversal (safety window unresolved)
- Automatic payment allocation strategy
- Automatic sibling discount rule
- CSV/PDF export
- Real notification channels
- Scheduled cron setup
- Audit UI and export
- UI/controllers/routes

## Phase 10B-1: Web Layer for Core Registration Workflows
Status: complete. Verified on 2026-09-30.

Implemented:
- FamilyController with index, create, store, show, edit, and update.
- StudentRegistrationController with create and store.
- StoreFamilyRequest, UpdateFamilyRequest, and RegisterStudentRequest.
- Blade layout plus family index, create, show, edit, and student registration views.
- Named family and student registration web routes.
- Phase 10B-1 feature tests, including no-delete-route and no-due-item guarantees.

## Phase 10B-2: Web Auth & Route Protection
Status: complete. Verified on 2026-09-30.

Implemented:
- LoginController with create, store, and destroy.
- LoginRequest with credential validation and authentication.
- resources/views/auth/login.blade.php.
- EnsureUserHasRole middleware registered as the role alias in bootstrap/app.php.
- auth plus role:Admin protection on all family and student registration web routes.
- Admin check repeated in the three Web Form Requests.
- Session user and logout form in the shared layout.
- Phase 10B-2 feature tests covering guests, login, logout, Admin access, and 403 for Teacher, Accountant, and role-less users.

## Phase 10B-3: Web Fee & Discount Management
Status: complete. Verified on 2026-09-30.

Implemented:
- CreateFeeCategory, UpdateFeeCategory, and CreateStudentFeeSubscription actions.
- FeeCategoryController with index, create, store, edit, and update.
- FeeStructureController with index, create, and store.
- StudentDiscountController with create and store.
- StudentFeeSubscriptionController with create and store.
- Five new Web Form Requests, all Admin-only.
- Seven new Blade views for fee categories, fee structures, discounts, and subscriptions.
- Discount and fee subscription links on the family show page.
- Phase 10B-3 feature tests, 15 in total.

## Phase 10B-4: Web Due Generation & Dashboard Pages
Status: complete. Verified on 2026-09-30.

Implemented:
- DueGenerationController for recurring and event due generation triggers.
- DuesDashboardController rendering BuildDuesDashboardReport.
- GenerateRecurringDuesRequest, GenerateEventDuesRequest, and DuesDashboardFilterRequest.
- Recurring, event, and dashboard Blade views.
- Layout navigation for the Admin area.
- Phase 10B-4 feature tests, 18 in total.

Deferred:
- Scheduler and cron setup for recurring generation
- Event management UI
- Payment recording, allocation, and receipt UI
- Promotion UI and reminder sending UI
- Accountant and Teacher web access
- API controllers, API resources, and mobile endpoints
- Delete and destructive web routes
- Automatic sibling discount rule
- Automatic payment allocation strategy
- Student activation after registration payment
- Guardian login and a Guardian-to-User link
- Teacher section-scoped student access
- Accountant student visibility decision
- Promotion reversal (safety window unresolved)
- CSV/PDF export
- Audit UI and export

## Phase 10B-5: Web Payment Collection & Receipt Pages
Status: complete. Verified on 2026-09-30.

Implemented:
- PaymentCollectionController with create, store, and show.
- ReceiptController with show.
- StoreManualPaymentRequest.
- Payment create, payment show, and receipt show Blade views.
- Manual receipt number entry and manual allocations only.
- Family-level outstanding due item selection.
- Phase 10B-5 feature tests, 11 in total.

Deferred:
- Payment edit, delete, refund, and receipt delete routes.
- Automatic allocation, even-split allocation, and oldest-first allocation.
- Online payment gateways and receipt PDF export.
- Accountant and Teacher payment web access.
- Payment UI index and reporting page.
- API controllers, API resources, and mobile endpoints.
- Guardian login and a Guardian-to-User link.
- Teacher section-scoped student access.
- Accountant student visibility decision.
- Promotion reversal (safety window unresolved).
- Student activation after registration payment.
- Scheduled cron setup.
- Audit UI and export.

## Phase 10B-6: Web Event Management Pages
Status: complete. Verified on 2026-09-30.

Implemented:
- CreateEvent, UpdateEvent, CreateEventCharge, and SetEventParticipation actions.
- EventController, EventChargeController, and EventParticipationController.
- Four new Admin-only Web Form Requests.
- Event list, create, show, edit, charge, and participation Blade views.
- Event navigation in the shared layout.
- Phase 10B-6 feature tests, 11 in total.

Deferred:
- Event charge editing and destructive event, charge, and participation routes.
- Event management audit entries, no existing audit constant covers them.
- Event creation/editing API and mobile endpoints.
- Payment, receipt, reminder, and promotion UI.
- Scheduler and cron setup for recurring generation.
- Accountant and Teacher event web access.
- Guardian login and a Guardian-to-User link.
- Teacher section-scoped student access.
- Accountant student visibility decision.
- Promotion reversal (safety window unresolved).
- Automatic payment allocation and automatic sibling discount.
- Receipt PDF export, CSV/PDF export, and audit UI/export.

## Phase 10B-7: Web Student Promotion Pages
Status: complete. Verified on 2026-09-30.

Implemented:
- PromotionBatchController with index, create, store, show, and confirm.
- StorePromotionBatchRequest and ConfirmPromotionBatchRequest, both Admin-only.
- Promotion batch list, draft create, and detail Blade views.
- Promotion navigation in the shared layout.
- Phase 10B-7 feature tests, 9 in total.

Deferred:
- Draft item target/action editing in the web layer.
- Promotion reversal and reversal safety window.
- Promotion export (CSV/PDF) and class-in-charge reassignment.
- Promotion delete or destructive routes.
- Promotion API and mobile endpoints.
- Due generation, payment, receipt, event, reminder, and dashboard UI beyond existing web pages.
- Accountant and Teacher promotion web access.
- Guardian login and a Guardian-to-User link.
- Teacher section-scoped student access.
- Accountant student visibility decision.
- Automatic payment allocation and automatic sibling discount.
- Student activation after registration payment.
- Scheduled cron setup and audit UI/export.

## Phase 10B-8: Web Payment Reminder Pages
Status: complete. Verified on 2026-09-30.

Implemented:
- PaymentReminderController with index, create, store, and show.
- GeneratePaymentRemindersRequest and PaymentReminderFilterRequest, both Admin-only.
- Reminder list, generation, and stored preview Blade views.
- Payment reminder navigation in the shared layout.
- Phase 10B-8 feature tests, 12 in total.

Deferred:
- Reminder send/status-transition/edit/delete routes.
- SMS, WhatsApp, email, delivery providers, queues, and scheduling.
- Reminder API and mobile endpoints.
- Guardian login and a Guardian-to-User link.
- Accountant and Teacher reminder web access.
- Payment, receipt, promotion, and event management beyond existing pages.
- Promotion reversal (safety window unresolved).
- Automatic payment allocation and automatic sibling discount.
- Student activation after registration payment.
- Receipt PDF export, CSV/PDF export, and audit UI/export.

## Phase 10B-9: Web Admin Usability & Navigation Polish
Status: complete. Verified on 2026-09-30.

Implemented:
- AdminDashboardController serving a read-only /admin landing page.
- Root route now redirects guests to login and authenticated users to the admin dashboard.
- Grouped admin navigation row in the shared layout.
- Consistent success, error, and validation flash display in the layout.
- Safe cross-links between existing named routes only.
- Phase 10B-9 feature tests, 16 in total.
- Existing ExampleTest and root-route auth test updated for the redirect behaviour.

Deferred:
- Any new dashboard widgets, charts, or reporting queries.
- Accountant and Teacher dashboard or navigation access.
- Personalized or role-specific navigation variants.
- Advanced UI styling, frontend framework, or asset pipeline.
- Reminder sending, queues, scheduler, and delivery providers.
- Payment, promotion, and event features beyond existing pages.

## Phase 10B-10: Web Manual QA & Bug Fix Pass
Status: complete. Verified on 2026-09-30.

Manual QA checklist completed across auth, admin dashboard, families and students,
fees and discounts, due generation and dashboard, payments and receipts, events,
promotion, reminders, and route safety.

Bugs found: none. No application code changes were required.

Findings confirmed as correct behaviour:
- Promotion lists only active students, so a pending_registration student is not promoted.
- Post-login lands on /families while the root route sends authenticated admins to /admin.
  Both were explicitly specified in earlier phases, so neither was changed here.

Added:
- tests/Feature/WebManualQaRegressionTest.php covering all admin pages, destructive
  and API route absence, dashboard filter combinations, family and event update
  forms, logout protection, and one full journey from login through payment,
  receipt, event dues, reminders, and promotion.

Deferred:
- Any new feature, module, or business rule.
- Standardising the post-login landing page.
- Student activation after registration payment.
- Reminder sending, queues, scheduler, and delivery providers.
- Accountant and Teacher web access.

## Phase 10C-1: Demo Data & Local Testing Setup
Status: complete. Verified on 2026-09-30.

Implemented:
- database/seeders/DemoDataSeeder.php for local and testing use only.
- Deterministic roles, users, academic years, grades, sections, families, guardians,
  students, enrollments, fee categories, fee structures, discount, and opt-in subscription.
- Recurring dues, one payment with receipt, event charges, event dues, reminders, and a
  draft promotion batch, all created through the existing workflow actions.
- Demo Admin, Accountant, and Teacher users, where only Admin has web access.
- DatabaseSeeder reduced to roles plus one Admin and never calls the demo seeder.
- docs/local-demo.md with reset, seed, run, credentials, and safety notes.
- tests/Feature/DemoDataSeederTest.php, 13 tests covering content, idempotency,
  the production guard, and web page loading after seeding.

Deferred:
- Automatic DemoDataSeeder execution during migrate.
- A custom artisan command wrapper for seeding.
- Realistic large-volume demo data or performance datasets.
- Any change to production business logic or seeding behaviour.

## Phase 10C-2: Deployment Readiness & Security Review
Status: complete. Verified on 2026-09-30.

Implemented:
- docs/deployment-readiness.md with the production env checklist, deployment steps,
  rollback basics, and the current production blockers.
- docs/security-review.md with the auth and authorization state, guardian privacy,
  attack surface, seeding and secret hygiene, open risks, and next steps.
- .env.example corrected to ship mysql keys and a SESSION_SECURE_COOKIE hint.
- tests/Feature/DeploymentReadinessTest.php, 10 tests locking in the production
  guards, secret hygiene, route exposure, and auth plus role coverage.

Findings fixed:
- .env.example pointed at sqlite with MySQL keys commented out, which would have
  produced a broken local setup when copied to .env.

Findings documented, not fixed, by design:
- No login rate limiting.
- No password reset and no email verification.
- Coarse Admin-only authorization model.
- SESSION_SECURE_COOKIE has no safe default and must be set in production.
- No database-level money constraints.

Deferred:
- Login throttling, password reset, email verification, and 2FA.
- Per-resource Accountant and Teacher permissions.
- Audit review screen, export, and retention policy.
- Receipt numbering rule.

## Phase 10C-3: System Understanding, Data Flow & UX Map
Status: complete. Verified on 2026-09-30.

Documentation only. No business logic, model, migration, controller, or test changed.

Created:
- docs/system-overview.md. What Skooly is, who can use it today, module status, and
  an honest split between demo-ready and not-production-ready.
- docs/data-flow.md. Registration, fee, payment, event, reminder, and promotion
  flows with Mermaid diagrams, plus the safety rules that protect money and privacy.
- docs/user-workflows.md. Fourteen practical Admin workflows, each with purpose,
  navigation path, prerequisites, what is written, and current limitations.
- docs/ui-ux-roadmap.md. Honest assessment of the plain HTML interface and a phased
  redesign plan from foundation through role-specific experiences.
- docs/production-gap-register.md. 27 gaps with severity, rationale, suggested phase,
  and the business decision each one waits on.

Key finding: there is no web screen for academic setup, so years, terms, grades, and
sections still require console access. This is recorded as a High severity gap.

## Phase 10C-4: Admin UI/UX Foundation & Login Redesign
Status: complete. Verified on 2026-09-30.

Presentation only. No business logic, model, migration, controller, or FormRequest changed.

Implemented:
- public/css/admin.css, a complete admin design system in plain CSS.
- Sidebar-based admin layout with grouped navigation, active state, brand, user, and logout.
- Redesigned login page as a centred product card.
- Dashboard regrouped into Registration, Fees and dues, Payments, Events and promotion, and Reminders.
- Consistent forms, tables, detail key-value blocks, empty states, and status badges across all pages.
- Six reusable Blade components: page-header, card, empty-state, status-badge, alert, button-link.
- Shared flash partial for success, error, and validation messages.
- tests/Feature/WebUiUxTest.php, 11 tests covering branding, fields, navigation, and structure.

Key decision: public/build is gitignored, so the Vite and Tailwind pipeline would
break every page on a fresh checkout. The prompt's documented fallback was used
instead: one static CSS file linked from the layout, with no build step.

## Phase 10C-4B: Commercial Admin UI/UX Redesign
Status: complete. Verified on 2026-09-30.

Presentation only. No backend logic, model, migration, controller, or request changed.

Redesigned:
- Admin shell: dark institutional sidebar, light workspace, sticky topbar, CSS-only mobile drawer.
- Login: split layout with a brand story panel and a focused sign-in card.
- Dashboard: product home with stat cards and grouped workflow cards.
- Panels: double-bezel frames, raised surfaces, layered depth, stronger hierarchy.
- Forms, tables, detail rows, empty states, alerts, and status badges restyled.
- Responsive behaviour down to small screens, with reduced-motion support.

Added:
- x-stat-card and x-form-section components.
- x-card now renders a framed panel.
- tests/Feature/WebCommercialUiTest.php, 16 tests.

Carried forward: the static CSS decision from 10C-4, because the
Vite and Tailwind build output is gitignored.

## Phase 10C-4C: Commercial UI/UX Defect Audit and Workflow Completion
Status: complete. Verified on 2026-09-30.

Presentation and workflow navigation only. No backend behaviour changed.

Audited and fixed:
- Missing workflow actions: confirmations, loading states, empty-state calls to
  action, breadcrumbs, primary-action hierarchy, and back links were added or corrected
  across every existing screen.
- No confirmation existed on any irreversible-looking action. Five now confirm first:
  recurring generation, event generation, payment, reminders, and promotion.
- No submit loading state existed. All write forms now disable and mark themselves busy.
- Flash messages were static blocks. They are now dismissible toasts.
- Empty states were dead text. They now explain the prerequisite and offer the next step.
- The receipt page did not read as a receipt. It is now a printable receipt sheet.
- Reminder screens understated that nothing is sent. This is now a labelled callout.

Added:
- public/js/admin-ui.js. Local, dependency free, no CDN, no build step.
- docs/ui-ux-defect-audit.md.
- tests/Feature/WebCommercialWorkflowUiTest.php, 20 tests.

Rejected: SweetAlert2, because the Vite build output is gitignored and depending on
it would break a fresh checkout and the test suite.

## Phase 10C-5: Backend-Frontend Feature Parity and CRUD Coverage Audit
Status: complete, verified on 2026-09-30, committed in f63c2d5.

Audit and planning only. No backend behaviour, route, controller, or model changed.

Created:
- docs/backend-frontend-feature-parity.md, covering every module against the UI.
- docs/crud-coverage-matrix.md, classifying twelve operations per module.
- docs/frontend-missing-feature-backlog.md, ordered by severity with suggested phases.
- docs/destructive-action-policy-draft.md.
- tests/Feature/FeatureParityDocumentationTest.php, 7 tests.

Headline findings:
- Nine Foundation modules have no web UI, so school staff cannot configure the school.
- Payment and Receipt have no list page.
- Audit logs are written but cannot be viewed.
- Guardian management, student detail, discount, and subscription screens are partial.
- No payment correction or refund path exists.
- SchoolSetting.active_academic_year_id is inert.

Recommended next phase: 10D-1, academic setup plus audit viewing.

## Phase 10D-1A: Academic Setup Web Pages
Status: complete, verified on 2026-10-01, committed in 1891bf6.

Implemented:
- Admin-only AcademicYearController, TermController, GradeController, SectionController, and SchoolSettingController.
- Admin-only Form Requests for academic setup validation.
- Academic year, term, grade, and section list, create, show, edit, and update pages.
- Active academic year settings page, sidebar navigation group, and dashboard card.
- WebAcademicSetupTest coverage for role protection, CRUD flows, navigation, and route absence.

Deferred:
- Archive/deactivate: the existing academic setup schema has no lifecycle status and archive semantics are unresolved.
- Hard delete: intentionally absent.
- Audit log, user, role, teacher, API/mobile, Accountant, Teacher, and Guardian UI remain outside this phase.

Verification:
- `php artisan migrate:fresh --no-interaction` and `php artisan db:seed --class=DemoDataSeeder --no-interaction` passed.
- `php artisan test --compact`: 410 tests / 1913 assertions passed.
- PHPStan, Pint, Composer audit, and `git diff --check` passed.

## Phase 10D-1B: Audit Log Viewing
Status: complete and verified on 2026-10-07; committed in dab9034.

Implemented:
- Admin-only AuditLogController index and show actions.
- Read-only audit-log index and detail pages, including stored metadata.
- Governance navigation link and WebAuditLogTest coverage.
- Updated parity, CRUD, backlog, and system-overview documentation.

Deferred:
- Audit-log filtering, export, retention, create, update, and delete workflows.
- Audit log access for Accountant, Teacher, and Guardian users.

Verification:
- `php artisan test --compact`: 415 tests / 1935 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D: Audit Log Workflow
Status: complete and verified on 2026-10-07; pending commit.

Implemented:
- Admin-only stored-action, actor, record-type, and occurred-at date-range filters with 20-record pagination and query-string preservation.
- Admin-only CSV streamed download and local-Dompdf PDF download using the same filtered AuditLog query and filter summary.
- JSON metadata export, no polymorphic auditable lookup, and no audit mutation, archive, or purge route.
- Forever retention decision: AuditLog remains append-only with no deletion or retention job.
- `WebAuditLogTest` coverage for each filter, combined pagination, CSV/PDF downloads, role protection, and destructive-route absence.

Verification:
- `php artisan test tests/Feature/WebAuditLogTest.php --compact`: 14 tests / 60 assertions passed.
- `php artisan test --compact`: 490 tests / 2363 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-2: Identity, Teacher, Subject & Assignment Management
Status: complete and verified on 2026-10-07; committed in deef8f5.

Implemented:
- Fixed Superadmin role plus `users:make-superadmin {email}` bootstrap command.
- is_active user lifecycle and inactive-login rejection.
- Admin/Superadmin User management; no hard delete, Admin restrictions, and final-Superadmin safeguards.
- Subject CRUD with teaching-assignment deletion protection.
- Teacher qualification, teaching assignment, and class-in-charge list/create/view web flows.

Deferred:
- Accountant and Teacher operational access.
- Role CRUD, user hard deletion, and assignment correction/removal workflows.

Verification:
- `php artisan test --compact`: 428 tests / 1982 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-3: Student & Guardian Management
Status: complete and verified on 2026-10-07; committed in 60007d9.

Implemented:
- Guardian create, view, and edit pages under existing Families.
- Student detail and edit pages plus explicit Guardian-Student link and audited unlink workflows.
- Enrollment placement page that delegates to Enrollment::placeIn().
- Discount review/deactivation and fee-subscription review/end pages for future generation only.

Deferred:
- Guardian login, photo upload/storage, discount/subscription lifecycle audit entries, standalone enrollment/link reports, and Event participation scope.

Verification:
- `php artisan test --compact`: 438 tests / 2035 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-4: Draft Discard & Reminder Cancellation
Status: complete and verified on 2026-10-07; committed in bb27137.

Implemented:
- Admin-only discard of draft promotion batches, retaining every batch item and making no Student or Enrollment change.
- Admin-only cancellation of pending payment reminders, retaining their stored snapshots and making no financial or delivery change.
- Confirmed UI forms, status guards, and feature coverage for success and invalid-state paths.

Deferred:
- Archive/deactivate flows remain blocked by unresolved archive semantics.
- Audit entries for these transitions remain deferred because no AuditLog action constants exist.

Verification:
- `php artisan test --compact`: 442 tests / 2055 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-5: Fee Structure & Event Charge Editing
Status: complete and verified on 2026-10-07; pending commit.

Implemented:
- Admin-only FeeStructure amount and frequency editing before a StudentDueItem directly references the structure.
- Admin-only EventCharge amount editing before the Event has any EventDueItem; event-level locking is conservative because EventCharge provenance is unavailable.
- Transactional update actions, Form Requests, scoped event-charge routes, locked-status UI, and no-delete coverage.

Preserved:
- FeeStructure category, grade, and academic year identity; EventCharge event and grade identity; and all generated due snapshots.
- No new schema, deletion, or audit workflow.

Verification:
- `php artisan test --compact`: 467 tests / 2223 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-6: Payment & Receipt History Lists
Status: complete and verified on 2026-10-07; committed in 4cf641f.

Implemented:
- Admin-only payment and receipt history index pages with links to their existing detail pages.
- Search by family code, payment reference, and receipt number; validated fixed sorting; 20-record pagination with query-string preservation.
- No financial data mutation, automatic allocation, refund, edit, delete, or receipt export workflow.

Deferred:
- Receipt PDF export, audit-log filtering/export/retention, broader list-page rollout, and payment correction/refund.

Verification:
- `php artisan test --compact`: 445 tests / 2073 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-6B: Remaining List Pagination & Search
Status: complete and verified on 2026-10-07; pending commit.

Implemented:
- 20-record pagination with query-string preservation on the remaining Admin list pages.
- Validated text search across relevant family, academic, fee, event, promotion, staff, assignment, and user lists.
- Pagination only for AuditLog and Student-scoped discount/subscription histories, preserving their existing scope and deferred filtering decisions.

Deferred:
- Fixed sorting on the newly paginated lists, audit-log filtering/export/retention, receipt PDF export, and payment correction/refund.

Verification:
- `php artisan test --compact`: 450 tests / 2120 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-6C: Safe Fixed Sort Controls for Admin Lists
Status: complete and verified on 2026-10-07; pending commit.

Implemented:
- Extended the shared list-search request and component with validated sort and direction inputs.
- Added fixed, per-controller sort maps plus stable `id` tie-breakers to every in-scope Admin list page.
- Preserved existing default ordering, text search, pagination, query-string state, and User Gate/policy authorization.
- Added valid sort, query-string preservation, and sort-injection rejection coverage to `WebIndexPaginationTest`.

Deferred:
- Audit-log filtering, sorting, export, and retention; payment correction/refund; receipt PDF export.
- Payment, receipt, reminder, and Student-scoped discount/subscription lists remain unchanged by this phase.

Verification:
- `php artisan test --compact`: 452 tests / 2124 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-7: Payment Reversal Workflow
Status: complete, extended for approved partial reversals, and verified on 2026-10-08; pending commit.

Implemented:
- Append-only PaymentReversal and CorrectionReceipt models, factories, and migrations.
- Accountant-only reversal requests with required reasons and exact selected original-allocation amounts.
- Admin-only independent approval with dual-role self-approval prevention.
- PaymentReversalAllocation records with per-reversal allocation uniqueness, safe migration backfill, and removal of the former one-reversal-per-payment constraint.
- Transactional locking with request-time and approval-time remaining-cap checks across approved reversal allocations; only selected amounts reopen and invalid approval rolls back.
- Immutable itemized correction-receipt snapshots plus explicit requested and approved audit entries containing totals and entries.
- Finance-only Accountant access to payment/receipt history and reversal views; no collection, family, or approval access.
- PaymentReversalTest coverage for success, exact reopening, immutability, correction receipt, audit actions, duplicate prevention, self approval, roles, validation, and rollback.

Deferred:
- Refunds, payment edits/deletes, receipt PDF export, and broader Accountant operational permissions.

Verification:
- `php artisan migrate:fresh --no-interaction` passed.
- `php artisan test --compact`: 498 tests / 2413 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-8: Promotion Draft Editing & Accountant Read Access
Status: complete and verified on 2026-10-07; pending commit.

Implemented:
- Admin-only per-item PromotionBatch editing while a batch is draft, using transactional row locks and an authoritative draft-state check.
- Promote and retain require a matching target grade and target section; exclude and graduate clear both target IDs.
- promotion_batch_item_updated audit entries capture the before and after item target/action values inside the transaction.
- ConfirmPromotionBatch locks and rechecks draft status before applying changes, preserving atomic confirmation against concurrent item edits.
- Accountant read-only access to Dues Dashboard and payment-reminder list/detail, alongside the existing payment, receipt, and reversal history/detail/request access.
- Accountant-only finance navigation and authorization coverage for prohibited Admin, family, student, guardian, academic, staff, event, promotion, collection, reminder-mutation, and approval pages.

Preserved:
- Source Enrollments remain immutable, confirmation remains atomic, and no next-year due item is generated.
- Guardian visibility is unchanged and no Accountant access to family, student, or guardian pages was introduced.

Verification:
- `php artisan migrate:fresh --no-interaction` passed.
- `php artisan test --compact`: 474 tests / 2273 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D-9: Print-Friendly Detail Pages & Receipt PDF Download
Status: complete and verified on 2026-10-08; pending commit.

Implemented:
- Family and Student detail pages have clear browser print buttons using local `window.print()` only.
- Scoped `print-record` styles hide the admin shell, page actions, table actions, and mutation controls while retaining displayed record fields.
- Admin-only Receipt PDF download uses local Dompdf with the existing manual receipt number as its filename.
- The PDF reads only Receipt family, payment, and allocation snapshots. It never creates, renumbers, or changes receipts, payments, allocations, or due items.
- Accountant receipt list/detail access is unchanged; Accountant PDF download access is deliberately absent.

Deferred:
- Report export, dashboard charts, and dashboard/report refinement remain deferred.

Verification:
- `php artisan test tests/Feature/WebPaymentCollectionTest.php tests/Feature/WebCommercialWorkflowUiTest.php --compact`: 37 tests / 267 assertions passed.
- `php artisan test --compact`: 496 tests / 2395 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Accountant Payment Collection
Status: complete and verified on 2026-10-08; pending commit.

Implemented:
- Finance-only `/payments/collect` entry page that resolves an exact Family code, stores the selected Family in session, and prevents Accountant allocation URLs from bypassing that selection.
- Accountant access to the existing selected-Family allocation page and manual payment-recording transaction through `PaymentPolicy::create`.
- Accountant-only view behavior on the allocation page so it never links to Family or due-generation pages.
- Coverage for Accountant entry, selected-Family allocation data, recording, family/non-finance denial, Teacher denial, and the GET-only collection selector.

Preserved:
- The existing transactional family-ownership and balance guards remain authoritative.
- No collection mutation route was added beyond the existing payment-recording POST; reversal approval and reminder mutation remain Admin-only.

Verification:
- `php artisan migrate:fresh --no-interaction` passed.
- `php artisan test --compact`: 499 tests / 2444 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Phase 10D Archive/Deactivate Workflow
Status: complete and verified on 2026-10-07; pending commit.

Implemented:
- `is_archived` lifecycle flags and local `active()` scopes for AcademicYear, Term, Grade, and Section.
- Admin-only confirmed archive/restore routes with explicit audit records for every transition.
- Active-year archive protection and SchoolSetting filtering/validation for archived years.
- Existing indexes, details, reports, and foreign references remain readable; only new configuration selectors exclude archived records.

Verification:
- `php artisan migrate:fresh --no-interaction` passed.
- `php artisan test --compact`: 481 tests / 2324 assertions passed.
- PHPStan, Pint, npm build, Composer audit, and `git diff --check` passed.

## Roadmap Step 2: Security Hardening
Status: complete and verified on 2026-10-08; pending commit.

Implemented:
- Login throttling after five failed attempts per normalized email and IP address, with a successful login clearing the limiter.
- Laravel password-broker reset flow with opaque reset-link responses, password reset views, and token-reset coverage.
- Laravel MustVerifyEmail support, signed verification links, resend throttling, and verified middleware on protected application routes.
- MySQL StudentDueItem CHECK constraint for non-negative money fields and `paid_amount + balance_amount = net_amount`; SQLite test triggers provide equivalent test-environment enforcement.
- Production secure-cookie documentation and verified local/demo seed accounts.
- Authenticator-app TOTP setup with a session-only pending secret, encrypted confirmed secret, manual secret and provisioning URI, valid-code confirmation, and one-time recovery codes stored only as hashes.
- Password-first login challenge that remains unauthenticated until valid TOTP or recovery verification, account-and-IP challenge throttling, session regeneration on completion, and password plus valid-code disablement that clears all state.

Verification:
- `php artisan migrate:fresh --no-interaction` passed.
- `php artisan test --compact`: 520 tests / 2622 assertions passed.
- PHPStan, Pint, npm build (optional Fontaine warning), and Composer audit passed.
- `git diff --check` passed.

## Phase 8: Student Promotion
Status: complete. Verified on 2026-09-30.

Includes:
- PromotionBatch, PromotionBatchSection, and PromotionBatchItem models and factories.
- promotion_batches, promotion_batch_sections, and promotion_batch_items migrations.
- app/Actions/Promotion/CreatePromotionBatch.php for draft creation.
- app/Actions/Promotion/ConfirmPromotionBatch.php for atomic confirmation.
- Default next-grade and same-named target section resolution.
- Promote, retain, graduate, and exclude actions.
- Source-year Enrollment immutability and no next-year due generation.
- Phase 8 feature tests.

Deferred:
- Promotion reversal (safety window unresolved)
- CSV/PDF export
- Class-in-charge reassignment
- Audit logs
- Scheduled automation or cron command
- Automatic payment allocation
- Payment-driven student registration activation
- Family combined billing aggregation
- Frontend dashboards
- UI/controllers/routes
- Promotion

## Phase 7B: Events Generating Due Items
Status: complete. Verified on 2026-09-30.

Includes:
- Event, EventCharge, EventParticipation, and EventDueItem models and factories.
- events, event_charges, event_participations, and event_due_items migrations.
- app/Actions/Events/GenerateEventDueItems.php.
- Mandatory and opt-in event due generation for enrolled students.
- EventCharge amount snapshots and deterministic generation_key duplicate prevention.
- Discount application with DueItemDiscount snapshots.
- Phase 7B feature tests.

Deferred:
- Participation and payment-status reporting
- Scheduled automation or cron command
- Automatic payment allocation
- Automatic sibling discount rule
- Payment-driven student registration activation
- Family combined billing aggregation
- Reminders
- Frontend dashboards
- Event notifications
- UI/controllers/routes
- Promotion
- Audit

## Phase 7C: Dues Dashboard / Reporting Queries
Status: complete. Verified on 2026-09-30.

Includes:
- app/Actions/Reports/BuildDuesDashboardReport.php.
- summary, by_fee_category, family_balances, student_balances, and outstanding_due_items sections.
- Filters for academic year, grade, section, date range, fee category, and family.
- Read-only guarantee: no tables added, no money records created or modified.
- Phase 7C feature tests.

Deferred:
- Participation and payment-status reporting
- Scheduled automation or cron command
- Automatic payment allocation
- Automatic sibling discount rule
- Payment-driven student registration activation
- Family combined billing aggregation
- Frontend dashboards
- Event notifications
- UI/controllers/routes
- Promotion
- Audit

## Phase 7D: Payment Reminders / Notification Foundation
Status: complete. Verified on 2026-09-30.

Includes:
- PaymentReminder model and factory.
- payment_reminders migration as an internal outbox table.
- app/Actions/Notifications/GeneratePaymentReminders.php.
- Upcoming and overdue due item coverage.
- Guardian eligibility through explicit guardian_student links.
- Combined billing consolidation per Guardian and Family.
- Deterministic reminder_key duplicate prevention.
- Read-only guarantee for due items, payments, allocations, and receipts.
- Phase 7D feature tests.

Deferred:
- Real notification channels (email, SMS, WhatsApp)
- Reminder templates and rendering
- Scheduled automation or cron command
- Automatic payment allocation
- Automatic sibling discount rule
- Payment-driven student registration activation
- Family combined billing aggregation
- Frontend dashboards
- Event notifications
- UI/controllers/routes
- Promotion
- Audit
