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
- Phase 7C Dues Dashboard / Reporting Queries: complete.
- Phase 7D Payment Reminders / Notification Foundation: complete.
- Phase 8 Student Promotion: complete.
- Phase 9A Authorization & Guardian Privacy Hardening: complete.
- Phase 9B Audit Logs: complete.
- Phase 9C Backend Hardening & Final Foundation Review: complete.
- Phase 10A Backend Workflow Actions / Service Layer Completion: complete.
- Phase 10B-1 Web Layer for Core Registration Workflows: complete.
- Phase 10B-2 Web Auth & Route Protection: complete.
- Phase 10B-3 Web Fee & Discount Management: complete.
- Phase 10B-4 Web Due Generation & Dashboard Pages: complete.
- Phase 10B-5 Web Payment Collection & Receipt Pages: complete and verified; pending commit.
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
- payment_reminders is an internal outbox table. No external channel sends anything.
- Reminder eligibility requires an outstanding balance, an unpaid or partially_paid status, and a due date.
- Guardian reminder eligibility uses explicit guardian_student links, never family membership alone.
- Combined billing families receive one consolidated reminder per Guardian and Family.
- Reminder generation never creates payments, receipts, or allocations, and never changes due item balances.
- Promotion is draft-then-confirm. Draft creation never modifies Student or Enrollment records.
- Confirmation creates target-year Enrollments atomically and never modifies source-year Enrollments.
- Graduated students get status graduated with no target Enrollment. Excluded students are skipped.
- Promotion never generates next-year fee Due Items.
- Promotion reversal is deferred because the safety window is unresolved.
- No Spatie permissions. Authorization uses role checks through the existing roles relationship.
- Guardian has no User link, so Guardian privacy is enforced by query helpers, not Gate.
- Guardian access requires an explicit guardian_student link. Family membership and combined billing never grant sibling visibility.
- Teacher access is denied, including financial details.
- Audit logs are append-only and written only through RecordAuditLog. No observers or packages.
- Payment recording, payment allocations, and promotion create/confirm are audited inside their transactions.
- Recurring, event, and reminder generation each log once per run.
- A failed transaction writes no audit entries.
- Historical and financial tables are protected by the database with restrictOnDelete, not by application code.
- Provenance pointers null out on source deletion so the local record survives.
- Balance correctness is enforced in the payment action, including against sequential payments.
- No DB CHECK constraint currently prevents a negative balance_amount; that remains an open decision.
- All business workflows live in app/Actions. Controllers, routes, and requests still do not exist.
- RegisterStudent defaults to pending_registration, links only explicit Guardians, and never generates due items.
- LinkGuardianToStudent is the only path to Guardian access and requires the same Family.
- Student activation after registration payment is deferred because registration dues are not identifiable.
- Web controllers live in app/Http/Controllers/Web and only validate plus delegate to app/Actions.
- Web validation lives in app/Http/Requests/Web. Views use a single plain-HTML layout.
- Web routes have no auth middleware yet, so FormRequest::authorize() returns true.
- No delete route, API controller, or frontend framework exists.
- Minimal session login and logout exist. No auth package is installed.
- Family and student registration web routes require auth plus role:Admin.
- Admin is the only role allowed on those routes. Teacher, Accountant, and role-less users get 403.
- Web Form Requests repeat the Admin check as defense in depth.
- Admin web pages exist for fee categories, fee structures, student discounts, and opt-in fee subscriptions.
- Fee structure web editing is deferred. Fee structures stay academic-year versioned.
- No fee, discount, or subscription delete route exists.
- Only opt-in fee categories can be subscribed, enforced in the form request and the action.
- Admin web pages exist for recurring due generation, event due generation, and the dues dashboard.
- Both generation flows call the existing actions and pass the Admin as the audit actor.
- The dashboard reuses BuildDuesDashboardReport and never recalculates money in Blade.
- Due generation is manual. No scheduler or cron entry is registered.
- Admin web payment collection calls Payment::recordManual() and supports manual allocation only.
- Only outstanding due items for the selected Family are shown in the payment form.
- Receipt pages render stored snapshots and never recalculate from live due items.
- No payment edit, refund, delete, automatic allocation, or receipt export route exists.
- Users may have multiple roles.
- No Spatie permissions yet.
- Financial workflows must use DB transactions later.
- Promotion must be draft/confirm, atomic, and must not overwrite historical enrollments.

## Phase 10B-5 Deferred Work
- Scheduler and cron setup for recurring generation.
- Event management UI.
- Payment edit, delete, refund, receipt delete, and receipt PDF export.
- Payment reporting/index UI.
- Automatic allocation, even-split allocation, and oldest-first allocation.
- Online payment gateways.
- Promotion UI and reminder sending UI.
- Accountant and Teacher web access.
- API auth, API controllers, and mobile endpoints.
- Advanced user management, password reset, and email verification.
- Delete and destructive web routes.
- Fee structure web editing (structures are academic-year versioned).
- Audit entries for fee category and fee subscription workflows.
- Automatic sibling discount rule.
- Automatic payment allocation strategy.
- ActivateStudentAfterRegistrationPaid (registration dues are not identifiable).
- Guardian login and a Guardian-to-User link.
- Teacher section-scoped student access.
- Accountant student visibility decision.
- Promotion reversal (safety window unresolved).
- DB-level CHECK constraint for non-negative balances.
- CSV/PDF export.
- Audit UI and export.

## Verification Command Order
php artisan migrate:fresh
php artisan test
php vendor/bin/phpstan analyse
php vendor/bin/pint --test
composer audit
git diff --check
git status

## Latest Verification
- 2026-09-30: migrate:fresh passed; 265 tests / 915 assertions passed; PHPStan and Pint passed; Composer audit found no advisories; git diff --check passed.
