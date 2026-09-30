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
Status: complete / pending commit. Verified on 2026-09-30.

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
- Dashboards
- Reminders
- Events
- UI/controllers/routes
- Promotion
- Audit
