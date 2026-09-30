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
- Fees, Dues and Discounts (Phase 5, verified and pending commit)

## Current Module
- Phase 5: Fees, Dues and Discounts is complete / pending commit.

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
