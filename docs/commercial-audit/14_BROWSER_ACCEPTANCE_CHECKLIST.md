# Browser Acceptance Checklist

**Status:** NOT EXECUTED in this audit. Complete on Chrome and Edge at 320, 375, 768, 1024, and 1440px.

## Every role

- [ ] Login/logout, failed login throttling, password reset, email verification, TOTP/challenge/recovery.
- [ ] Keyboard-only navigation, visible focus, skip/landmark behavior, no trap in confirmation dialog.
- [ ] Screen-reader names/labels/errors/toast announcements.
- [ ] Back/forward after a submitted write form restores controls.

## Admin workflows

- [ ] Initial SchoolSetting bootstrap, active-year switch warning, archive/restore.
- [ ] Family search/create/edit, guardian linking, student registration/photo/profile/placement.
- [ ] Fee setup, discount/subscription dates, recurring/event generation and duplicate protection.
- [ ] Payment allocation, two-child receipt HTML/PDF, reversal request/approval, student/family history.
- [ ] Promotion draft edit/retain/graduate/confirm/export and conflict feedback without technical details.
- [ ] Reminder generation, eligibility, queue feedback, cancellation, delivery history.

## Accountant / Teacher / Guardian

- [ ] Accountant exact-code lookup never links/browses unrelated family/student data.
- [ ] Teacher sees only active students in assigned active-year sections.
- [ ] Guardian sees only explicitly linked children; sibling due/payment/profile URL attempts return 404/403.

## Responsive and accessibility

- [ ] Sidebar opens/closes without obscuring focus; table overflow is usable.
- [ ] Touch targets, contrast, zoom 200%, form validation, error recovery.
- [ ] Print family/student/receipt; receipt PDF has complete child identity.
