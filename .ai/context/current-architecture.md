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
- Web Admin Usability & Navigation Polish (Phase 10B-9)
- Web Manual QA & Bug Fix Pass (Phase 10B-10)
- Demo Data & Local Testing Setup (Phase 10C-1)
- Deployment Readiness & Security Review (Phase 10C-2)
- System Understanding, Data Flow & UX Map (Phase 10C-3)
- Admin UI/UX Foundation & Login Redesign (Phase 10C-4)
- Commercial Admin UI/UX Redesign (Phase 10C-4B)
- Commercial UI/UX Defect Audit and Workflow Completion (Phase 10C-4C)
- Backend-Frontend Feature Parity and CRUD Coverage Audit (Phase 10C-5, committed in f63c2d5)

## Current Module
- Phase 10D Archive/Deactivate Workflow is implemented; verification is in progress.

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

## Payment Reversals
- PaymentReversal is append-only and has many immutable PaymentReversalAllocation selections tied to original PaymentAllocations. A Payment can have multiple reversal requests.
- Accountants request with a required reason; Admins approve, except a dual-role requester cannot approve their own reversal.
- Request and approval lock the payment and original allocations. Requested amounts must be positive and no more than the remaining amount after approved reversal allocations; approval rechecks that cap and due-item paid/balance/net safety under locks.
- Approval never mutates or deletes the original payment, receipt, or allocations. It reopens only approved selected amounts and creates a CorrectionReceipt with immutable original-receipt and itemized reversal snapshots.
- Payment reversal request and approval use explicit append-only audit entries.
- Accountant web access is limited to finance-only payment collection through an exact family-code lookup, payment/receipt history and detail, payment-reversal visibility/requesting, the Dues Dashboard, and payment-reminder list/detail. Collection exposes only the selected Family's outstanding due-item allocation data; family browsing, reminder mutations, students, guardians, academic setup, staff, events, promotion, approval, and the admin dashboard remain Admin-only.

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
- AuditLogController provides Admin-only index, show, CSV export, and PDF export endpoints. The index filters stored action, actor, auditable type, and occurred-at date range; all paths eager-load only actors and never follow auditable records.
- CSV is streamed and JSON-serializes stored metadata; PDF is generated by Dompdf from a local HTML view. Both exports include a filter summary and use the index query.
- Audit logs are retained forever and remain append-only. No audit-log archive, purge, create, update, or delete workflow exists.

## Backend Hardening
- Historical and financial tables are protected by the database with restrictOnDelete, not by application code.
- Provenance pointers deliberately null out on source deletion so the local record survives.
- Balance correctness is enforced in the payment action, including against sequential payments.
- Snapshot tables (receipts, due_item_discounts, audit_logs.metadata) are never recomputed from live records.
- MySQL CHECK constraints require non-negative StudentDueItem monetary fields and `paid_amount + balance_amount = net_amount`.

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
- Login throttling allows five failed attempts per normalized email and IP address each minute, and a successful login clears the limiter.
- PasswordResetController uses Laravel's password broker with opaque reset-link responses. EmailVerificationController uses Laravel signed verification links; every protected application route also requires verified email.
- Authenticator-app TOTP setup keeps a pending secret in the session until a valid code confirms it. Confirmed secrets use an encrypted User cast, recovery codes use an encrypted array of hashes, and login remains unauthenticated until a valid TOTP or consumed recovery code completes the rate-limited challenge and regenerates the session. Disabling requires the current password plus a valid second factor and clears all TOTP state.
- EnsureUserHasRole is registered as the role middleware alias in bootstrap/app.php.
- Family and student registration routes require auth plus role:Admin. Admin is the only allowed role today.
- Web Form Requests repeat the Admin check, so protection does not depend on route configuration alone.
- No auth package, API auth, or token scheme exists.

## Web Fee and Discount Management
- Admin web pages cover FeeCategory list, create, and edit, plus FeeStructure list, create, and edit.
- FeeStructure editing changes only amount and frequency before a StudentDueItem directly references the structure. Its category, grade, academic year identity and every generated due snapshot remain fixed.
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
- The payment UI supports Admin collection from a Family page and Accountant collection only through `/payments/collect`, which resolves an exact family code to the existing allocation form and stores that selected Family in the session. Accountant create/store requests are denied unless they match the selected Family. The collection form exposes only selected-Family due items and the existing payment-recording POST remains the only collection mutation route.
- Admin-only payment and receipt history lists support family-code, payment-reference, and receipt-number search, validated fixed sorting, and 20-record pagination.
- ReceiptController provides an Admin-only Dompdf download that renders only the stored Receipt snapshots and uses the existing manual receipt number as its filename. Accountants retain receipt list/detail access but cannot download the PDF.

## Web Event Management
- EventController uses CreateEvent and UpdateEvent; it does not confirm or generate dues.
- EventChargeController creates and updates per-grade charges. It changes only amount, and locks every charge for an Event once any EventDueItem exists because charge-level provenance is unavailable.
- EventParticipationController uses SetEventParticipation and preserves the existing opted_in and opted_out statuses.
- GenerateEventDueItems remains the only creator of EventDueItem and StudentDueItem records.
- No event management deletion, payment, receipt, or audit workflow exists.

## Web Student Promotion
- PromotionBatchController creates drafts through CreatePromotionBatch and confirms drafts through ConfirmPromotionBatch.
- The web layer accepts only source/target academic years and source section IDs, the complete existing creation contract.
- Draft items may be edited individually by Admin through UpdatePromotionBatchItem. The action locks the batch and item, requires a valid matching target grade/section for promote or retain, clears targets for exclude or graduate, and audits the change.
- Source enrollments are never modified. Target enrollments are created only on confirmation, within the existing transaction.
- Confirmed and discarded batch items remain immutable. No promotion reversal, export, destructive route, or next-year due generation exists.
- Admins may discard a draft PromotionBatch, which changes only its status and discarded_at. Confirmed and discarded batches cannot be discarded again.

## Web Payment Reminders
- PaymentReminderController lists and previews internal reminder records for Admin and Accountant; only Admin may generate or cancel them.
- Reminder generation can be limited by academic year or family, the exact action-supported filters.
- The detail page renders due_item_ids and message_snapshot without recalculating content or sending a channel message.
- No send, edit, delete, status-transition, queue, scheduler, or Guardian web workflow exists.
- Admins may cancel a pending PaymentReminder. Cancellation changes only its status and does not send a message or modify stored reminder snapshots or financial records.

## Web Admin Usability & Navigation
- AdminDashboardController serves a read-only /admin landing page with COUNT summaries and workflow link cards.
- The root route always redirects: guests to login, authenticated users to the admin dashboard.
- The shared layout exposes one grouped admin navigation row plus consistent success, error, and validation flash display.
- Cross-links reuse existing named routes only; empty states already existed on every index page.
- No backend action, model, authorization rule, or FormRequest changed in this phase.
- Every in-scope Admin index now paginates 20 records with query-string preservation. Identifiable configuration and operational lists support validated text search and fixed controller-owned sort options with direction and `id` tie-breakers; audit logs remain unfiltered and unsorted by decision.

## Web Academic Setup
- Admin-only web pages manage AcademicYear, Term, Grade, Section, and the singleton SchoolSetting.
- Academic setup routes provide index, create, store, show, edit, and update; SchoolSetting provides edit and update.
- Form Requests repeat the Admin role check as defense in depth.
- Academic year details show terms, grade details show sections, and section details show its grade.
- SchoolSetting.active_academic_year_id is enforced by ActiveAcademicYear for new year-scoped operations. Historical reads, reports, and existing edits remain unfiltered; promotion accepts a historical source but requires the active year as target.
- AcademicYear, Term, Grade, and Section have `is_archived` flags with explicit local `active()` scopes, never global scopes.
- Archive/restore is Admin-only, confirmed, and audited. It preserves all history, does not cascade from AcademicYear, and hides records only from new-selection/configuration flows.
- SchoolSetting excludes archived academic years and blocks selection of one; its current active academic year cannot be archived.

## Web Identity and Teaching Configuration
- Superadmin is a fixed role assigned through `users:make-superadmin {email}` to an existing trusted user.
- UserPolicy allows Superadmin to manage every account and Admin to manage only non-Admin/non-Superadmin accounts.
- Users have an is_active lifecycle; inactive accounts cannot log in and are not hard deleted.
- Subjects support Admin-only CRUD; teaching assignments prevent a referenced Subject from deletion.
- Teacher qualifications, teaching assignments, and class-in-charge assignments support Admin-only list/create/view workflows. Teaching assignments require an existing qualification.

## Web Student and Guardian Management
- GuardianController supports Admin-only Guardian create, view, edit, and update under an existing Family.
- Guardian visibility remains explicit: StudentController links and unlinks guardian_student records only after same-Family validation; unlink writes an audit record in the same transaction.
- StudentController supports Admin-only detail and edit, without changing the Student's Family or historical Enrollments.
- EnrollmentPlacementController calls Enrollment::placeIn() for section movement and preserves placement history.
- Discount and StudentFeeSubscription lists support future-only deactivation/end workflows; stored due-item snapshots are never rewritten.

## Demo Data & Local Testing
- DemoDataSeeder builds deterministic local and testing data and returns early in production.
- All generated records come from the existing actions, never duplicated logic.
- Every record uses firstOrCreate or updateOrCreate, so repeated runs are safe.
- DatabaseSeeder stays minimal and never invokes the demo seeder.
- docs/local-demo.md is the single source for local run instructions and credentials.

## Deployment and Security
- docs/deployment-readiness.md owns the production env checklist, deploy, and rollback steps.
- docs/security-review.md owns the current auth, authorization, privacy, and risk record.
- Both seeders return early in production, and this is covered by tests.
- DeploymentReadinessTest asserts every admin route carries auth plus role:Admin.

## Documentation Set
- docs/system-overview.md explains what the system is and its module status.
- docs/data-flow.md is the reference for how data moves and which safety rules apply.
- docs/user-workflows.md is the reference for how staff actually operate the app.
- docs/ui-ux-roadmap.md records interface gaps and the redesign sequence.
- docs/production-gap-register.md is the single list of what blocks production.

## Admin Interface
- public/css/admin.css is the entire admin design system. It is plain CSS with no build step.
- The Vite and Tailwind pipeline is present but unused, because public/build is gitignored
  and committing a build artifact would be worse than a static stylesheet.
- resources/views/components holds the shared UI components. Views stay free of design duplication.
- Navigation is a grouped sidebar rendered once in the layout. Views never repeat it.
- The commercial shell is dark sidebar plus light workspace, with a CSS-only mobile drawer.
- public/js/admin-ui.js provides toasts, confirmations, and submit loading. It is local, has no
  build step, and degrades gracefully: if it fails to load, forms still submit normally.
- Family and Student detail pages use local `window.print()` controls and a `print-record` boundary.
  Scoped print styles remove the admin shell and mutation controls without changing routes, queries, or fields.
- Irreversible-looking actions are marked with data-confirm. Write forms use data-loading.
- docs/ui-ux-defect-audit.md is the record of audited screens and their remaining limits.
- docs/backend-frontend-feature-parity.md, docs/crud-coverage-matrix.md, docs/frontend-missing-feature-backlog.md,
  and docs/destructive-action-policy-draft.md are the planning record for what to build next.
- Known parity rule: money and history are never deleted; configuration is archived.
