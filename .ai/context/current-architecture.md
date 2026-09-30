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
- Recurring Fee Due Generation (Phase 7A)
- Events Generating Due Items (Phase 7B)
- Dues Dashboard / Reporting Queries (Phase 7C)
- Payment Reminders / Notification Foundation (Phase 7D)
- Student Promotion (Phase 8)
- Authorization & Guardian Privacy Hardening (Phase 9A)
- Audit Logs (Phase 9B)
- Backend Hardening & Final Foundation Review (Phase 9C)
- Backend Workflow Actions / Service Layer Completion (Phase 10A)
- Web Layer for Core Registration Workflows (Phase 10B-1)
- Web Auth & Route Protection (Phase 10B-2)
- Web Fee & Discount Management (Phase 10B-3)
- Web Due Generation & Dashboard Pages (Phase 10B-4)
- Web Payment Collection & Receipt Pages (Phase 10B-5)
- Web Event Management Pages (Phase 10B-6)
- Web Student Promotion Pages (Phase 10B-7)
- Web Payment Reminder Pages (Phase 10B-8)
- Web Admin Usability & Navigation Polish (Phase 10B-9, verified and pending commit)

## Current Module
- Phase 10B-9: Web Admin Usability & Navigation Polish is complete / pending commit.

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

## Events Generating Due Items
- Event belongs to an AcademicYear and a FeeCategory, with name, event_date, is_mandatory, and confirmed_at.
- EventCharge holds a per-grade amount; a uniform charge is represented by equal amounts on several charges.
- EventParticipation records opted_in or opted_out for opt-in events.
- app/Actions/Events/GenerateEventDueItems.php generates StudentDueItems and links them via event_due_items.
- Event dues are StudentDueItems and participate in billing like any other fee.
- Changing an EventCharge later never rewrites an already-generated StudentDueItem.

## Dues Dashboard / Reporting Queries
- app/Actions/Reports/BuildDuesDashboardReport.php is read-only and returns plain arrays.
- It adds no tables and modifies no money records.
- Totals are read from stored StudentDueItem snapshot fields.
- Filters: academic year, grade, section, date range, fee category, family.
- Family and student balance summaries are available, sorted by balance descending.
- Money is normalized to two-decimal strings without floating-point arithmetic.

## Payment Reminders / Notification Foundation
- payment_reminders is an internal outbox table. Nothing is sent.
- app/Actions/Notifications/GeneratePaymentReminders.php builds records for upcoming and overdue due items.
- Eligibility requires an outstanding balance, an unpaid or partially_paid status, and a due date.
- Guardian eligibility uses explicit guardian_student links only.
- Combined billing families receive one consolidated reminder per Guardian and Family.
- A deterministic reminder_key makes re-running for the same as_of_date safe.

## Student Promotion
- PromotionBatch is draft-then-confirm with source and target academic years.
- CreatePromotionBatch lists active students in the selected source sections and creates editable draft items only.
- Default target grade is the next Grade by sequence_order; the default action is graduate when none exists.
- ConfirmPromotionBatch runs in a DB transaction and creates target-year Enrollments atomically.
- Source-year Enrollments are never modified. Graduated students get status graduated with no target Enrollment.
- Excluded students are marked skipped. Promotion never generates next-year fee Due Items.
- Reversal is not implemented because the safety window is unresolved.

## Authorization & Guardian Privacy
- No Spatie permissions. Authorization uses simple role checks through the existing roles relationship.
- User::hasAnyRole() complements the existing hasRole(). No new roles were added.
- Policies cover Student, StudentDueItem, Payment, Receipt, PaymentReminder, and PromotionBatch.
- Admin can view and manage all covered records. Accountant can view and manage finance records only.
- Teacher access is denied, including financial details.
- Guardian has no User link, so Guardian access is enforced by query helpers instead of Gate.
- AuthorizeGuardianStudentAccess checks guardian_student only. Family membership is never sufficient.
- ListGuardianVisibleStudents and ListGuardianVisibleDueItems are the entry points for future Guardian-facing screens.

## Audit Logs
- audit_logs is append-only with a nullable actor, polymorphic auditable, small metadata, and occurred_at.
- app/Actions/Audit/RecordAuditLog.php is the only writer. No observers or packages are used.
- Payment recording logs payment_recorded and one payment_allocation_recorded per allocation.
- Promotion batch creation and confirmation are logged inside their existing transactions.
- Recurring, event, and reminder generation log once per run.
- A failed transaction writes no audit entries, so the log never claims a change that rolled back.

## Backend Hardening
- Historical and financial tables are protected by the database with restrictOnDelete, not by application code.
- Provenance pointers deliberately null out on source deletion so the local record survives.
- Balance correctness is enforced in the payment action, including against sequential payments.
- Snapshot tables (receipts, due_item_discounts, audit_logs.metadata) are never recomputed from live records.

## Backend Workflow Actions
- app/Actions holds every business workflow. Controllers, routes, and requests are still absent.
- CreateFamily and UpdateFamily manage the household registration and billing unit.
- RegisterStudent registers a Student under an existing Family and may create one initial Enrollment.
- LinkGuardianToStudent is the only way a Guardian gains access to a Student.
- ApplyStudentDiscount records a discount; due generation later applies and snapshots it.
- CreateFeeStructure records academic-year scoped configuration.
- All multi-write workflows run inside a DB transaction and audit through RecordAuditLog.

## Web Layer
- app/Http/Controllers/Web holds thin web controllers. app/Http/Requests/Web owns web validation.
- Controllers only transform validated input and delegate to app/Actions.
- resources/views uses a single layouts/app layout with plain HTML and no frontend framework.
- Web routes currently cover family browsing, family create/update, and student registration only.
- No authentication middleware, login, API, resource, or delete route exists yet.

## Web Auth
- Session login and logout live in app/Http/Controllers/Auth/LoginController with LoginRequest validation.
- EnsureUserHasRole is registered as the role middleware alias in bootstrap/app.php.
- Family and student registration routes require auth plus role:Admin. Admin is the only allowed role today.
- Web Form Requests repeat the Admin check, so protection does not depend on route configuration alone.
- No auth package, API auth, or token scheme exists.

## Web Fee and Discount Management
- Admin web pages cover FeeCategory list, create, and edit, plus FeeStructure list and create.
- FeeStructure web editing is deliberately absent because structures are academic-year versioned.
- Student discounts and opt-in fee subscriptions are managed per student from the family page.
- Discount application reuses ApplyStudentDiscount, so discount_applied auditing still works.
- CreateFeeStructure and CreateStudentFeeSubscription never create or modify StudentDueItems.
- No delete route exists for any fee, discount, or subscription resource.

## Web Due Generation and Dashboard
- DueGenerationController triggers GenerateRecurringDueItems and GenerateEventDueItems and nothing else.
- Both generation flows pass the authenticated Admin as the audit actor.
- Event generation only targets existing events. There is no event management UI.
- DuesDashboardController renders BuildDuesDashboardReport output without recalculating any amount.
- DuesDashboardFilterRequest mirrors the report rule that grade and section need an academic year.
- Due generation is manual. No scheduled task or cron entry is registered.

## Web Payment Collection and Receipts
- PaymentCollectionController is a thin adapter to Payment::recordManual(). It has no allocation business logic.
- StoreManualPaymentRequest provides user-friendly validation for totals, family ownership, and current balances; Payment::recordManual() remains authoritative.
- ReceiptController renders the stored Receipt snapshot only.
- The payment UI is Admin-only and supports manual collection, payment viewing, and receipt viewing without mutation routes.

## Web Event Management
- EventController uses CreateEvent and UpdateEvent; it does not confirm or generate dues.
- EventChargeController creates per-grade charges only; editing is deliberately absent.
- EventParticipationController uses SetEventParticipation and preserves the existing opted_in and opted_out statuses.
- GenerateEventDueItems remains the only creator of EventDueItem and StudentDueItem records.
- No event management deletion, payment, receipt, or audit workflow exists.

## Web Student Promotion
- PromotionBatchController creates drafts through CreatePromotionBatch and confirms drafts through ConfirmPromotionBatch.
- The web layer accepts only source/target academic years and source section IDs, the complete existing creation contract.
- Draft items remain derived and read-only in the web layer; confirmation uses their existing backend targets and actions.
- Source enrollments are never modified. Target enrollments are created only on confirmation, within the existing transaction.
- No promotion reversal, export, destructive route, or next-year due generation exists.

## Web Payment Reminders
- PaymentReminderController lists and previews internal reminder records and delegates generation to GeneratePaymentReminders.
- Reminder generation can be limited by academic year or family, the exact action-supported filters.
- The detail page renders due_item_ids and message_snapshot without recalculating content or sending a channel message.
- No send, edit, delete, status-transition, queue, scheduler, or Guardian web workflow exists.

## Web Admin Usability & Navigation
- AdminDashboardController serves a read-only /admin landing page with COUNT summaries and workflow link cards.
- The root route always redirects: guests to login, authenticated users to the admin dashboard.
- The shared layout exposes one grouped admin navigation row plus consistent success, error, and validation flash display.
- Cross-links reuse existing named routes only; empty states already existed on every index page.
- No backend action, model, authorization rule, or FormRequest changed in this phase.
