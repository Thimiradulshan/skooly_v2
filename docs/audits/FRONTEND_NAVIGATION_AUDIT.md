# Frontend Navigation Audit

Date: 2026-10-25

## Inventory

- Runtime routes inspected: **189**.
- Named runtime routes: **187**.
- Literal Blade `route()` references inspected: **383** references across **180** unique route names.
- Missing named routes referenced by Blade: **0**.
- Hardcoded root-relative `href`/`action` values in Blade: **0 found**.
- UI architecture inspected: shared sidebar, dashboard actions, index/create/detail/edit controls, lifecycle forms, exports, portals, profile, security, payments, receipts, reversals, reminders, events, promotions, audit logs, and academic/staff setup.

## Role-to-interface matrix

| Role | Visible control group | Route families | Middleware / policy boundary | Response |
|---|---|---|---|---|
| Admin | Overview | `admin.dashboard` | `auth`, `verified`, `role:Admin` | Admin dashboard Blade |
| Admin | Academic Setup | `academic-years.*`, `terms.*`, `grades.*`, `sections.*`, `school-settings.*`, `subjects.*` | Admin route group; active-year middleware only on new year-scoped operations | Setup/list/detail/form Blades |
| Admin | Students & Families | `families.*`, `guardians.*`, `students.*`, `enrollments.placements.*` | Admin route group; explicit Guardian links preserved | Registration and history Blades |
| Admin | Staff & Teachers | `users.*`, `teacher-qualifications.*`, `teacher-assignments.*`, `section-year-assignments.*` | Admin/Superadmin user policy plus Admin assignment routes | Staff and assignment Blades |
| Admin | Finance | `fee-*`, `due-generation.*`, `payments.*`, `receipts.*`, `payment-reversals.*`, `dues-dashboard.*`, `payment-reminders.*` | Admin or approved Admin/Accountant groups; Payment policy on collection | Finance Blades, streams, PDFs |
| Admin | Events & Promotions | `events.*`, `promotion-batches.*` | Admin group; active-year enforcement for new operations | Event/promotion Blades and exports |
| Admin | Reports & Audit | `audit-logs.*` | Admin-only, read-only | Index/detail/CSV/PDF |
| Accountant | Finance | collection, payment/receipt/reversal history, dues dashboard, reminder read pages | `role:Admin,Accountant`; mutation routes remain separately restricted | Finance-only workspace |
| Teacher | Teacher Portal | `teacher.portal`, `teacher.classes`, `teacher.subjects`, `teacher.students*` | `role:Teacher`; section assignment scope; privacy 404 retained | Teacher portal Blades |
| Guardian | Guardian Portal | `guardian.portal`, `guardian.students.show`, `guardian.dues`, `guardian.payments` | Authenticated linked account; explicit `guardian_student` checks | Guardian-scoped Blades; privacy 404 retained |
| Superadmin-only | Administration | `users.*` | `role:Admin,Superadmin` plus `UserPolicy` | User management Blades |
| All authenticated roles | Account | `profile.*`, `two-factor.*`, `logout` | `auth`, `verified` as applicable | Account/security Blades |

## Findings and repairs

1. **CA-14 first-run dead end:** School Settings was linked but returned 404 when no singleton existed. Repaired with an explicit initial setup form.
2. **Dependent create links:** year-scoped controls could lead to an exception/404 before setup. New route middleware redirects authorized Admins to School Settings with visible feedback.
3. **Navigation density and duplication:** the former flat sidebar duplicated reporting links and contained an empty Communication heading. Replaced with role-specific, collapsible native `<details>` groups.
4. **Mobile navigation semantics:** the former hidden checkbox/label mechanism was replaced with real buttons, `aria-controls`, `aria-expanded`, Escape close, focus movement, and a backdrop close control.
5. **Desktop navigation:** a persisted collapsed mode was added without removing any route.
6. **Active state:** active links now receive `aria-current="page"` through rendered markup or progressive enhancement.

## Intended 404s retained

- Teacher requests for Students outside assigned sections.
- Guardian requests for Students without an explicit Guardian–Student link.
- Missing route-model-bound records.

These responses protect record existence and were not classified as broken navigation.

## Verification

- Navigation/UI/Accountant focused run: 28 tests, 226 assertions passed.
- Academic setup and route-repair focused coverage passed after correcting a false-positive selector test that had previously accepted an error response.
- Static route-name audit found no unresolved Blade route names.

## Limitation

The matrix is based on runtime route metadata, source inspection, and HTTP feature tests. Real-browser link crawling was not available and is not claimed.
