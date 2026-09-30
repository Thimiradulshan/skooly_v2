# Phase Status

## Current Phase
Phase 6: Payments & Receipts

## Completed Phases
- Phase 1: Academic Foundation - complete
- Phase 2: Users, Roles & Teachers - complete
- Phase 3: Families & Guardians - complete
- Phase 4: Students & Enrollments - complete
- Phase 5: Fees, Dues & Discounts - complete
- Phase 6: Payments & Receipts - complete / pending commit

## Current Status
Phase 6 implementation and verification are complete. Pending review and commit.

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

## Verification Result
Passed on 2026-09-30:
- php artisan migrate:fresh --no-interaction
- php artisan test --compact: 52 tests, 130 assertions
- php vendor/bin/phpstan analyse: 0 errors
- php vendor/bin/pint --test
- composer audit: no security vulnerability advisories
- git diff --check passed with CRLF warning only

## Blockers
None.

## Next Exact Step
Review git status, then commit Phase 6.
