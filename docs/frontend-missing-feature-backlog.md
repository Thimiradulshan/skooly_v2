# Frontend Missing Feature Backlog

Phase 10C-5 audit, updated by Phase 10D-6C. Items are ordered by whether they block
real school use, not by effort.

---

## Critical for school staff

These block ordinary use. The office cannot run the school on the app today without a
console.

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- |
| Academic setup archive/deactivate | Admins can archive and restore academic years, terms, grades, and sections. Archived records remain visible in history and are excluded only from new configuration selections. | `is_archived` lifecycle flags | Yes | Complete in 10D archive workflow | Complete |
| Active year behaviour | School Setting can now select the active year, but no existing workflow reads the setting. | Yes, `SchoolSetting` | Edit/update | Whether anything should *depend* on it | Later decision |
| Audit log filtering, export, and retention | Admins can now review entries and stored metadata, but cannot narrow, export, or retain them by policy. | Yes, `AuditLog` | Read-only index and detail | Retention policy and export format | 10D-6 |

**Phase 10D-1A removes the academic setup console dependency. Phase 10D-1B adds
Admin-only, read-only audit-log viewing.** Audit filtering, export, and retention
remain deferred.

Completed in Phase 10D-1A: **Academic Year**, Term, **Grade**, **Section**, and
School Setting list/create/view/edit/update web surfaces. Academic Year, Term, Grade,
and Section archive/restore are complete; the active academic year cannot be archived.

---

## High

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- |
| Payment list | Staff can now browse, search, sort, and paginate payment history. | Yes, `Payment` | Yes | None | Complete in 10D-6 |
| Receipt list and PDF export | Receipts can now be browsed, searched, sorted, and paginated. Browser print works today; PDF does not. | Yes, `Receipt` | List, show, print | Receipt numbering rule, which also affects export | PDF deferred |
| User lifecycle and authorization expansion | Fixed-role user management and archiving now exist, but Accountant and Teacher operational permissions remain Admin-only. | Yes, `User`, `Role` | List/create/view/edit/archive | Per-resource role permissions | 10D-8 |
| Teacher profile refinement | Teachers can be managed as Users and their qualifications can be viewed, but no dedicated profile beyond the staff account exists. | Yes, `User` with teacher role | Partial | None | Later |
| Subject and assignment corrections | Subjects have CRUD and qualifications/assignments have list/create/view, but teaching configuration cannot yet be corrected or withdrawn. | Yes, `Subject`, `TeacherAssignment`, `SectionYearAssignment` | Partial | Correction/history rule | Later |
| Guardian management refinement | Guardians can now be added/edited and explicitly linked/unlinked from Students, but no Guardian login exists. | Yes, `Guardian` | Add/edit/view/link/unlink | Guardian authentication | Later |
| Student photo upload | Student details and status can be edited, but photo_path accepts an existing path only; secure file upload is absent. | Yes, `Student` | Detail/edit | Upload/storage policy | Later |
| Discount history/audit | Discounts can be reviewed and deactivated for future generation, but deactivation is not separately audited. | Yes, `Discount` | List/deactivate | Audit event decision | Later |
| Fee subscription history/audit | Subscriptions can be reviewed and ended for future generation, but ending is not separately audited. | Yes, `StudentFeeSubscription` | List/end | Audit event decision | Later |
| Search, sort, and pagination | In-scope Admin lists now have validated search where relevant, fixed sorting, direction controls, and pagination with query-string preservation. Audit logs and Student-scoped histories intentionally remain outside this rollout. | n/a | Yes | None | Complete in 10D-6C |
| Payment correction or refund | Full payment reversal is now available through an Accountant request and independent Admin approval, with due-item reopening and correction receipts. Refunds and partial reversals remain absent. | Yes | Yes | None for the approved full-reversal workflow | Complete in 10D-7 |
| Accountant access | Accountants can view payment/receipt history and request/list payment reversals. Collection, family, and approval access remain Admin-only. | Yes | Finance history and reversals only | Broader operational permissions | Later |

---

## Medium

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- |
| Discard draft promotion batches | Drafts can now be discarded without changing Students or Enrollments. | Yes | Yes | None | Complete in 10D-4 |
| Cancel stale reminders | Pending reminder records can now be cancelled without sending a message or changing snapshots. | Yes | Yes | None | Complete in 10D-4 |
| Promotion item editing | Every exception case is blocked. A single student cannot be retained, excluded, graduated, or retargeted. | No | No | Required. Backend edit action first | 10D-8 |
| Fee structure editing | Amount and frequency can be corrected before a due item directly references the structure. | Yes | Yes | Approved: lock after direct due generation | Complete in 10D-5 |
| Event charge editing | Amount can be corrected before the Event has any due item. | Yes | Yes | Approved: conservatively lock every charge after event due generation | Complete in 10D-5 |
| Event participation management | Only students in charge grades can be opted in. | Partial | Partial | Whether wider opt-in is valid | Later |
| Report export | The dashboard cannot be shared or filed. | `BuildDuesDashboardReport` | No | Export format | 10D-9 |
| Enrollment reporting refinement | Enrollment placement can be viewed and moved with preserved history, but no standalone enrollment list exists. | Yes | Student detail/placement | None | Later |
| Guardian-Student link reporting | Links can be created and revoked with an audit record, but no standalone link report exists. | Yes | Student detail | None | Later |

---

## Low

| Gap | Why it matters | Backend exists | Frontend exists | Decision needed | Suggested phase |
| --- | --- | --- | --- | --- | --- | --- |
| Recent activity feed on the admin dashboard | Would make the audit trail visible without a separate page. | `AuditLog` | No | Depends on the audit UI | 10D-1 |
| Attention queue on the dashboard | Shows what needs action today rather than only counts. | Would need new queries | No | What counts as needing attention | 10D-9 |
| Charts and widgets | Visual summaries of collections. | No | No | None | 10D-9 |
| Status badges and density polish | Mostly done in 10C-4B and 10C-4C. | n/a | Yes | None | Later |
| Remove the dead `welcome.blade.php` | Unreachable since `/` redirects. | n/a | Dead file | None | 10D-4 |
| Print stylesheet for the family and student pages | Family and Student detail pages have browser print controls and scoped print output that retains record details and omits the admin shell and mutation controls. | n/a | Yes | None | Complete in 10D-9 |

---

## Suggested implementation order

1. **10D-1 — Academic setup and audit viewing.** Without these, no non-technical
   staff can operate the app. Everything else is blocked behind a console.
2. **10D-2 — User, role, teacher, subject, and assignment management.** Staff
   onboarding depends on it.
3. **10D-3 — Student and guardian management.** Fixes the one-guardian-per-family
   limitation and the missing student record pages.
4. **10D archive workflow — complete.** Academic configuration archives safely without
   deleting or rewriting history.
5. **10D-6 — Lists, search, pagination, and safe sorting.** Makes the existing screens usable at scale.
6. **10D-7 — Payment correction or refund.** The only genuinely missing capability
   that cannot be deferred indefinitely.
7. **10D-5 — Fee structure and event charge editing: complete.** Prices lock after due generation; snapshots are never changed.
8. **10D-8 — Promotion item editing and Accountant access.** Both need decisions.
9. **10D-9 — Print-friendly Family and Student detail pages are complete.** Exports and dashboard refinements remain deferred.
