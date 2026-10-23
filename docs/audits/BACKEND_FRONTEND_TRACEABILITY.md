# Backend-to-Frontend Traceability

Audit method: route/controller/request/action/view/test inspection. “Working” means an authorized, server-rendered UI path exists and is exercised by feature tests; it is not a claim of browser automation.

| Module / role | Operation | Backend and authorization | UI entry point | Working | Finding / fix |
|---|---|---|---|---|---|
| Admin | Academic CRUD/archive | Admin routes, requests, archive action | Sidebar → Academic Setup | Yes | Tests: `WebAcademicSetupTest` |
| Admin | Grade/section/class teacher | CRUD + year assignment actions | Sidebar → Grades/Sections/Class In Charge | Yes | Historic records retained |
| Admin | Users/roles | User policy/controller | Sidebar → Users | Yes | Fixed roles only |
| Admin | Teacher qualification/assignment correction | qualification and assignment actions | Sidebar → Teacher Qualifications/Assignments | Yes | correction is audited |
| Admin | Family create/search/edit | Family action/controller | Sidebar → Families | Yes | Guardian identity search fixed in this audit |
| Admin | Guardian create/edit/link students | Guardian actions/controller | Family/Student detail pages | Yes | explicit links only |
| Admin | Guardian account linking | linking action | Guardian detail page | Yes | verified matching-account selector fixed in this audit |
| Admin | Student directory/edit/placement/payment history | directory/request/Enrollment transaction; allocation-scoped history query | Sidebar → Students → Student profile → Payment history | Yes | Student history is read-only and excludes sibling allocations |
| Admin | Fee configuration/discount/subscription | actions/requests | Fee pages and Student detail | Yes | no destructive deletion |
| Admin | Recurring/event due generation | generation actions | Dues/Events pages | Yes | manual only |
| Admin/Accountant | Dues dashboard | read-only report | Finance navigation | Yes | Accountant limited to finance view |
| Admin/Accountant | Manual payment/receipt history | policy + `Payment::recordManual`; stored-balance Family summary | Family/Collect Payment/History | Yes | Family total/student balances/itemized manual allocation; Accountant exact-family session guard |
| Accountant/Admin | Reversal request/approval | policy/action | Payment/Reversal views | Yes | Accountant requests, independent Admin approves |
| Admin | Event/charges/participation/payment status | action/controller plus read-only stored-due report | Sidebar → Events → Event → Participation & payment status | Yes | report does not generate charges or recalculate balances |
| Admin | Promotion draft/edit/confirm/discard/export | policy/actions plus confirmed-batch CSV/PDF export | Sidebar → Promotion → Confirmed batch | Yes | CSV neutralizes spreadsheet-formula values; reversal remains absent |
| Admin/Accountant | Reminder preview | policy/controller | Payment Reminders | Yes | no external provider enabled |
| Admin | Consent/verification/queue | Guardian/reminder actions | Guardian/reminder details | Yes | delivery rechecks eligibility |
| Guardian | profile/dues/payments | Guardian scope actions | Guardian sidebar | Yes | allocation-only financial scope |
| Teacher | classes/subjects/students | active-year section scope | Teacher sidebar | Yes | financial/staff data absent |
| Superadmin | user management | UserPolicy | Users pages | Yes | no separate dashboard |

## Backend-only or intentionally absent paths

| Capability | Status | Reason |
|---|---|---|
| Recurring scheduled dues | Backend generation exists; no scheduler | FR-M10.1 remains blocked by unspecified due-date/cycle scheduling policy |
| Allocation strategy configuration | No backend/UI | SRS open decision |
| Promotion reversal | No backend/UI | safety window and conflict-resolution policy unresolved |
| Attendance/exams/timetables | No backend/UI | outside Stage 1 implementation |
| External email delivery | guarded job refuses SMTP | safety/design limitation |
