# Frontend Missing Feature Backlog

Phase 10C-5 audit, updated by Phase 10D-1B. Items are ordered by whether they block
real school use, not by effort.

---

## Critical for school staff

These block ordinary use. The office cannot run the school on the app today without a
console.

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- |
| Academic setup archive/deactivate | List, create, view, edit, and update now exist for academic years, terms, grades, and sections. Old configuration cannot yet be hidden safely. | No status columns | No | Required. Define archive semantics before a migration and UI | 10D-4 |
| Active year behaviour | School Setting can now select the active year, but no existing workflow reads the setting. | Yes, `SchoolSetting` | Edit/update | Whether anything should *depend* on it | Later decision |
| Audit log filtering, export, and retention | Admins can now review entries and stored metadata, but cannot narrow, export, or retain them by policy. | Yes, `AuditLog` | Read-only index and detail | Retention policy and export format | 10D-6 |

**Phase 10D-1A removes the academic setup console dependency. Phase 10D-1B adds
Admin-only, read-only audit-log viewing.** Audit filtering, export, and retention
remain deferred.

Completed in Phase 10D-1A: **Academic Year**, Term, **Grade**, **Section**, and
School Setting list/create/view/edit/update web surfaces. Archive/deactivate remains
deferred for the Academic Year, Term, Grade, and Section records.

---

## High

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- |
| Payment list | Staff cannot answer "has this family paid?" without a payment ID. | Yes, `Payment` | No, show only | None to list | 10D-6 |
| Receipt list and PDF export | Receipts are reachable only by ID. Browser print works today; PDF does not. | Yes, `Receipt` | Show and print only | Receipt numbering rule, which also affects export | 10D-6 |
| User and role management | Staff accounts cannot be created without a shell. | Yes, `User`, `Role` | No | Whether roles are fixed or user-definable | 10D-2 |
| Teacher list and profile | Teachers cannot be managed at all. | Yes, `User` with teacher role | No | Same as user management | 10D-2 |
| Subject and assignment setup | Teacher assignments block any future teacher section scoping. | Yes, `Subject`, `TeacherAssignment`, `SectionYearAssignment` | No | None to create and list | 10D-2 |
| Guardian add and edit | A family can only ever have the single guardian entered at creation. A second parent cannot be added. | Yes, `Guardian` | Indirect only | None | 10D-3 |
| Student detail and edit | Students are reachable only through a family. Admission number, status, and photo path cannot be corrected. | Yes, `Student` | No | None | 10D-3 |
| Discount list and deactivate | A discount can be applied but never reviewed or withdrawn. A wrong discount silently affects future dues. | Yes, `Discount` | No | Whether withdrawal is "deactivate" or "end date" | 10D-3 |
| Fee subscription list and end | A transport subscription cannot be switched off once granted. | Yes, `StudentFeeSubscription` | No | Same as discount | 10D-3 |
| Search, sort, and pagination | Every list degrades at real school size. | n/a | No | None | 10D-6 |
| Payment correction or refund | **There is no correction path for a mistaken payment at all.** A duplicate or wrong-amount payment is permanent. | No | No | **Required.** Needs amount ceiling, audit, approval, and whether due items are reopened | 10D-7 |
| Accountant access | Finance work is impossible for anyone but an Admin. | Role and policies exist | No pages | Required before any Accountant page | 10D-8 |

---

## Medium

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- |
| Discard draft promotion batches | A `discarded` status exists but no UI can use it. Abandoning a draft means creating another batch. | Status exists, action does not | No | Low risk to add | 10D-4 |
| Cancel stale reminders | A `cancelled` status exists but a stale reminder cannot be withdrawn. | Status exists, action does not | No | Low risk to add | 10D-4 |
| Promotion item editing | Every exception case is blocked. A single student cannot be retained, excluded, graduated, or retargeted. | No | No | Required. Backend edit action first | 10D-8 |
| Fee structure editing | A price cannot be corrected after creation. | No | No | Whether editing may affect already-generated dues | 10D-5 |
| Event charge editing | Same problem, smaller blast radius. | No | No | Same | 10D-5 |
| Event participation management | Only students in charge grades can be opted in. | Partial | Partial | Whether wider opt-in is valid | 10D-3 |
| Report export | The dashboard cannot be shared or filed. | `BuildDuesDashboardReport` | No | Export format | 10D-9 |
| Enrollment list and edit | A student cannot be moved between sections after registration. | No | No | Whether to allow section moves | 10D-3 |
| Guardian-Student link management | Revoking a link is the main privacy lever and is console-only. | No | No | Whether revocation needs audit | 10D-3 |

---

## Low

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- |
| Recent activity feed on the admin dashboard | Would make the audit trail visible without a separate page. | `AuditLog` | No | Depends on the audit UI | 10D-1 |
| Attention queue on the dashboard | Shows what needs action today rather than only counts. | Would need new queries | No | What counts as needing attention | 10D-9 |
| Charts and widgets | Visual summaries of collections. | No | No | None | 10D-9 |
| Status badges and density polish | Mostly done in 10C-4B and 10C-4C. | n/a | Yes | None | Later |
| Remove the dead `welcome.blade.php` | Unreachable since `/` redirects. | n/a | Dead file | None | 10D-4 |
| Print stylesheet for the family and student pages | Only the receipt currently prints cleanly. | n/a | Partial | None | 10D-9 |

---

## Suggested implementation order

1. **10D-1 — Academic setup and audit viewing.** Without these, no non-technical
   staff can operate the app. Everything else is blocked behind a console.
2. **10D-2 — User, role, teacher, subject, and assignment management.** Staff
   onboarding depends on it.
3. **10D-3 — Student and guardian management.** Fixes the one-guardian-per-family
   limitation and the missing student record pages.
4. **10D-4 — Archive, discard, and cancel workflows.** Applies the draft policy in
   `docs/destructive-action-policy-draft.md`.
5. **10D-6 — Lists, search, and pagination.** Makes the existing screens usable at scale.
6. **10D-7 — Payment correction or refund.** The only genuinely missing capability
   that cannot be deferred indefinitely.
7. **10D-5 — Fee structure and event charge editing.** Requires a re-pricing decision.
8. **10D-8 — Promotion item editing and Accountant access.** Both need decisions.
9. **10D-9 — Exports, prints, and dashboard refinements.**
