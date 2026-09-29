# Open Business Decisions

Do not invent answers for these.

## Global Unresolved Decisions
- Default payment allocation strategy.
- Default sibling discount percentage/rule.
- Promotion reversal safety window.
- Duplicate family detection: block or warning.

## Family and Guardian Rules
- family_code is unique; duplicate-family detection beyond that remains unresolved as block versus warning.
- Do not make guardian email, contact_no, or nic unique yet unless confirmed.
- Guardian visibility is controlled by guardian_student, not family membership alone.
- Combined billing is enabled by default for a family, but its future payment-allocation strategy remains unresolved.

## Phase 4 Rules
- admission_no is required and globally unique; auto-generation is not implemented.
- Student status is a simple string and defaults to pending_registration.
- Pending Registration must remain until registration due items are paid, but fee generation and payment-driven status transitions are deferred.
- gender is stored as an unconstrained required string; no enum or SRS value set has been defined.
- photo_path is nullable; upload and storage behavior are deferred.
