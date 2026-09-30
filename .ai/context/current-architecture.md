# Current Architecture

## Style
Laravel modular monolith.

## Rules
- Use Laravel conventions.
- Keep implementation small and requirement-complete.
- No unnecessary repositories/services/interfaces.
- Users may have multiple roles.
- Guardian access is explicit through guardian_student.
- Combined family billing must not broaden guardian visibility.
- Student grade/section history uses enrollments and enrollment placements.
- Financial workflows must use database transactions later.
- Promotion must be draft/confirm and atomic later.

## Completed Modules
- Academic Foundation
- Identity and Teacher Foundation
- Families and Guardians (Phase 3)
- Students and Enrollments (Phase 4)
- Fees, Dues and Discounts (Phase 5)
- Payments & Receipts (Phase 6)
- Recurring Fee Due Generation (Phase 7A, verified and pending commit)

## Current Module
- Phase 7A: Recurring Fee Due Generation is complete / pending commit.

## Families and Guardians
- Family is the household registration and billing unit, identified by a unique family_code.
- A Family has many Guardians; each Guardian belongs to exactly one Family.
- Deleting a Family with registered Guardians is restricted by the database.
- Combined billing defaults to enabled, without granting Guardians student visibility.
- Guardian access to Students is explicit through guardian_student.

## Students and Enrollments
- A Student belongs to one Family and may have many explicit Guardian links.
- admission_no is globally unique; Student status defaults to pending_registration.
- Grade and Section belong to an Enrollment, not to Student.
- An Enrollment is unique per Student and Academic Year and records its current Grade and Section.
- Enrollment::placeIn() updates current placement and appends placement history in one database transaction.
- Composite foreign keys ensure a stored Section belongs to the stored Grade.

## Fees, Dues and Discounts
- FeeCategory identifies recurring and non-recurring charges.
- FeeStructure is configuration scoped to FeeCategory, Grade, AcademicYear, and frequency.
- StudentDueItem is a per-Student historical amount snapshot, not a live view of FeeStructure.
- Discount applies to a specific Student and FeeCategory; it is not automatically applied.
- DueItemDiscount snapshots applied discount data and its nullable discount_id nulls on source Discount deletion.
- StudentFeeSubscription supports future opt-in categories without subscription workflows.

## Payments & Receipts
- Payments are family-level.
- PaymentAllocation links a Payment to specific StudentDueItems.
- Manual allocation only is implemented. No automatic even-split or oldest-first allocation.
- StudentDueItem tracks paid_amount, balance_amount, and status (unpaid, partially_paid, paid).
- Receipt snapshots payment, family, and allocation details immutably.
- Payment recording uses DB transaction for atomicity.

## Recurring Fee Due Generation
- app/Actions/Fees/GenerateRecurringDueItems.php generates StudentDueItems for a cycle.
- Source of truth is recurring FeeStructures for the academic year, matched to students enrolled in the same year and grade.
- FeeCategory.is_opt_in gates categories that require an active StudentFeeSubscription.
- Generated dues are still StudentDueItems with paid_amount 0 and status unpaid.
- Discounts are applied at generation time and snapshotted to DueItemDiscount.
- generation_key prevents duplicate dues for the same student, category, structure, and cycle.
- Generation is transactional and never creates payments or receipts.
