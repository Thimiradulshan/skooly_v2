# Open Business Decisions

Do not invent answers for these.

## Global Unresolved Decisions
- Default payment allocation strategy remains unresolved.
- Default sibling discount percentage/rule remains unresolved.
- Promotion reversal safety window remains unresolved.
- Duplicate-family detection: block versus warning remains unresolved.

## Family and Guardian Rules
- family_code is unique; duplicate-family detection beyond that remains unresolved as block versus warning.
- Do not make guardian email, contact_no, or nic unique yet unless confirmed.
- Guardian visibility is controlled by guardian_student, not family membership alone.
- Combined billing is enabled by default for a family, but its future payment-allocation strategy remains unresolved.

## Student Rules
- admission_no is required and globally unique; auto-generation is not implemented.
- Student status is a simple string and defaults to pending_registration.
- Pending Registration must remain until registration due items are paid, but fee generation and payment-driven status transitions are deferred.
- gender is stored as an unconstrained required string; no enum or SRS value set has been defined.
- photo_path is nullable; upload and storage behavior are deferred.

## Phase 5 Rules
- Default payment allocation strategy remains unresolved; no payment or allocation workflow exists.
- Default sibling discount percentage/rule remains unresolved; no automatic sibling discount suggestion or application exists.
- Discounts are per Student and FeeCategory and are snapshotted to DueItemDiscount when a future due-generation workflow applies them.
- Fee due generation is schema-ready only; recurring scheduling is deferred.

## Phase 6 Rules
- Payments are family-level; allocations are manual only.
- No automatic even-split or oldest-first allocation is implemented.
- StudentDueItem tracks paid_amount, balance_amount, and status.
- Receipt snapshots are immutable; later due item changes do not rewrite receipt.
- Payment recording uses DB transaction for atomicity.

## Phase 7A Rules
- Opt-in fee categories are declared explicitly with FeeCategory.is_opt_in. No name-based detection is used.
- is_opt_in defaults to false. When true, an active StudentFeeSubscription is required.
- Discount value_type vocabulary is amount or percentage. A null value_type is treated as amount.
- Automatic payment allocation remains unresolved and is not implemented.
- Automatic sibling discount rule remains unresolved and is not implemented.
- generation_key is the duplicate-prevention mechanism; it must stay deterministic.

## Phase 7B Rules
- Event must reference fee_category_id. Never hardcode or match an event fee category name.
- EventCharge amount is per grade and is snapshotted at generation time.
- EventParticipation status is opted_in or opted_out. Mandatory events ignore participation rows.
- Duplicate generation is prevented by a deterministic generation_key.
- Event generation never creates payments, receipts, or automatic payment allocation.
- Participation and payment-status reporting is deferred.

## Phase 7C Rules
- Reporting is strictly read-only. It must never write to due items, payments, allocations, or receipts.
- Dashboard totals come from stored StudentDueItem snapshots, not recomputed values.
- grade_id and section_id are only valid together with academic_year_id because enrollment is year-specific.
- No new payment allocation strategy is introduced by reporting.
- Frontend dashboards, reminders, and notifications remain deferred.

## Phase 7D Rules
- PaymentReminder is an internal outbox record only. No external channel sends anything yet.
- Reminder eligibility must use explicit guardian_student links. Family membership alone is never sufficient.
- Combined billing consolidates into one reminder per Guardian and Family. Non-combined families get one per due item.
- Reminder generation must never create payments, receipts, or allocations, and must not modify due item balances or statuses.
- reminder_key must stay deterministic so re-running for the same as_of_date is safe.
- Reminder timing, templates, and channels are deferred.

## Phase 8 Rules
- Promotion is draft-then-confirm. Nothing applies until ConfirmPromotionBatch runs.
- Source-year Enrollments must never be modified. Promotion only creates target-year Enrollments.
- Promotion must never generate next-year fee Due Items automatically.
- Promotion confirmation must stay atomic: all target Enrollments or none.
- Promotion reversal safety window remains unresolved, so reversal is not implemented.
- Student.grade_id and Student.section_id must never exist; grade and section live on Enrollment.

## Phase 9A Rules
- Guardian privacy is enforced only through guardian_student links. Family membership never grants access.
- Combined billing must never expose a sibling's due items, payments, or academic data to an unlinked Guardian.
- Teacher financial access is denied. Teacher student access is denied because section scope cannot be proven from current models.
- Accountant student visibility is an unresolved decision; StudentPolicy currently allows Admin only.
- Guardian login and a Guardian-to-User link remain deferred because no such link exists today.
- Audit logs remain deferred to Phase 9B.

## Phase 9B Rules
- Audit logs are append-only. No update or delete audit workflow exists.
- Audit entries are written by explicit RecordAuditLog calls, not observers, so behaviour stays testable.
- actor_user_id stays nullable because backend actions may run without a user context.
- Payment and promotion audit entries must be written inside the same transaction as the change they describe.
- student_registered, family_created, family_updated, and discount_applied constants exist but are not yet wired to workflows, because those controllers/actions do not exist.
- Do not invent controllers purely to produce audit entries.

## Phase 9C Findings
- No DB-level CHECK constraint prevents a negative StudentDueItem.balance_amount. The payment path guards this in application code only. Adding a CHECK constraint remains an open decision.
- No money column uses an unsigned type, consistent with the existing decimal(12,2) convention.
- No new open decisions were introduced. Hardening required no code changes.
