# SRS Compliance Matrix — Stage 1

**Authority:** `docs/requirements/SRS_Module_Wise_Stage1.pdf`, v2.0, 2026-08-06. PDF extraction was verified page-by-page, including its module and requirement tables.

**Status meanings:** COMPLETE = implemented and UI-reachable; PARTIALLY COMPLETE = safe portion exists but a required element is absent; MISSING = no evidence; DEFECTIVE = implemented behavior contradicts the SRS; INTENTIONALLY RESTRICTED = narrower role/access design; REQUIRES SRS CLARIFICATION = blocked by an open SRS decision.

| ID | Requirement / role | Backend, UI, authorization, tests | Status | Evidence and gap |
|---|---|---|---|---|
| FR-M1.1 | Admin creates academic years | CRUD controller/requests, Admin routes, `WebAcademicSetupTest` | COMPLETE | `AcademicYearController`, `academic-years/*` |
| FR-M1.2 | Admin defines terms | CRUD controller/requests, Admin routes/tests | COMPLETE | `TermController`, `terms/*` |
| FR-M1.3 | Exactly one active year; new work defaults to it | singleton setting and `ActiveAcademicYear`; tests | COMPLETE | `SchoolSetting`, `ActiveAcademicYearEnforcementTest` |
| FR-M1.4 | Warn before switching with incomplete promotion | active-year settings show draft batches tied to the outgoing active year and require acknowledgement before a switch | COMPLETE | `SchoolSettingController`, `school-settings/edit.blade.php`, `WebAcademicSetupTest` |
| FR-M2.1 | Admin grades with sequence | CRUD/archive UI/tests | COMPLETE | `GradeController`, `grades/*` |
| FR-M2.2 | Admin sections and capacity | CRUD/archive UI/tests | COMPLETE | `SectionController`, `sections/*` |
| FR-M2.3 | Assign class-in-charge | year-scoped assignment UI/tests | COMPLETE | `SectionYearAssignmentController` |
| FR-M2.4 | Grade/sections persist across years | shared Grade/Section plus yearly Enrollment | COMPLETE | migrations, `Enrollment` |
| FR-M3.1 | Admin staff accounts and roles | User UI; fixed roles and policy | COMPLETE | `UserController`, `UserPolicy` |
| FR-M3.2 | Teacher qualifications | qualification list/create/show UI | COMPLETE | `TeacherQualificationController` |
| FR-M3.3 | Qualified Teacher + section + subject assignment | checked by action and UI; Section supplies Grade context | COMPLETE | `CreateTeacherAssignment`, `UpdateTeacherAssignment`, tests; SRS does not require a separate Grade–Subject applicability relation |
| FR-M3.4 | Role-restricted family/student/fees | middleware, policies, portal query scoping | COMPLETE | `routes/web.php`, portal tests |
| FR-M4.1 | Admin fee categories | create/edit/list UI | COMPLETE | `FeeCategoryController` |
| FR-M4.2 | Grade/year/frequency fee structures | create/edit/list UI | COMPLETE | `FeeStructureController` |
| FR-M4.3 | Year-versioned fees preserve generated dues | snapshots/lock tests | COMPLETE | `StudentDueItem`, fee tests |
| FR-M5.1 | Family creation and Guardian name/phone/NIC duplicate search | Admin Family UI, Guardian-aware search | COMPLETE | `FamilyController`, `WebRegistrationWorkflowTest` |
| FR-M5.2 | Multiple Guardians/Students per Family | relations and UI | COMPLETE | `Family`, Family pages/tests |
| FR-M5.3 | Explicit per-student Guardian subset | link/unlink UI and privacy actions | COMPLETE | `guardian_student`, tests |
| FR-M5.4 | Per-student override of shared address/contact | nullable Student address/contact overrides with Family fallback; Admin registration/profile UI and validation | COMPLETE | `students.address_override`, `students.contact_no_override`, Student forms/controller, migration batch 4 |
| FR-M5.5 | Combined billing toggle/default/consolidation | toggle/default; collection screen shows stored Family total and each linked Student's stored outstanding balance before manual allocation | COMPLETE | `PaymentCollectionController`, `payments/create.blade.php`, `WebPaymentCollectionTest` |
| FR-M5.6 | Preserve Family/history | no Family delete/deactivate route; validated Family search/sorting/pagination preserve query state; restrictive history FKs | COMPLETE | `FamilyController`, routes/migrations, `WebIndexPaginationTest` |
| FR-M5.7 | Guardian credentials only see linked students | User→Guardian link and strict portal scope | COMPLETE | `GuardianPortalController`, tests |
| FR-M6.1 | Staff register first/additional child | Admin creates Family then registers child | PARTIALLY COMPLETE | function works but Accountant is restricted and first-child flow is two screens |
| FR-M6.2 | Existing-family prefill | Family context/guardians shown; optional Student address/contact overrides default to Family data when blank | COMPLETE | `students/create.blade.php`, `RegisterStudent`, `WebRegistrationWorkflowTest` |
| FR-M6.3 | Registration mandatory dues auto-generated | no mandatory-registration category/rule | MISSING | `RegisterStudent` explicitly creates no dues |
| FR-M6.4 | Pending until mandatory dues paid | new registrations always start pending and pending Students cannot be manually activated through the profile UI | PARTIALLY COMPLETE | `RegisterStudent`, `StudentRegistrationController`, `UpdateStudentRequest`; mandatory fee classification and settlement-driven activation remain unimplemented |
| FR-M6.5 | Active students attendance/class roster | active filter in promotion; attendance absent | OUT OF SCOPE | SRS Stage 1 has no attendance module/UI |
| FR-M6.6 | Edit profile/transfer with history | profile editing, private validated photo upload, and `Enrollment::placeIn()` UI | COMPLETE | `StudentController`, student forms/tests |
| FR-M6.7 | Inactive/withdrawn/graduated lifecycle | status edit and promotion graduation | COMPLETE | `UpdateStudentRequest`, promotion action |
| FR-M7.1 | Staff lookup by Family or Student | Admin Family/Student lists; Accountant exact Family code | PARTIALLY COMPLETE | no Accountant individual-Student lookup |
| FR-M7.2 | Combined total and child breakdown | collection screen displays the stored Family outstanding total, every linked Student's stored balance, and itemized outstanding dues | COMPLETE | `PaymentCollectionController`, `payments/create.blade.php`, `WebPaymentCollectionTest` |
| FR-M7.3 | Cash/bank/online payment | manual method field and family allocations | PARTIALLY COMPLETE | no online gateway; Accountant safely limited |
| FR-M7.4 | Configurable allocation strategy/manual override | manual allocation is authoritative | REQUIRES SRS CLARIFICATION | default strategy is open item; no strategy setting |
| FR-M7.5 | Itemized receipt | immutable allocation snapshot/receipt UI/PDF | COMPLETE | `Receipt`, receipt views/tests |
| FR-M7.6 | Partial balances per due item | transactional allocations and snapshots | COMPLETE | `Payment::recordManual`, tests |
| FR-M8.1 | Event with grade applicability | Event + per-grade charges UI | COMPLETE | Event/charge controllers |
| FR-M8.2 | Per-grade charges | create/edit before generation | COMPLETE | `EventChargeController` |
| FR-M8.3 | Opt-in or mandatory event | `is_mandatory`, participation UI | COMPLETE | Event model/forms |
| FR-M8.4 | Confirmation/mandatory due generation | manual Admin generation creates itemized dues | PARTIALLY COMPLETE | no separate confirmation/automatic trigger |
| FR-M8.5 | Participation/payment status by student/family | Admin-only read-only event status report lists applicable Student, Family, Grade, participation, charge, generated due, stored paid/balance, and status | COMPLETE | `EventController::status`, `events/status.blade.php` |
| FR-M9.1 | Student fee-category discount | create/deactivate UI | COMPLETE | `StudentDiscountController` |
| FR-M9.2 | Configurable sibling suggestion | no rule/setting | REQUIRES SRS CLARIFICATION | named open item |
| FR-M9.3 | Discount at generation | snapshots at recurring/event generation | COMPLETE | generation actions/tests |
| FR-M10.1 | Scheduled recurring generation | manual UI only | MISSING | no recurring due scheduler |
| FR-M10.2 | Discounts/opt-in respected | generation action applies both | COMPLETE | `GenerateRecurringDueItems` |
| FR-M10.3 | Feeds combined billing | generated StudentDueItems feed the same stored-balance Family collection view and remain itemized by Student | COMPLETE | `GenerateRecurringDueItems`, `PaymentCollectionController`, `WebPaymentCollectionTest` |
| FR-M11.1 | Due/collected/outstanding filters | report/dashboard totals and filters; Admin/Accountant | COMPLETE | `BuildDuesDashboardReport` |
| FR-M11.2 | Family outstanding view | family balances report | COMPLETE | dashboard family balances |
| FR-M12.1 | Stored, reprintable receipts | receipt pages/PDF with caller-entered, database-unique immutable receipt number | COMPLETE | `receipts.receipt_no` unique constraint, `ReceiptController`, `PaymentReceiptTest` |
| FR-M12.2 | History per student and family | Family payment history plus Admin-only allocation-scoped Student history with original receipt and correction status | COMPLETE | `StudentController::paymentHistory`, `students/payment-history.blade.php`, `StudentPaymentHistoryTest` |
| FR-M12.3 | Combined receipt child breakdown | allocation snapshots/PDF | COMPLETE | `Receipt` snapshots |
| FR-M13.1 | Automated upcoming/overdue reminders | generation, scheduler, queued local-only delivery | PARTIALLY COMPLETE | external transport intentionally disabled |
| FR-M13.2 | Combined consolidated reminder | deterministic Family/Guardian record | COMPLETE | `GeneratePaymentReminders` |
| FR-M14.1 | Audit registration/family/discount/payment | append-only audit UI/export | COMPLETE | `RecordAuditLog`, `AuditLogController` |
| FR-M14.2 | Audit allocation strategy | allocation rows logged; no strategy choice exists | REQUIRES SRS CLARIFICATION | depends on FR-M7.4 |
| FR-M15.1 | Select source year/sections | promotion create UI | COMPLETE | `PromotionBatchController` |
| FR-M15.2 | Active students/default next target | action derives target by sequence | COMPLETE | `CreatePromotionBatch` |
| FR-M15.3 | edit promote/retain/exclude/graduate | draft-item edit UI/action | COMPLETE | `UpdatePromotionBatchItem` |
| FR-M15.4 | draft/edit/discard/confirm | all Admin UI paths | COMPLETE | promotion views/actions |
| FR-M15.5 | atomic new enrollments | transaction and tests | COMPLETE | `ConfirmPromotionBatch` |
| FR-M15.6 | graduate status/history | graduation action preserves history | COMPLETE | `ConfirmPromotionBatch` |
| FR-M15.7 | no automatic next-year dues | explicitly prevented/tested | COMPLETE | promotion tests |
| FR-M15.8 | confirmed batch CSV/PDF export | Admin-only confirmed-batch CSV and local-Dompdf PDF exports with source/target/outcome details; CSV formula values are neutralized | COMPLETE | `PromotionBatchController`, `promotion-batches/pdf.blade.php` |
| FR-M15.9 | enrollment history on profile | Admin/Guardian/Teacher profile views | COMPLETE | Student/portal views |
| FR-M15.10 | re-evaluate class-in-charge target | year-scoped assignment exists | PARTIALLY COMPLETE | promotion does not surface/review target-year assignments |

## SRS non-functional/open-item assessment

- Promotion confirmation atomicity and Guardian privacy are implemented and tested.
- Promotion reversal safety window is **unresolved** and no reversal exists.
- Family creation/updates and promotion confirmation are audited; Family merge is not implemented.
- Open SRS items remain: default allocation strategy, sibling discount rule, promotion reversal safety window, duplicate-family block versus warning.
