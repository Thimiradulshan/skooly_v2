# Phase Status

## Current Phase
Phase 7D: Payment Reminders / Notification Foundation

## Completed Phases
- Phase 1: Academic Foundation - complete
- Phase 2: Users, Roles & Teachers - complete
- Phase 3: Families & Guardians - complete
- Phase 4: Students & Enrollments - complete
- Phase 5: Fees, Dues & Discounts - complete
- Phase 6: Payments & Receipts - complete
- Phase 7A: Recurring Fee Due Generation - complete
- Phase 7B: Events Generating Due Items - complete
- Phase 7C: Dues Dashboard / Reporting Queries - complete
- Phase 7D: Payment Reminders / Notification Foundation - complete / pending commit

## Current Status
Phase 7D implementation and verification are complete. Pending review and commit.

## Schema Decisions
- FeeCategory identifies recurring and non-recurring charges.
- FeeStructure is versioned by fee category, grade, academic year, and frequency.
- StudentFeeSubscription supports opt-in categories without implementing subscription workflows.
- Discount applies to a specific Student and FeeCategory; no sibling discount automation exists.
- StudentDueItem is per Student and snapshots the description and amounts independently of FeeStructure changes.
- DueItemDiscount snapshots applied discount data. Its nullable discount_id uses nullOnDelete so historical snapshots remain if the source Discount is deleted.
- Payments are family-level.
- PaymentAllocation links a Payment to specific StudentDueItems.
- Manual allocation only is implemented. No automatic even-split or oldest-first allocation.
- StudentDueItem tracks paid_amount, balance_amount, and status (unpaid, partially_paid, paid).
- Receipt snapshots payment, family, and allocation details.
- Payment recording uses DB transaction.
- FeeCategory has an is_opt_in flag (default false). When true, dues are generated only for students with an active StudentFeeSubscription.
- GenerateRecurringDueItems creates StudentDueItems from recurring FeeStructures for students enrolled in the same academic year and grade.
- Generated dues are still StudentDueItems, with paid_amount 0, balance_amount equal to net_amount, and status unpaid.
- Discounts are applied at generation time and snapshotted into DueItemDiscount. value_type uses amount or percentage; null is treated as amount.
- Discount is clamped so net_amount never goes below 0.
- Duplicate generation is prevented by a deterministic generation_key built from student, category, structure, and cycle.
- Generation runs inside a DB transaction and creates no payments or receipts.
- Event belongs to an AcademicYear and a FeeCategory. No event fee category name is hardcoded.
- EventCharge stores a per-grade amount, unique per event and grade.
- EventParticipation stores opted_in or opted_out, unique per event and student.
- event_due_items links a generated StudentDueItem back to its Event.
- Mandatory events generate for all enrolled applicable students; opt-in events generate only for opted-in students.
- EventCharge amount is snapshotted into StudentDueItem and never rewritten later.
- Event generation is transactional and creates no payments or receipts.
- BuildDuesDashboardReport is read-only. It adds no tables and modifies no money records.
- Reporting reads stored StudentDueItem snapshot balances. It never recalculates discounts or re-derives payments from PaymentAllocation.
- Supported filters: academic_year_id, grade_id, section_id, fee_category_id, family_id, due_date_from, due_date_to.
- grade_id or section_id without academic_year_id throws InvalidArgumentException because enrollment is year-specific.
- Report sections: summary, by_fee_category, family_balances, student_balances, outstanding_due_items.
- Money totals are formatted as two-decimal strings without floating-point arithmetic.
- payment_reminders is an internal outbox table. No external sending is implemented.
- Reminder types are upcoming and overdue. Statuses are pending, sent, and cancelled.
- Reminder eligibility requires balance_amount > 0, status unpaid or partially_paid, and a non-null due_date.
- Guardian reminder eligibility uses explicit guardian_student links only, never family membership alone.
- Combined billing families get one consolidated reminder per Guardian and Family with student_due_item_id null.
- Non-combined families get one reminder per Guardian and StudentDueItem.
- reminder_key is deterministic and prevents duplicate reminders for the same as_of_date.
- Generation creates no payments, receipts, allocations, and never modifies StudentDueItem.

## Verification Result
Passed on 2026-09-30:
- php artisan migrate:fresh --no-interaction
- php artisan test --compact: 113 tests, 352 assertions
- php vendor/bin/phpstan analyse: 0 errors
- php vendor/bin/pint --test
- composer audit: no security vulnerability advisories
- git diff --check passed

## Blockers
None.

## Next Exact Step
Review git status, then commit Phase 7D.
