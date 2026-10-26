# Backend ↔ Frontend Traceability

## Evidence method

`docs/commercial-audit/evidence/route-ledger.csv` statically maps each named route to middleware, literal Blade references, test references, and a BrowserEndToEnd value. All ledger entries remain **NOT VERIFIED** for browser E2E because no browser tool/session was available.

## Authorized workflow trace samples

| Operation | Role | UI/control → request | Route/controller → action/data | Result |
| --- | --- | --- | --- | --- |
| Create Family | Admin | `families/create.blade.php:10` → POST | `families.store` → `FamilyController::store` → `CreateFamily` | UI exists; audit actor missing (CA-05) |
| Record payment | Admin/Accountant | `payments/create.blade.php:39-45` → POST | `families.payments.store` → `Payment::recordManual` | server validation/transaction/receipt; receipt gap CA-02 |
| Generate recurring dues | Admin | confirmation/loading form → POST | `due-generation.recurring.store` → `GenerateRecurringDueItems` | UI exists; subscription date defect CA-07 |
| Archive term | Admin | `terms/show.blade.php:10` → POST | `terms.archive` → archive action | timing characterization redirects/flash; browser not verified |
| Confirm promotion | Admin | confirmation form → POST | `promotion-batches.confirm` → `ConfirmPromotionBatch` | atomic backend; raw SQL error disclosure CA-21 |
| Queue reminder | Admin | `payment-reminders/show.blade.php:28` → POST | `payment-reminders.queue` → queue attempt + job | UI exists; queue configuration can synchronously block CA-04 |
| Guardian dues | Guardian-linked User | sidebar → GET | `GuardianPortalController::dues` → constrained due query | explicit-link filtering is present |
| Teacher student directory | Teacher | sidebar → GET | `TeacherPortalController::students` → assignment-scoped enrollments | pending students incorrectly included CA-03 |

## Known gaps

1. **Receipt data flow:** the UI is discoverable and the POST/redirect path works; `Payment.php:122-130` records no `student_id`/student name. Both HTML and PDF have no child column. CA-02.
2. **Notification behavior:** UI labels “queue” but `Bus::dispatch` will execute inline if a deployment sets `QUEUE_CONNECTION=sync`; no deployment invariant rejects sync. CA-04.
3. **No real-browser evidence:** DOM controls, keyboard, request timing under live HTTP, and JavaScript recovery after real navigation are not verified. See `14_BROWSER_ACCEPTANCE_CHECKLIST.md`.
4. **Write-form consistency:** 76 forms were found, 59 with `data-loading`; this includes GET and auth forms, so it is a coverage review item rather than proof that 17 mutations are defective. CA-22 tracks the dependency vulnerability, not this count.

## Route security trace

* Protected application routes require `auth`, `verified`, and role middleware (`routes/web.php:83-308`).
* Role middleware checks only authentication and role membership (`EnsureUserHasRole.php:16-26`); it does **not** check `is_active`, enabling CA-01 for existing sessions.
* Controllers/policies provide resource-specific secondary checks for financial and Guardian pathways. The static ledger links every route to tests, but coverage references do not establish actual browser end-to-end behavior.
