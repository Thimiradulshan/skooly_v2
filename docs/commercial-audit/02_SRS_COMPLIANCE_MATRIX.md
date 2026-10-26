# Stage 1 SRS Compliance Matrix

Status reflects current source, tests, and routes. `D` = defective; `P` = partial; `BD` = needs an approved business decision. Evidence paths are representative, not a claim of browser verification.

| Requirement | Status | Evidence / gap |
| --- | --- | --- |
| M1.1 Create academic year | Complete | `AcademicYearController::store`; `WebAcademicSetupTest` |
| M1.2 Define terms | Complete | `TermController`; term routes/views |
| M1.3 Exactly one active year/defaults | Compliant | `SchoolSetting`; explicit first-setting bootstrap and singleton ID constraint; CA-14 resolved 2026-10-25 |
| M1.4 Admin switch warning | Complete | `SchoolSettingController.php:25-42` warns on draft promotion batches |
| M2.1 Grade create/edit/order | Complete | `GradeController`, unique sequence migration |
| M2.2 Section/capacity | Complete | `SectionController`, section form |
| M2.3 Assign class-in-charge | Partial | year assignment exists; no correction/reassignment lifecycle is evidenced |
| M2.4 Persist grade/sections | Complete | shared Grade/Section tables; Enrollment year scope |
| M3.1 Staff accounts / roles | Complete | `UserController`, `UserPolicy` |
| M3.2 Teacher qualifications | Complete | qualification controller/routes |
| M3.3 Teacher section+subject assignment | Complete | `TeacherAssignmentController`, qualification validation |
| M3.4 Role-restricted data | Complete | route middleware/policies; teacher financial UI absent |
| M4.1 Fee categories | Complete | `FeeCategoryController` |
| M4.2 Year/grade fee structures | Complete | `FeeStructureController`; uniqueness migration |
| M4.3 Versioned structures | Complete | `fee_structures` identity plus due snapshots |
| M5.1 Family create/search duplicate aid | Complete | `FamilyController.php:22-35` searches guardian name/phone/NIC |
| M5.2 Multiple guardians/students | Complete | relationships and UI |
| M5.3 Explicit guardian subset | Complete | guardian-student routes/actions |
| M5.4 Shared/overridable contacts | Complete | `address_override`, `contact_no_override` migration |
| M5.5 Combined billing switch | Complete | family field and payment aggregation screens |
| M5.6 Preserve family history | Complete | no family delete route; restrictive FK |
| M5.7 Guardian credentials/limited access | Complete | `users.guardian_id`, guardian portal; explicit checks |
| M6.1 Register first/additional child | Complete | family registration routes |
| M6.2 Prefill family data | Partial | family context/guardians available; full prefill workflow not browser-verified |
| M6.3 Registration dues generated | Missing | no registration-fee classification/action; documented open decision |
| M6.4 Pending until mandatory dues paid | Missing | no registration-due identification/status transition |
| M6.5 Active-only roster | Defective | teacher query lacks student status: `TeacherPortalController.php:79-90`; CA-03 |
| M6.6 Profile and placement history | Complete | `StudentController`, `Enrollment::placeIn` flow |
| M6.7 Mark lifecycle statuses | Partial | status edit exists; workflow semantics/withdrawal controls are limited |
| M7.1 Search Family or Student | Partial | Family search and student directory exist; accountant uses exact family code only |
| M7.2 Combined total / child breakdown | Complete | due/payment structures and family UI |
| M7.3 Record cash/bank/online payment | Complete | manual method field and `Payment::recordManual` |
| M7.4 Configurable allocation strategy | BD | manual-only implemented; strategy choice explicitly unresolved |
| M7.5 Itemized receipt | Defective | receipt snapshot/view omit child identity; CA-02 |
| M7.6 Individual partial balances | Complete | locked allocations and due-item balances |
| M8.1 Event / grades | Complete | Event, charges, forms |
| M8.2 Per-grade charges | Complete | `EventChargeController` |
| M8.3 Opt-in / mandatory | Complete | Event fields/participation |
| M8.4 Confirm/generate dues | Partial | separate manual generation exists; confirmation semantics are not a discrete event transition |
| M8.5 Participation/payment status | Complete | event status route/view and due records |
| M9.1 Student/category discount | Complete | discount action/UI |
| M9.2 Sibling auto-suggestion | BD | rule/percentage unresolved |
| M9.3 Discount at generation | Complete | `GenerateRecurringDueItems.php:68-89` |
| M10.1 Scheduled recurring generation | Partial | manual generator; no recurring billing scheduler |
| M10.2 Subscription dates respected | Defective | existence check ignores `starts_on`/`ends_on`: `GenerateRecurringDueItems.php:106-114`; CA-07 |
| M10.3 Generated dues feed family billing | Complete | all dues are student/family-linked |
| M11.1 Dashboard filters/totals | Complete | report action/dashboard |
| M11.2 Family outstanding view | Complete | report family balances |
| M12.1 Stored/reprintable receipt | Defective | snapshot/PDF exists but lacks required child identity (CA-02) |
| M12.2 Student/family history | Complete | payment/receipt and student allocation history |
| M12.3 Combined payment child itemization | Defective | CA-02 |
| M13.1 Automated reminders | Partial | scheduler/outbox/job exists, but external delivery disabled and job is sync if queue misconfigured |
| M13.2 Consolidated family reminder | Defective | job rejects `due_item_ids` count other than one (`DeliverPaymentReminderEmail.php:52-55`) |
| M14.1 Audit key actions | Defective | family web create does not pass actor (CA-05); coverage otherwise broad |
| M14.2 Allocation strategy change audit | BD | no configurable strategy is implemented |
| M15.1 Select source/year/sections | Complete | batch create form/action |
| M15.2 Active students/default target | Complete | create action and batch item defaults |
| M15.3 Review/promote/retain/exclude/graduate | Defective | retain can target a different grade (CA-06) |
| M15.4 Draft/edit/discard | Complete | item update and discard routes |
| M15.5 New target enrollment | Complete | atomic `ConfirmPromotionBatch` |
| M15.6 Graduate/history | Complete | status update with historical enrollments |
| M15.7 No automatic next-year fees | Complete | confirm action does not generate dues |
| M15.8 CSV/PDF export | Complete | `PromotionBatchController.php:103-140` |
| M15.9 Enrollment history | Complete | student/guardian views load enrollments/placements |
| M15.10 Re-evaluate class-in-charge | Partial | year-specific assignments exist; promotion has no explicit reassignment review |

**Counts:** Complete 43; Partial 9; Missing 2; Defective 8; Requires business decision 3; total 65.
