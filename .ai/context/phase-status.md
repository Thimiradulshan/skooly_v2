# Phase Status

## Current Phase
Phase 7B: Events Generating Due Items

## Completed Phases
- Phase 1: Academic Foundation - complete
- Phase 2: Users, Roles & Teachers - complete
- Phase 3: Families & Guardians - complete
- Phase 4: Students & Enrollments - complete
- Phase 5: Fees, Dues & Discounts - complete
- Phase 6: Payments & Receipts - complete
- Phase 7A: Recurring Fee Due Generation - complete
- Phase 7B: Events Generating Due Items - complete / pending commit

## Current Status
Phase 7B implementation and verification are complete. Pending review and commit.

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

## Verification Result
Passed on 2026-09-30:
- php artisan migrate:fresh --no-interaction
- php artisan test --compact: 80 tests, 235 assertions
- php vendor/bin/phpstan analyse: 0 errors
- php vendor/bin/pint --test
- composer audit: no security vulnerability advisories
- git diff --check passed

## Blockers
None.

## Next Exact Step
Review git status, then commit Phase 7B.
