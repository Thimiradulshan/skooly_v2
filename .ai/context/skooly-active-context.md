# Skooly Active Context

Use this file before broad project reinspection.

## Current Status
- Phase 1 Academic Foundation: complete.
- Phase 2 Users, Roles & Teachers: complete after final MySQL verification.
- Phase 3 Families & Guardians: complete.
- Phase 4 Students & Enrollments: complete.
- Phase 5 Fees, Dues & Discounts: complete.
- Phase 6 Payments & Receipts: complete.
- Phase 7A Recurring Fee Due Generation: complete.
- Phase 7B Events Generating Due Items: complete.
- Phase 7C Dues Dashboard / Reporting Queries: complete and verified; pending commit.
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
- FeeCategory.is_opt_in (default false) gates categories requiring an active StudentFeeSubscription.
- GenerateRecurringDueItems creates StudentDueItems from recurring FeeStructures for enrolled students.
- Generated dues have paid_amount 0 and status unpaid; generation never creates payments or receipts.
- Discounts are applied at generation time and snapshotted; value_type is amount or percentage.
- generation_key is deterministic and prevents duplicate dues for the same cycle.
- Event belongs to an AcademicYear and a FeeCategory; no event fee category name is hardcoded.
- Mandatory events generate dues for all applicable enrolled students; opt-in events only for opted_in students.
- EventCharge amount is snapshotted into StudentDueItem and never rewritten.
- event_due_items links each generated StudentDueItem back to its Event.
- Event generation never creates payments or receipts.
- BuildDuesDashboardReport is read-only and reads stored StudentDueItem snapshot balances.
- Dashboard filters: academic year, grade, section, date range, fee category, family.
- grade_id or section_id require academic_year_id because enrollment is year-specific.
- Family and student balance summaries are available; no UI/controllers/routes exist yet.
- Users may have multiple roles.
- No Spatie permissions yet.
- Financial workflows must use DB transactions later.
- Promotion must be draft/confirm, atomic, and must not overwrite historical enrollments.

## Phase 7C Deferred Work
- Participation and payment-status reporting.
- Scheduled automation or cron command.
- Automatic payment allocation strategies.
- Automatic sibling discount rule.
- Payment-driven student registration activation.
- Family combined billing aggregation.
- Reminders.
- Frontend dashboards.
- Event notifications.
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
- 2026-09-30: migrate:fresh passed; 97 tests / 295 assertions passed; PHPStan and Pint passed; Composer audit found no advisories; git diff --check passed.
