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

## Phase 10A Rules
- Student activation after registration payment remains deferred. There is no explicit way to identify registration mandatory Due Items, so no activation action was written.
- FeeCategory has no registration flag, and name-matching a category such as "Registration" is forbidden.
- CreateFeeStructure and LinkGuardianToStudent are intentionally not audited because no existing audit constant covers them and none may be invented.
- Workflow actions never generate due items, never activate students, and never grant Guardian access by family membership.

## Phase 10B-1 Rules
- Web controllers must stay thin. They validate and delegate to app/Actions and never re-implement business rules.
- Form Requests in app/Http/Requests/Web own all web input validation.
- No authentication or authorization middleware is applied yet, so FormRequest::authorize() returns true. Login and route protection remain deferred.
- No delete or destructive web route exists yet.
- No API controllers, resources, or mobile endpoints exist yet.
- Audit entries are produced by actions only. Controllers must not write audit logs.

## Phase 10B-2 Rules
- Web access is Admin only for family and student registration pages. Accountant and Teacher are denied until their exact web permissions are confirmed.
- No auth package may be installed. Session auth plus the role middleware alias is the whole mechanism.
- Route middleware is the primary gate; Web FormRequest::authorize() repeats the Admin check as defense in depth.
- Login failures must never reveal whether the email exists.
- The root route and the login routes stay public.

## Phase 10B-3 Rules
- Fee structure web editing stays deferred. Fee structures are academic-year versioned, so changing one is a separate decision.
- Fee category and fee subscription workflows stay unaudited until audit constants exist for them.
- Only opt-in fee categories may be subscribed, enforced by both the form request and the action.
- Discounts are never applied retroactively to existing StudentDueItems.
- No delete or destructive web route may be added without an explicit decision.

## Phase 10B-4 Rules
- Due generation stays a manual Admin web action. No scheduler or cron entry may be added without a decision.
- The dashboard must reuse BuildDuesDashboardReport and must never recalculate money in Blade.
- The dashboard must require academic_year_id whenever grade_id or section_id is supplied.
- Event due generation may only target events that already exist. Event management UI stays deferred.
- Payment recording, allocation, and receipt pages stay deferred.

## Phase 10B-5 Rules
- Payment collection is manual only. The form never selects allocations automatically.
- Receipt numbers are entered manually because Payment::recordManual() requires a unique receipt number and no numbering rule exists.
- The form validates allocations for a selected Family, but Payment::recordManual() remains the final transaction and balance guard.
- Payment edits, deletes, refunds, receipt export, and online gateways remain deferred.

## Phase 10B-6 Rules
- Event management uses only existing Event, EventCharge, and EventParticipation fields and constants.
- Event charges are create-only in the web layer because charges can already have generated due item snapshots.
- Event participation does not generate due items; GenerateEventDueItems remains the sole generation action.
- No event management audit constant exists, so event create/update, charges, and participation workflows are unaudited.
- Event deletion stays deferred because generated EventDueItem records are historical.

## Phase 10B-7 Rules
- Web promotion batch creation supports only the existing action signature: source year, target year, and source section IDs.
- Target mappings are derived by CreatePromotionBatch. Per-item editing needs a separate backend workflow decision.
- ConfirmPromotionBatch remains the only confirmation path and stays atomic.
- Promotion reversal safety window remains unresolved, so no reversal route or workflow exists.
- Promotion web workflows never generate StudentDueItems or next-year fees.

## Phase 10B-8 Rules
- Payment reminder web generation supports only the existing GeneratePaymentReminders action contract; it always considers both upcoming and overdue due items.
- Reminder pages are internal outbox previews only. They must not send a message, change status, or infer Guardian visibility.
- Guardian privacy remains enforced by the generation action through explicit guardian_student links.
- No reminder channel, delivery provider, queue, or scheduling rule has been chosen.

## Phase 10B-9 Rules
- Usability polish must not change backend actions, models, or FormRequest authorization.
- The admin dashboard is read-only: COUNT summaries only, no money calculation in Blade.
- The root route always redirects (login for guests, dashboard for authenticated users) and never renders content.
- Cross-links may only use existing named routes; no route may be invented for a link.
- No new Accountant or Teacher permissions may be introduced by navigation changes.

## Phase 10B-10 Rules
- QA fixes only real bugs. Confirmed working behaviour must not be changed.
- Confirmed behaviours from QA: promotion lists only active students, and post-login still lands on /families.

## Phase 10C-1 Rules
- Demo data is local and testing only. DemoDataSeeder must never run in production.
- Demo data must be produced by existing actions, never by duplicated generation logic.
- Demo seeding must stay idempotent so it can be re-run during development.
- Demo credentials are throwaway and must be changed before any real deployment.
- Demo reminders are internal outbox records and must never be sent anywhere.

## Phase 10C-2 Rules
- .env.example must contain placeholders only, never a real APP_KEY, password, or token.
- DemoDataSeeder and DatabaseSeeder must both stay inert when APP_ENV=production.
- Production deployment must never rely on a seeded account. Admin credentials are created on the server.
- Known security gaps are documented rather than silently fixed, so no auth redesign happens without a decision.
- Login throttling, password reset, email verification, and 2FA remain deferred until explicitly requested.

## Phase 10C-3 Rules
- Documentation only. Do not change business logic, models, migrations, or controllers to make the docs look nicer.
- Do not resolve an open business decision while documenting it. Record the decision and what it blocks instead.
- UI/UX work must not start for a screen whose shape depends on an unresolved decision.
- Honest status reporting is required. Do not describe a partial module as complete.

## Phase 10C-4 Rules
- Presentation only. Backend logic, models, migrations, and controllers must not change for visual work.
- Route names and submitted field names must never change for layout or styling work.
- Visual QA may be code and test based when no browser is available, but that limitation must be stated.

## Phase 10C-4B Rules
- Do not adopt the Vite and Tailwind pipeline while public/build is gitignored, because every page would break on a fresh checkout.
- Prefer a static stylesheet and dependency-free script over a CDN or a package that needs a build.
- UI enhancements must degrade gracefully: forms still submit if the script fails to load.

## Phase 10C-4C Rules
- Only existing named routes may be linked. Never add a button for a route that does not exist.
- Irreversible-looking actions must be marked with data-confirm, and every write form with data-loading.
- Never claim a notification was sent when no delivery provider exists.

## Phase 10C-5 Findings
- Academic setup has no web UI, so school staff cannot configure the school without a console.
- Payment correction or refund does not exist and must not be designed without approval. See docs/destructive-action-policy-draft.md.
- Archive semantics are undefined: hidden from new selections only, or hidden from reports too.
- Fee structure and event charge editing are unresolved because due items already reference them.
- Promotion item editing needs a decision before any backend edit action is written.
- SchoolSetting.active_academic_year_id is inert and needs a decision on whether anything should honour it.
- Money and history must never be hard deleted. Configuration should be archived, pending approval.

## Phase 10D-1A Rules
- Academic year, term, grade, and section web pages support list, create, view, edit, and update only.
- Archive/deactivate remains deferred: none of the four tables has a lifecycle status, and archive semantics remain unresolved.
- No hard delete or archive route exists for academic setup.
- SchoolSetting.active_academic_year_id may be selected by an Admin but remains inert until a separate decision assigns downstream behaviour.

## Phase 10D-1B Rules
- Audit-log viewing is Admin-only and read-only.
- Audit pages display only stored AuditLog data and never create, update, delete, or recompute an audit entry.
- Audit-log filtering, export, and retention remain deferred; no retention period or export format has been chosen.
- Audit viewing must remain readable when actor_user_id or the polymorphic auditable source is null.

## Phase 10D-2 Rules
- Roles are fixed: Superadmin, Admin, Accountant, and Teacher. Role CRUD is not implemented.
- The first Superadmin is assigned to an existing trusted user through the `users:make-superadmin` console command.
- Users are archived through is_active and are never hard deleted. Inactive users cannot authenticate.
- Superadmin manages all accounts. Admin can manage only users without Admin or Superadmin roles.
- The final active Superadmin cannot be archived or stripped of the Superadmin role; users cannot archive themselves.
- Subject deletion is allowed only when no teaching assignment references the Subject.
- Teacher qualifications, teaching assignments, and class-in-charge assignments are list/create/view only; correction and removal rules remain deferred.

## Phase 10D-3 Rules
- Guardian creation and editing do not grant Student visibility. Links remain explicit through guardian_student.
- Guardian-Student unlink is Admin-only, confirmed in the web UI, and audited as guardian_student_unlinked.
- Student edits never change Family membership. Enrollment movement must use Enrollment::placeIn() so placement history is preserved.
- Discount withdrawal sets is_active false and never changes existing due-item snapshots.
- Fee subscription ending sets is_active false plus ends_on and never changes existing due-item snapshots.
- Guardian login, photo upload/storage, discount/subscription lifecycle auditing, and standalone link/enrollment reports remain deferred.

## Phase 10D-4 Rules
- A PromotionBatch may be discarded only while it is draft. Discarding changes only its status and discarded_at; it never changes Students, Enrollments, promotion items, or fees.
- A PaymentReminder may be cancelled only while it is pending. Cancellation changes only its status; it never sends a message or changes its stored snapshot, due items, payments, receipts, or allocations.
- Both workflows are Admin-only, confirmed in the web UI, and preserve history rather than deleting records.
- No audit action constants exist for discard or cancellation, so neither transition writes a new audit entry in this phase.
- Archive/deactivate remains blocked pending the module-specific business decisions documented in docs/destructive-action-policy-draft.md.

## Phase 10D-6 Rules
- Payment and Receipt history is read-only and Admin-only. It does not alter payments, receipts, allocations, due items, or snapshots.
- Search is limited to family code, payment reference, and receipt number. Sort columns and directions are validated allow-lists; no client-provided SQL identifier is used.
- Receipt PDF export, audit-log filtering/export/retention, and payment correction/refund remain deferred pending their documented decisions.

## Phase 10D-7 Rules
- Payment correction is an append-only, full-payment reversal only; the original Payment, Receipt, and PaymentAllocation records are never changed or deleted.
- Exactly one PaymentReversal may exist per original Payment, enforced by a unique original_payment_id.
- Accountant requests require a reason. Admin approval is required, and a dual-role requester may not approve their own request.
- Approval reopens each original allocation atomically: paid_amount decreases, balance_amount increases, and status becomes unpaid at zero paid or partially_paid otherwise.
- Admin enters a unique correction receipt number on approval. CorrectionReceipt snapshots the original Receipt and reversal details; the original receipt stays immutable.
- Request and approval are explicitly audited inside their respective transactions.
- Accountants receive only payment/receipt history and reversal request/list/detail access. Payment collection, family access, and approval remain Admin-only.

## Phase 10D-6B Rules
- Pagination is read-only and uses 20 records per page with query-string preservation across every in-scope Admin list page.
- Text search is limited to relevant stored identifiers and names. It is validated to 100 characters and never supplies a SQL identifier.
- AuditLog remains paginated but unfiltered; audit-log filtering, export, and retention remain deferred.
- Student discount and subscription histories remain scoped to their selected Student and do not gain cross-student search.
