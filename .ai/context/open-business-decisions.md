# Open Business Decisions

Do not invent answers for these.

## Global Unresolved Decisions
- Default payment allocation strategy remains unresolved.
- Default sibling discount percentage/rule remains unresolved.
- Promotion reversal safety window remains unresolved.
- Duplicate-family detection: block versus warning remains unresolved.

## Family and Guardian Rules
- family_code is unique; duplicate-family detection beyond that remains unresolved as block versus warning.
- Do not make guardian email, contact_no, or nic unique yet unless confirmed.
- Guardian visibility is controlled by guardian_student, not family membership alone.
- Combined billing is enabled by default for a family, but its future payment-allocation strategy remains unresolved.

## Student Rules
- admission_no is required and globally unique; auto-generation is not implemented.
- Student status is a simple string and defaults to pending_registration.
- Pending Registration must remain until registration due items are paid, but fee generation and payment-driven status transitions are deferred.
- gender is stored as an unconstrained required string; no enum or SRS value set has been defined.
- photo_path is nullable; upload and storage behavior are deferred.

## Phase 5 Rules
- Default payment allocation strategy remains unresolved; no payment or allocation workflow exists.
- Default sibling discount percentage/rule remains unresolved; no automatic sibling discount suggestion or application exists.
- Discounts are per Student and FeeCategory and are snapshotted to DueItemDiscount when a future due-generation workflow applies them.
- Fee due generation is schema-ready only; recurring scheduling is deferred.
