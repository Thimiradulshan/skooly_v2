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
Status: complete / pending commit. Verified on 2026-09-30.

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

Deferred:
- Fee due generation
- Payment-driven Student status transitions
- Attendance
- Student UI/controllers/routes
- Photo upload behavior
- Events
- Promotion
- Audit
