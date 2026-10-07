# Backend and Frontend Feature Parity

Phase 10C-5 audit, updated by Phase 10D-1A. This document records what exists on
both sides and what remains missing.

Method: read `app/Models`, `app/Actions`, `app/Http/Controllers/Web`,
`app/Http/Requests/Web`, `routes/web.php`, `resources/views`, and `tests/Feature`,
then compared the surfaces. 35 models, 24 actions, 17 web controllers, 55 named web
routes, 44 Blade views, 33 test files.

Legend for Frontend Status: **Complete**, **Partial**, **Missing**, **Intentionally absent**.

---

## Foundation

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| School Setting | `SchoolSetting` / `school_settings` | none | `SchoolSettingController` | edit, update | edit | `AcademicFoundationTest`, `WebAcademicSetupTest` | **Partial** | Active year is still inert outside this setting | The column remains read by nothing else | Later decision |
| Academic Year | `AcademicYear` / `academic_years` | `SetAcademicRecordArchived` | `AcademicYearController` | index, create, store, show, edit, update, archive, restore | index, create, show, edit, archive, restore | `AcademicFoundationTest`, `WebAcademicSetupTest` | **Complete** | None | Active SchoolSetting year is protected; history remains visible | Complete |
| Term | `Term` / `terms` | `SetAcademicRecordArchived` | `TermController` | index, create, store, show, edit, update, archive, restore | index, create, show, edit, archive, restore | `AcademicFoundationTest`, `WebAcademicSetupTest` | **Complete** | None | History remains visible | Complete |
| Grade | `Grade` / `grades` | `SetAcademicRecordArchived` | `GradeController` | index, create, store, show, edit, update, archive, restore | index, create, show, edit, archive, restore | `AcademicFoundationTest`, `WebAcademicSetupTest` | **Complete** | None | `sequence_order` remains historical; history remains visible | Complete |
| Section | `Section` / `sections` | `SetAcademicRecordArchived` | `SectionController` | index, create, store, show, edit, update, archive, restore | index, create, show, edit, archive, restore | `AcademicFoundationTest`, `WebAcademicSetupTest` | **Complete** | None | History remains visible | Complete |
| User | `User` / `users` | none | none | none | none | `IdentityAndTeacherFoundationTest` | **Missing** | No user list, create, edit, or password reset | Staff cannot be onboarded without a console | 10D-2 |
| Role | `Role` / `roles` | none | none | none | none | `IdentityAndTeacherFoundationTest` | **Missing** | No role list or management | Only three fixed roles exist. Granting Admin is a console task | 10D-2 |
| Teacher | `User` with teacher role | none | none | none | none | `IdentityAndTeacherFoundationTest` | **Missing** | No teacher list or profile | Same table as User; needs a role filter, not a new table | 10D-2 |
| Subject | `Subject` / `subjects` | none | none | none | none | `IdentityAndTeacherFoundationTest` | **Missing** | No list or create | Needed before teacher assignments are meaningful | 10D-2 |
| Teacher Assignment | `TeacherAssignment` / `teacher_assignments` | none | none | none | none | `IdentityAndTeacherFoundationTest` | **Missing** | No list, create, or view | Blocks any future teacher section scoping | 10D-2 |
| Section-Year Assignment | `SectionYearAssignment` / `section_year_assignments` | none | none | none | none | `IdentityAndTeacherFoundationTest` | **Missing** | No list, create, or view | Same | 10D-2 |

**Conclusion:** academic setup is now available to Admins through the web app. User,
role, teacher, subject, and assignment setup remain backend-only.

---

## Registration

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Families | `Family` | `CreateFamily`, `UpdateFamily` | `FamilyController` | index, create, store, show, edit, update | index, create, show, edit | `WorkflowActionsTest`, `WebRegistrationWorkflowTest` | **Complete** | No archive, no delete | Delete is restricted by children. Archive needs a decision | 10D-4 |
| Guardians | `Guardian` | created inside `CreateFamily` only | `FamilyController` (indirect) | none directly | family create and show | `FamilyGuardianTest` | **Partial** | No guardian list, view, edit, or **add a second guardian** | A family can only ever have the one guardian entered at creation | 10D-3 |
| Students | `Student` | `RegisterStudent` | `StudentRegistrationController` | create, store | create, family show | `WorkflowActionsTest`, `WebRegistrationWorkflowTest` | **Partial** | No student list, no student detail, no edit | Students are only reachable through a family. Status and photo path cannot be edited in the UI | 10D-3 |
| Guardian-Student links | `guardian_student` | `LinkGuardianToStudent` | no standalone controller | none | set during registration | `WorkflowActionsTest` | **Partial** | No page to review or revoke a link | Revoking a link is the main privacy lever and is console-only | 10D-3 |
| Enrollments | `Enrollment` | created inside `RegisterStudent` | `StudentRegistrationController` (indirect) | none | student create | `StudentEnrollmentTest` | **Partial** | No enrollment list, view, or edit | A student cannot be moved between sections after registration | 10D-3 |
| Enrollment placement history | `EnrollmentPlacement` | written by `Enrollment::placeIn()` | none | none | none | `StudentEnrollmentTest` | **Intentionally absent** | None today | No screen moves a student, so there is nothing to show. Correct to omit | Later |

---

## Fees

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Fee Categories | `FeeCategory` | `CreateFeeCategory`, `UpdateFeeCategory` | `FeeCategoryController` | index, create, store, edit, update | index, create, edit | `FeeDueDiscountTest`, `WebFeeDiscountManagementTest` | **Complete** | No archive, no delete | Delete restricted once structures exist | 10D-4 |
| Fee Structures | `FeeStructure` | `CreateFeeStructure` | `FeeStructureController` | index, create, store | index, create | `FeeDueDiscountTest`, `WebFeeDiscountManagementTest` | **Partial** | No view, **no edit**, no delete | Edit is deliberately create-only. An edit needs a decision on whether it may re-price already-generated dues | 10D-5 |
| Student Fee Subscriptions | `StudentFeeSubscription` | `CreateStudentFeeSubscription` | `StudentFeeSubscriptionController` | create, store | create | `FeeDueDiscountTest`, `WebFeeDiscountManagementTest` | **Partial** | No list, no view, no edit, no end | A subscription cannot be switched off once granted. Real operational gap | 10D-3 |
| Discounts | `Discount` | `ApplyStudentDiscount` | `StudentDiscountController` | create, store | create | `FeeDueDiscountTest`, `WebFeeDiscountManagementTest` | **Partial** | No list, no view, no edit, no end | An applied discount cannot be reviewed or stopped. Real operational gap | 10D-3 |
| Student Due Items | `StudentDueItem` | `GenerateRecurringDueItems`, `GenerateEventDueItems` | `DuesDashboardController` (read only) | `dues-dashboard.index` | dashboard | `FeeDueDiscountTest`, `WebDueDashboardTest` | **Partial** | No due-item list or detail page | Items are only visible inside the dashboard and the payment form | 10D-3 |
| Due Item Discounts | `DueItemDiscount` | written during generation | none | none | shown inside the receipt | `FeeDueDiscountTest` | **Intentionally absent** | None needed | Immutable snapshot. Surfacing it separately would invite edits | Not needed |

---

## Payments

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Payments | `Payment` | `Payment::recordManual()` | `PaymentCollectionController` | create, store, **show only** | create, show | `PaymentReceiptTest`, `WebPaymentCollectionTest` | **Partial** | **No payment list or index.** The only way to find a past payment is to know the ID | Highest-value finance gap. Staff cannot answer "did this family pay?" | 10D-6 |
| Payment Allocations | `PaymentAllocation` | created inside `recordManual()` | shown on payment show | none | payment show | `PaymentReceiptTest` | **Intentionally absent** | None | Allocations are immutable. A list would be read-only noise | Not needed |
| Receipts | `Receipt` | created inside `recordManual()` | `ReceiptController` | show only | show | `PaymentReceiptTest`, `WebCommercialWorkflowUiTest` | **Partial** | No receipt list, no PDF export | Print stylesheet exists, so a browser print is a real PDF path today | 10D-6 |
| Payment edit / refund | none | none | none | none | none | none | **Intentionally absent** | none | **Needs business decision.** There is no correction path for a mistaken payment at all. This is a genuine operational blocker, not a missing feature | 10D-7 |

---

## Events

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Events | `Event` | `CreateEvent`, `UpdateEvent` | `EventController` | index, create, store, show, edit, update | index, create, show, edit | `EventDueGenerationTest`, `WebEventManagementTest` | **Complete** | No delete | Delete unsafe once dues exist. Events have a `discarded`-like need but no such field | 10D-4 |
| Event Charges | `EventCharge` | `CreateEventCharge` | `EventChargeController` | create, store | create, event show | `WebEventManagementTest` | **Partial** | No edit, no delete | Create-only by design. Editing after generation would silently re-price dues | 10D-5 |
| Event Participation | `EventParticipation` | `SetEventParticipation` | `EventParticipationController` | create, store | create, event show | `EventDueGenerationTest`, `WebEventManagementTest` | **Partial** | No per-student list or bulk clear | Only students in the charge grades are listed, so a wider opt-in list is impossible | 10D-3 |
| Event Due Items | `EventDueItem` | created by `GenerateEventDueItems` | none | none | event show count | `EventDueGenerationTest` | **Intentionally absent** | None | Immutable link table | Not needed |

---

## Promotion

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Promotion Batches | `PromotionBatch` | `CreatePromotionBatch`, `ConfirmPromotionBatch` | `PromotionBatchController` | index, create, store, show, confirm | index, create, show | `StudentPromotionTest`, `WebStudentPromotionTest` | **Complete** | No discard, despite a `discarded` status existing | A draft can only be abandoned by creating another batch. Real gap | 10D-4 |
| Promotion Batch Sections | `PromotionBatchSection` | created by `CreatePromotionBatch` | none | none | show list | `StudentPromotionTest` | **Intentionally absent** | None | Read-only projection of the selection | Not needed |
| Promotion Batch Items | `PromotionBatchItem` | written by `CreatePromotionBatch` | none | none | show table, read only | `StudentPromotionTest` | **Partial** | **No per-item edit.** Target grade, section, and action are all frozen after creation | Blocks every exception case: retain, exclude, graduate, or retarget a single student. Needs a backend decision first | 10D-8 |
| Promotion reversal | none | none | none | none | none | none | **Intentionally absent** | none | Safety window is an unresolved business decision | Blocked on decision |

---

## Reminders

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Payment Reminders | `PaymentReminder` | `GeneratePaymentReminders` | `PaymentReminderController` | index, create, store, show | index, create, show | `PaymentReminderGenerationTest`, `WebPaymentReminderTest` | **Partial** | No cancel, despite a `cancelled` status existing; no send | A stale reminder cannot be withdrawn. No delivery provider exists, so send is correctly absent | 10D-4 |

---

## Audit

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Audit Logs | `AuditLog` | `RecordAuditLog` (write only) | `AuditLogController` | index, show, CSV export, PDF export | index, show, local PDF template | `AuditLogTest`, `WebAuditLogTest`, `DeploymentReadinessTest` | **Complete** | None | Admin-only stored-row filters and exports. Audit logs are append-only and retained forever; no archive, purge, mutation, or polymorphic-source lookup exists | Complete |

---

## Reports and dashboards

| Module | Model / Table | Backend actions | Web controller | Web routes | Views | Tests | Frontend | Missing UI | Risk / notes | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Dues Dashboard | `StudentDueItem` | `BuildDuesDashboardReport` | `DuesDashboardController` | index | index | `DuesDashboardReportTest`, `WebDueDashboardTest` | **Complete** | No export, no date comparison | Read-only and correct. Export is a nice-to-have | 10D-9 |
| Admin Dashboard | counts only | none | `AdminDashboardController` | index | dashboard | `WebCommercialUiTest` | **Partial** | No attention queue, no recent activity | Shows counts only. An audit feed would fill this once the audit UI exists | 10D-1 |

---

## Security and authentication

| Module | Backend | Web controller | Routes | Views | Tests | Frontend | Missing UI | Risk / notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Login | `Auth::attempt` | `LoginController` | login, login.store | auth/login | `WebAuthProtectionTest` | **Complete** | No password reset, no 2FA, no rate limit | Reset is the blocking gap |
| Logout | session invalidate | `LoginController` | logout | layout | `WebAuthProtectionTest` | **Complete** | none | none |
| Role middleware | `EnsureUserHasRole` | n/a | alias `role` | n/a | `DeploymentReadinessTest` | **Complete** | Accountant and Teacher have no pages at all | Role model exists but grants nothing today |
| Route protection | `auth` + `role:Admin` group | n/a | all admin routes | n/a | `DeploymentReadinessTest` | **Complete** | none | Every non-public route is covered |
| Guardian privacy | 3 privacy actions | none | none | none | `AuthorizationPrivacyTest` | **Intentionally absent** | No Guardian login | Correct. Guardian access is query-level, not route-level |
| Policies | 6 policies | none wired | n/a | n/a | `AuthorizationPrivacyTest` | **Intentionally absent** | none yet | Written but unused until per-role pages exist |

---

## Demo and deployment

| Module | Backend | Frontend | Missing UI | Risk / notes |
| --- | --- | --- | --- | --- |
| DemoDataSeeder | seeder | none needed | none | Guarded against production and tested |
| `DatabaseSeeder` | seeder | none needed | none | Roles plus one Admin only |
| `docs/local-demo.md` | docs | none needed | none | Current |
| `docs/deployment-readiness.md` | docs | none needed | none | Current |
| `docs/security-review.md` | docs | none needed | none | Lists the open risks |

---

## Cross-cutting gaps

These are not module gaps; they affect every module.

| Gap | Impact | Suggested phase |
| --- | --- | --- |
| No search, sort, or pagination anywhere | Every list degrades badly at real school size | 10D-6 |
| No student or payment list page | Staff cannot navigate to a record they did not just create | 10D-3, 10D-6 |
| No standalone Guardian management | A family can never gain a second guardian after creation | 10D-3 |
| No status-change controls for discounts or subscriptions | Staff can apply a discount but never withdraw it | 10D-3 |
| `SchoolSetting.active_academic_year_id` is inert | The schema implies an "active year" that nothing honours | 10D-1 |
