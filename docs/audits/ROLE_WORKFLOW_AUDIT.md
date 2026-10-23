# Role and Workflow Audit

## Roles discovered

`Superadmin`, `Admin`, `Accountant`, and `Teacher` are persisted roles. Guardian access is a User-to-Guardian link, not a role.

| Actor | Successful workflow evidence | Restrictions verified |
|---|---|---|
| Admin | academic setup → Family/Guardian → Student/enrollment/payment history; qualification → assignment correction; fees → dues → Family-balance payment → receipt; event → participation/payment status → dues; promotion draft → confirmation → export; reminder administration | Admin-only routes and Form Requests; finance/promotion audit tests |
| Accountant | exact Family-code lookup → manual allocation → receipt; finance history/dashboard; reversal request | cannot browse Families/Students/Guardians, mutate reminders, approve reversals, or open Admin dashboard (`WebAccountantAuthorizationTest`) |
| Teacher | active-year assignment → classes/subjects → section-scoped roster → student academic profile | cross-section profile is 404; inactive account denied; no finance UI (`TeacherPortalTest`) |
| Guardian | linked verified account → linked profiles → authorized dues/payment allocations | unlinked sibling is 404/hidden; deactivated account denied; no Family-wide receipt/payment disclosure (`GuardianPortalTest`) |
| Superadmin | create/manage accounts including Admin/Superadmin boundaries | no additional operational workflow beyond authorized Admin capability |

## High-value multi-step workflow status

| Workflow | Status | Evidence / limitation |
|---|---|---|
| Academic setup → registration → directory | PARTIALLY COMPLETE | registration works; registration due/status automation missing |
| Qualification → assignment → Teacher portal | COMPLETE | seeded/tested active-year assignment |
| Fee structure → dues → Family balance → manual payment → receipt | COMPLETE | stored Family/Student balances, manual itemized allocation, immutable receipt snapshot |
| Payment → reversal request → independent approval | COMPLETE | append-only tested workflow |
| Enrollment → promotion draft → confirm | COMPLETE | atomic history-preserving confirmation |
| Guardian record → explicit link → portal | COMPLETE | matching verified account selector added |
| Reminder → consent → verification → eligibility → queued attempt | PARTIALLY COMPLETE | local/log-only; no external delivery |
| Event → charge/participation → generated dues → status report | COMPLETE | Admin report reads stored participation and due/payment snapshots only |
