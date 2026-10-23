# UI/UX Defect Register

| ID | Priority | Finding | Evidence | Status |
|---|---|---|---|---|
| UX-01 | P1 | Guardian account link asked for an undiscoverable numeric User ID | old `guardians/show.blade.php` | FIXED: matching verified-account selector |
| UX-02 | P1 | Teacher sidebar exposed forbidden finance links; non-Admin brand link opened Admin dashboard | `layouts/app.blade.php` | FIXED: role-aware home/payment navigation |
| UX-03 | P2 | Family duplicate search omitted Guardian name/phone/NIC | `FamilyController`/Family index | FIXED: relationship search and label |
| UX-04 | P2 | Combined billing had no explicit consolidated total on the collection screen | payment create view | FIXED: stored Family total, linked Student balances, itemized dues, allocation total, and unallocated amount are visible before submission |
| UX-05 | P2 | Event page lacked family/student payment-status view | Event views/controllers | FIXED: Admin-only Event Participation & Payment Status screen reads applicable enrollment, participation, event dues, and stored balances without generating charges |
| UX-06 | P2 | Promotion had no export action | promotion routes/views | FIXED: confirmed batches expose Admin-only CSV/PDF downloads with source, target, and outcome details |
| UX-07 | P3 | Real-browser responsive, keyboard, and screen-reader verification unavailable | no browser automation installed | NOT TESTED; manual review required |
| UX-08 | P3 | Several complex legacy Blade views are densely single-line, hindering maintenance/accessibility review | Guardian/portal views | UNRESOLVED; no behavior defect proven |
| UX-09 | P1 | Changing the active year could silently leave draft promotion batches behind | `SchoolSettingController` had no promotion-batch query or acknowledgement | FIXED: warning, review link, and server-enforced acknowledgement before switch |
| UX-10 | P2 | Deactivating a user account was a checkbox change with no confirmation | `users/edit.blade.php` | FIXED: account-save confirmation explicitly explains immediate sign-in loss; server lifecycle guards remain in force |
| UX-11 | P2 | Family delete/deactivate was requested despite historical academic/financial dependencies | FR-M5.6, Family routes/model | INTENTIONALLY RESTRICTED: no destructive or unsupported lifecycle control; history-preserving edit/search/list controls remain available |
| UX-12 | P2 | Grade-specific Subject selector was requested without an SRS Grade–Subject entity or rule | FR-M3.2–M3.3 and `TeacherAssignment` schema | INTENTIONALLY RESTRICTED: assignment is Section + qualified Teacher + Subject; Grade context comes from Section |
| UX-13 | P2 | Assignment remove/unassign controls could erase or rewrite yearly teaching history | `TeacherAssignment` / `SectionYearAssignment` lifecycle model | INTENTIONALLY RESTRICTED: current teaching assignment correction is audited; historical assignments and class-in-charge removal remain unavailable pending retention rules |
| UX-14 | P2 | Admin could not inspect a single Student's historical payment allocations | Student profile had no allocation history entry point | FIXED: Admin-only Student Payment History shows allocation amount, payment date/reference, receipt, and related reversal/correction status |
| UX-15 | P1 | A private Student photo upload could not be displayed from the profile | uploads stored to private local disk without a delivery route | FIXED: Admin-only inline photo route streams the private image; no public storage URL is emitted |
| UX-16 | P1 | Successful two-factor challenge redirected non-Admin roles to an Admin-only route | `TwoFactorAuthenticationController` used the unconditional Families redirect | FIXED: post-challenge destination now matches the role-aware login workspace |
