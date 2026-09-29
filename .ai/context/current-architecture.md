# Current Architecture

## Style
Laravel modular monolith.

## Rules
- Use Laravel conventions.
- Keep implementation small and requirement-complete.
- No unnecessary repositories/services/interfaces.
- Users may have multiple roles.
- Guardian access must later be explicit through guardian_student.
- Combined family billing must not broaden guardian visibility.
- Student grade/section history must use enrollments/placements later.
- Financial workflows must use database transactions later.
- Promotion must be draft/confirm and atomic later.

## Completed Modules
- Academic Foundation
- Identity and Teacher Foundation
- Families and Guardians (Phase 3)
- Students and Enrollments (Phase 4, verified and pending commit)

## Current Module
- Phase 4: Students and Enrollments is complete / pending commit.

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
