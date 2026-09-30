# Skooly Active Context

Use this file before broad project reinspection.

## Current Status
- Phase 1 Academic Foundation: complete.
- Phase 2 Users, Roles & Teachers: complete after final MySQL verification.
- Phase 3 Families & Guardians: complete.
- Phase 4 Students & Enrollments: complete.
- Phase 5 Fees, Dues & Discounts: complete.
- Phase 6 Payments & Receipts: complete and verified; pending commit.
- MySQL fixed by removing obsolete MySQL 8.4 settings:
  - innodb_file_format=Barracuda
  - innodb_large_prefix=ON

## Database
- DB_CONNECTION=mysql
- DB_DATABASE=skooly
- MySQL version verified: 8.4.7
- Laravel config/database.php uses InnoDB for MySQL.

## Architecture Rules
- Family-based billing.
- Family is the household registration and billing unit, with a unique family_code.
- A Family has many Guardians; each Guardian belongs to one Family.
- A Family with Guardians cannot be deleted.
- Combined billing defaults to enabled but does not grant Guardian access to students.
- Guardian access to Students is explicit through guardian_student.
- Students belong to a Family and have globally unique admission_no values.
- Student status defaults to pending_registration; payment-driven status transitions are deferred.
- Grade and Section are stored on year-specific Enrollments, never on Students.
- Enrollment::placeIn() is transactional and preserves EnrollmentPlacement history.
- Database constraints prevent a Section from being paired with a different Grade in Enrollments or placement history.
- Fee structures are academic-year configuration; StudentDueItems preserve generated amount snapshots.
- Discounts target a Student and FeeCategory, and DueItemDiscount preserves applied snapshot data.
- DueItemDiscount.discount_id is nullable and nulls on source Discount deletion so historical snapshots remain.
- StudentFeeSubscription supports future opt-in fee categories without subscription workflows.
- Payments are family-level; manual allocation only.
- StudentDueItem tracks paid_amount, balance_amount, and status.
- Receipt snapshots are immutable; later due item changes do not rewrite receipt.
- Payment recording uses DB transaction for atomicity.
- Users may have multiple roles.
- No Spatie permissions yet.
- Financial workflows must use DB transactions later.
- Promotion must be draft/confirm, atomic, and must not overwrite historical enrollments.

## Phase 6 Deferred Work
- Automatic allocation strategies (even-split, oldest-first)
- Scheduled recurring due generation.
- Payment-driven student registration activation.
- Dashboards.
- Reminders.
- Events.
- UI/controllers/routes.
- Promotion.
- Audit.

## Verification Command Order
php artisan migrate:fresh
php artisan test
php vendor/bin/phpstan analyse
php vendor/bin/pint --test
composer audit
git diff --check
git status

## Latest Verification
- 2026-09-30: migrate:fresh passed; 52 tests / 130 assertions passed; PHPStan and Pint passed; Composer audit found no advisories; git diff --check passed.
