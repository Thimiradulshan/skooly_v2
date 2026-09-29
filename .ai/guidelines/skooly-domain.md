# Skooly Stage 1 Domain Rules

## Source of truth

The approved Skooly SRS and its requirement IDs are the product source of truth.

When planning important work:
- reference relevant FR-Mx.y requirements;
- distinguish explicit requirements from implementation decisions;
- do not silently invent unresolved business rules.

## Architecture principles

- Prefer Laravel-native solutions and existing project conventions.
- Use Laravel Boost documentation/tools before guessing framework behavior.
- Apply YAGNI.
- Do not introduce a repository, service layer, interface, DTO, event,
  CQRS layer, or other abstraction without a current concrete reason.
- Keep changes scoped to the requirement being implemented.

## Core domain invariants

- A Family is the household registration/billing unit.
- A Guardian may see only Students explicitly linked to that Guardian.
- Combined family billing must never broaden Guardian data visibility.
- Student grade/section history belongs in year-specific Enrollment records.
- Never implement promotion by overwriting historical grade/section data.
- A Payment may allocate to multiple Due Items and may span multiple
  Students in one Family.
- Partial payment allocation must remain itemized per Due Item.
- Promotion confirmation must be atomic: all intended enrollment changes
  succeed or none do.
- Promotion must not automatically create next-year fee Due Items.
- Historical Family, Student, Enrollment and payment records must remain
  accessible according to the SRS.

## Unresolved requirements

Do not choose a default for these without an explicit product decision:

- partial combined-payment allocation strategy;
- sibling discount percentage/rule;
- promotion reversal safety window;
- duplicate-family detection: warn versus block.

## High-risk areas

Treat these as requiring extra review and tests:

- authentication and authorization;
- Guardian/Student privacy;
- fee calculation;
- payment allocation;
- discounts;
- payment modification;
- promotion confirmation/reversal;
- audit logging;
- destructive migrations or data correction.

## Definition of done

For substantial PHP changes, run the relevant subset and report results:

- php vendor/bin/pint --test
- php vendor/bin/phpstan analyse
- php artisan test
- composer audit

For frontend changes also run:

- npm run build

Do not claim completion with failing verification.
