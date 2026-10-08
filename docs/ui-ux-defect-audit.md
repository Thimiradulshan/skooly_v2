# UI/UX Defect Audit and Workflow Completion

Phase 10C-4C. This is a record of what was audited, what was actually broken, and what
was changed. It is not a design wish list.

Scope: the existing Admin web app only. No backend behaviour was changed.

## Why the alert system is local JavaScript

The Vite and Tailwind pipeline is installed, but its build output lives in
`/public/build`, which is gitignored. Depending on that build would break every page
on a fresh checkout and break the test suite. SweetAlert2 was therefore rejected and
`public/js/admin-ui.js` was written instead: dependency free, no CDN, no build step,
served straight from `public/`.

Every behaviour in that file is progressive enhancement. If it fails to load, forms
still submit normally, flash messages remain readable, and nothing is lost.

## Screen-by-screen audit

| Screen | Current problem | Fix applied | Remaining limitation |
| --- | --- | --- | --- |
| Login | No submission state, so a double click could submit twice | Added `data-loading` so the button disables and shows a spinner; added password reset link | Two-factor authentication is not selected |
| Admin dashboard | Good structure, but no guidance on what to do first | Kept the grouped workflow cards and added eyebrow labelling so each group reads as a stage | Counts only; no "needs attention" list, because that would need new queries |
| Families index | Empty state was dead text with no way forward | Empty state now explains the prerequisite and offers a Create family button | No search, sort, or pagination |
| Family show | All actions looked equal, and there was no way back to the list | Added a breadcrumb, made Edit the primary action, kept the rest secondary | No per-student detail route exists, so student rows cannot be opened |
| Student registration | No indication of which family was in scope | Added a Families / FAM-X / Register student breadcrumb | No multi-step wizard |
| Fee categories | Empty state was dead text | Empty state now offers Create fee category | None material |
| Fee structures | Empty state was dead text | Empty state now offers Create fee structure; header cross-links to fee categories | No copy-to-next-year action |
| Discounts | No family or student trail when reviewing a discount | Added a Families / FAM-X / Student breadcrumb | No discount list page exists, so past discounts cannot be reviewed in the UI |
| Fee subscriptions | Same as discounts | Same breadcrumb | No subscription list page exists |
| Recurring due generation | Clicking Generate did irreversible-looking work with no warning | Added a confirmation dialog, a loading state, and a callout explaining opt-in and discount handling | Generation is still manual; no scheduler |
| Event due generation | Same problem, plus no route back to the event list | Added confirmation, loading, a Cancel link to Events, and a back link to the dashboard | Only existing events can be processed |
| Dues dashboard | Filter panel looked like the rest of the page | Kept the filter card and cross-links to generation and reminders | No charts, no date comparison, no export |
| Payment create | The most dangerous screen had no confirmation and the manual-allocation rule was buried in a footnote | Added a danger confirmation, a loading state, a numbered callout explaining manual allocation, and a stronger receipt-number warning | Allocation rows are unpaginated, so a large family is slow |
| Payment show | No trail back through the family | Added a Families / FAM-X / Payment breadcrumb | No payment list page exists |
| Receipt show | Did not read as a receipt; looked like another data table | Rebuilt as a receipt sheet with brand, receipt number, parties, line table, and a Total received block; added a print stylesheet | No PDF export |
| Events index | Empty state was dead text | Empty state now offers Create event | None material |
| Event show | No way back to the event list and no primary action | Added a breadcrumb, made Edit the primary action, and added a callout stating that editing never creates dues | Charges cannot be edited or deleted, which is intentional but not explained in-place |
| Event charges | No explanation of why it is create-only | Kept the create-only form and explained the snapshot rule | No per-charge edit |
| Event participation | Mandatory versus opt-in was unclear | Added an info callout distinguishing the two, and kept the student chooser | No bulk select-all without JavaScript |
| Promotion batches | Empty state was dead text | Empty state now offers Create promotion batch | No discard action, because no backend route exists |
| Promotion batch show | Confirm had no warning and looked like any other button | Added a danger confirmation, a loading state, and a callout stating that source enrollments are preserved and no fees are generated | No reversal, because the safety window is undecided |
| Payment reminders index | Empty state was dead text | Empty state now offers Generate reminders | No send action, correctly, because no provider exists |
| Payment reminder show | The "nothing is sent" caveat was a small footnote | Promoted it to a labelled preview-only callout that states nothing was sent and no provider is connected | No delivery history, because nothing is delivered |

## Workflow actions added

| Screen | Actions now visible |
| --- | --- |
| Families index | Create family |
| Family show | Edit, Register student, Record payment, per-student Discount and Fee subscription, breadcrumb home |
| Student registration | Save, Cancel, breadcrumb, loading state |
| Fee categories index | Create fee category, Edit per row, link to fee structures |
| Fee structures index | Create fee structure, link to fee categories |
| Discount / subscription | Breadcrumb to family and student, Save, Cancel |
| Recurring generation | Generate with confirmation, Cancel, back to dashboard, fee structures |
| Event generation | Generate with confirmation, Cancel to Events, back to dashboard |
| Dues dashboard | Recurring generation, reminders, dashboard |
| Payment create | Record with danger confirmation, Cancel, generate-dues fallback, loading state |
| Payment show | View receipt, back to family, breadcrumb |
| Receipt show | Back to payment, back to dashboard, breadcrumb |
| Events index | Create event |
| Event show | Edit, Add charge, Manage participation, Generate event dues, breadcrumb |
| Promotion index | Create promotion batch |
| Promotion show | Confirm with danger confirmation, back to list, breadcrumb |
| Reminders index | Generate reminder records |
| Reminder show | Back to list, breadcrumb, preview-only callout |

## Confirmation and loading behaviour

Five actions now ask before running, because each one writes financial or academic
state and none can be undone from the UI:

- Generate recurring dues
- Generate event dues
- Record a manual payment
- Generate reminder records
- Confirm a promotion batch

Payment and promotion use the danger tone. All write forms carry `data-loading`, so the
submit button disables and shows a spinner on submit, preventing double submission.
Backend idempotency was not changed.

## Accessibility notes

- Toasts use `role="status"` and errors use `role="alert"`, so failures are announced.
- Toasts and the confirmation dialog are dismissible by keyboard, and Escape closes
  the dialog and restores focus.
- Tone is never the only signal; every toast and callout carries text.
- `prefers-reduced-motion` disables animation.

## Known gaps carried forward

These are unchanged from earlier reviews and are not UI defects:

- No search, sort, or pagination anywhere.
- No student, guardian, or payment list pages, so records can only be reached from a
  family.
- No academic setup screen; years, grades, and sections still need console access.
- No audit review screen.
- No receipt PDF export.
- `resources/views/welcome.blade.php` is now unreachable because `/` redirects. It is
  dead markup and should be removed in a later cleanup.
