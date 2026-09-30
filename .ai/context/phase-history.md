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
Status: complete / pending commit. Verified on 2026-09-30.

Implemented:
- LoginController with create, store, and destroy.
- LoginRequest with credential validation and authentication.
- resources/views/auth/login.blade.php.
- EnsureUserHasRole middleware registered as the role alias in bootstrap/app.php.
- auth plus role:Admin protection on all family and student registration web routes.
- Admin check repeated in the three Web Form Requests.
- Session user and logout form in the shared layout.
- Phase 10B-2 feature tests covering guests, login, logout, Admin access, and 403 for Teacher, Accountant, and role-less users.

Deferred:
- Accountant and Teacher web access to registration pages
- API auth, tokens, and mobile endpoints
- Advanced user management, password reset, and email verification
- Guardian login and a Guardian-to-User link
- Teacher section-scoped student access
- Accountant student visibility decision
- Promotion reversal (safety window unresolved)
- Automatic payment allocation strategy
- Automatic sibling discount rule
- ActivateStudentAfterRegistrationPaid
- Delete and destructive web routes
- Payment UI, promotion UI, reminders UI, and dashboard UI
- CSV/PDF export
- Real notification channels
- Scheduled cron setup
- Audit UI and export

Deferred:
- Authentication, login, and authorization middleware on web routes
- API controllers, API resources, and mobile endpoints
- Delete and destructive web routes
- Payment UI, promotion UI, reminders UI, and dashboard UI
- Advanced UI design, CSS framework, and dynamic guardian rows
- Guardian login and a Guardian-to-User link
- Teacher section-scoped student access
- Accountant student visibility decision
- Promotion reversal (safety window unresolved)
- Automatic payment allocation strategy
- Automatic sibling discount rule
- ActivateStudentAfterRegistrationPaid
- CSV/PDF export
- Real notification channels
- Scheduled cron setup
- Audit UI and export

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
