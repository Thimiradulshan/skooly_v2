# Phase Status

## Current Phase
Phase 10B-2: Web Auth & Route Protection

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
- Phase 7D: Payment Reminders / Notification Foundation - complete
- Phase 8: Student Promotion - complete
- Phase 9A: Authorization & Guardian Privacy Hardening - complete
- Phase 9B: Audit Logs - complete
- Phase 9C: Backend Hardening & Final Foundation Review - complete
- Phase 10A: Backend Workflow Actions / Service Layer Completion - complete
- Phase 10B-1: Web Layer for Core Registration Workflows - complete
- Phase 10B-2: Web Auth & Route Protection - complete / pending commit

## Current Status
Phase 10B-2 implementation and verification are complete. Pending review and commit.

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
- Promotion is draft-then-confirm. CreatePromotionBatch never changes Student or Enrollment records.
- promotion_batches has status draft, confirmed, or discarded. No unique constraint on year pairs, so multiple drafts are allowed.
- Draft items cover active students in the selected source sections and source academic year.
- Default action is promote with target grade = next Grade by sequence_order and target section = same-named section in that grade.
- If no next grade exists the default action becomes graduate.
- If the target section cannot be resolved, target_section_id stays null and confirmation fails until it is valid.
- Confirmation creates target-year Enrollments only. Source-year Enrollments are never modified.
- Graduated students get Student status graduated and no target Enrollment. Excluded students are skipped.
- Confirmation runs in a DB transaction, so a single invalid item rolls back every target Enrollment, status change, and item update.
- Promotion never generates next-year fee Due Items.
- Guardian has no User link, so no Guardian authentication exists. Guardian privacy is enforced through query helpers, not Gate.
- User::hasAnyRole() joins the existing roles relationship and Role constants. No new roles were added.
- Policies exist for Student, StudentDueItem, Payment, Receipt, PaymentReminder, and PromotionBatch.
- Admin may view and manage all covered records. Accountant may view and manage finance records only.
- Teacher access is denied everywhere, including financial details.
- AuthorizeGuardianStudentAccess returns true only for an explicit guardian_student link.
- ListGuardianVisibleStudents and ListGuardianVisibleDueItems filter strictly through guardian_student.
- Family membership and combined billing never broaden guardian visibility.
- audit_logs is append-only with a nullable actor, polymorphic auditable, small metadata, and occurred_at.
- RecordAuditLog is an explicit action call. No observers were introduced, so audit stays deterministic in tests.
- Payment::recordManual logs payment_recorded plus one payment_allocation_recorded per allocation, inside its existing transaction.
- CreatePromotionBatch and ConfirmPromotionBatch log promotion_batch_created and promotion_batch_confirmed.
- The recurring, event, and reminder generation actions log once per run, never once per record.
- Confirmation audit logs roll back with the transaction, so a failed promotion writes nothing.
- Payment recording, promotion, and generation business rules are unchanged by audit logging.
- Phase 9C hardening review found no application code defects and required no code changes.
- Destructive-action protections are already DB-enforced with restrictOnDelete across payments, allocations, receipts, due items, reminders, and promotion source enrollments.
- Provenance pointers (due_item_discounts.discount_id, promotion_batch_items.applied_enrollment_id, audit_logs.actor_user_id) intentionally null on source deletion and are covered by tests.
- Sequential payments cannot overpay a partially paid due item, and a settled due item reaches exactly zero rather than a negative balance.
- Audit log metadata and due item discount snapshots stay historical after the source record is edited or deleted.
- CreateFamily creates a Family plus optional Guardians, and never links Guardians to Students.
- UpdateFamily edits only family_code, address, home_contact_no, and combined_billing_enabled, and never merges Families.
- LinkGuardianToStudent requires the same Family and is idempotent. Family membership alone still grants nothing.
- RegisterStudent defaults status to pending_registration, links only explicitly passed Guardians, optionally creates one Enrollment, and validates that Section belongs to Grade.
- RegisterStudent never generates StudentDueItems and never activates the Student.
- ApplyStudentDiscount creates a Discount for one Student and FeeCategory and never touches existing due items.
- CreateFeeStructure creates academic-year scoped configuration and never rewrites existing due items.
- The four previously deferred audit constants are now wired: student_registered, family_created, family_updated, discount_applied.
- Web controllers are thin. They validate with Form Requests and delegate to the Phase 10A actions.
- No business rule lives in a controller or a view.
- Family web routes: index, create, store, show, edit, update. No delete route exists.
- Student registration web routes: create and store under a Family.
- Audit logs are produced only by the actions, never by controllers.
- Registration still defaults to pending_registration and still creates no StudentDueItem.
- Minimal session login and logout exist at /login and /logout using Laravel session auth only.
- Login regenerates the session; logout invalidates the session and regenerates the CSRF token.
- Family and student registration web routes are wrapped in auth plus role:Admin.
- EnsureUserHasRole is registered as the role middleware alias in bootstrap/app.php.
- Web Form Requests also require an authenticated Admin, as defense in depth.
- Teacher, Accountant, and role-less authenticated users receive 403 on those routes.
- No auth package was installed. No API auth, API routes, or mobile endpoints exist.

## Verification Result
Passed on 2026-09-30:
- php artisan migrate:fresh --no-interaction
- php artisan test --compact: 221 tests, 743 assertions
- php vendor/bin/phpstan analyse: 0 errors
- php vendor/bin/pint --test
- composer audit: no security vulnerability advisories
- git diff --check passed

## Blockers
None.

## Next Exact Step
Review git status, then commit Phase 10B-2.
