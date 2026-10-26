# Role Workflow Audit

| Role | Reachability and workflow conclusion | Material restrictions / findings |
| --- | --- | --- |
| Superadmin | Can manage users; Admin capability is separately role-based. | Verify dual-role UX in browser. User deactivation does not terminate its current session (CA-01). |
| Admin | Broad SRS setup, registration, fee, payment, promotion, reminder, audit and export UI is reachable through navigation. Initial SchoolSetting setup is available (CA-14 resolved 2026-10-25). | Receipts fail child itemization (CA-02). |
| Accountant | Exact family-code collection; payment/receipt/reversal read and request, dashboard, reminder read. Route groups deny general family/student/admin management. | Cannot resolve a payment by student directory; may be product-acceptable but M7.1 is partial. |
| Teacher | Assigned class/subject/dashboard and student directory are implemented with section filtering. | Current directory includes pending-registration students, violating M6.5 (CA-03). No attendance module is Stage-1 scope. |
| Guardian-linked User | Dashboard, linked-student profile, dues and payments are constrained by guardian-student links. | Browser privacy/UAT still required; no mutation/payment portal flow is provided. |

## Privacy controls reviewed

`GuardianPortalController.php:39` returns 404 unless an explicit guardian-student link exists. Dues and allocations are constrained to IDs returned by `ListGuardianVisibleStudents` (`GuardianPortalController.php:46-73`). This is good defense against family-level sibling disclosure, but it is static/test evidence rather than live penetration testing.
