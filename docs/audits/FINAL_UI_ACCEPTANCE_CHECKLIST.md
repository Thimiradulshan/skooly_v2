# Final UI Acceptance Checklist

## Verification boundary

This checklist is for manual browser acceptance. No browser automation package is installed in this repository, so it records the exact checks that remain visual/manual rather than claiming they ran.

## Admin / Superadmin

- [ ] Sign in and verify the appropriate landing page and sidebar active state.
- [ ] Open My profile; update display name, test current-password-gated password change, and open Security/TOTP management.
- [ ] Create, search, sort, and paginate Families and Students.
- [ ] Register a Student under an existing Family; verify selected Guardian links, private photo upload, placement, and optional contact overrides.
- [ ] Edit a Student and clear an override to confirm the Family address/contact is shown again.
- [ ] Verify Academic Year/Term create, edit, archive/restore, and active-year warning flows.
- [ ] Verify User edit/deactivation confirmation and Superadmin safeguards.
- [ ] Verify Family-balance payment collection, allocation preview, validation feedback, receipt view, PDF, and payment history.
- [ ] Verify Event participation/payment status is read-only and that viewing it creates no due items.
- [ ] Confirm a Promotion Batch and download CSV/PDF; verify spreadsheets render formula-prefixed names as literal text.
- [ ] Verify reminder consent, verification, queue, and audit-log pages expose only the stated local/internal behavior.

## Accountant

- [ ] Sign in and confirm the Finance workspace opens at exact Family-code collection.
- [ ] Open My profile and Security; confirm role/email fields are not self-editable.
- [ ] Attempt direct Family, Student, Event, Academic Setup, and Admin Dashboard navigation; confirm each is denied.
- [ ] Collect against a selected Family only, then inspect payment/receipt/reversal history and Dues Dashboard.

## Teacher

- [ ] Sign in and verify Teacher dashboard, My Classes, My Subjects, and My Students navigation.
- [ ] Open My profile and Security; confirm role/email fields are not self-editable.
- [ ] Confirm only active-year assigned sections/students are shown.
- [ ] Attempt financial and unassigned-student URLs; confirm no financial/contact override data is exposed.

## Guardian

- [ ] Sign in and verify the Guardian dashboard lists only explicitly linked Students.
- [ ] Open My profile and Security; confirm Guardian master-record and email fields are not self-editable.
- [ ] Verify linked Student profile, dues, and allocation-scoped payment history.
- [ ] Attempt an unlinked sibling URL and confirm it is unavailable.

## Responsive and accessibility checks

- [ ] Test sidebar drawer, tables, forms, confirmation dialog, and keyboard tab order at 320px, tablet, and desktop widths.
- [ ] Confirm visible labels, validation text, focus indicators, and escape/cancel behavior for confirmation dialogs.
- [ ] Review browser console after each workflow for JavaScript errors.

## Automated evidence

The feature suite, authorization tests, privacy tests, workflow tests, static analysis, formatter, build, audit, and diff checks provide automated evidence. They do not replace the visual/browser checks above.
