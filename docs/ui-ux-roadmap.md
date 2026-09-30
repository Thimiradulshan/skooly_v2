# Skooly UI/UX Roadmap

## Honest assessment of the current interface

The current pages are **functional plain HTML**. They were built to prove the backend
works, not to be pleasant to use. There is no design system, no CSS framework, and no
responsive layout.

What that means in practice:

- Every page is a stack of tables and raw form fields on a white page.
- There is no sidebar. Navigation is one long row of pipe-separated text links.
- Forms have no consistent field grouping, no required indicators, and no inline help.
- Tables have no sorting, pagination, or search. The payment form lists every
  outstanding due item for a family.
- Validation errors appear as a list at the top, not next to the offending field.
- Empty states exist but are plain one-line sentences.
- There is no confirmation step for anything irreversible-looking, and no loading or
  disabled state on submit buttons.
- On a phone or tablet these pages are effectively unusable. Wide tables overflow.
- There is no visual sense of "where am I" beyond a heading.

Nothing here is broken. It is simply unpolished, and it will slow down and increase
errors for real staff.

## The gaps that matter most

Ranked by how much they hurt day-to-day use:

1. **No navigation model.** A flat link row does not scale past a handful of
   sections. Staff cannot tell what belongs where.
2. **No real form design.** Long forms, especially payment collection, are error
   prone.
3. **No way to find anything.** Every list is unfiltered or unpaginated. The student
   list for a family shows everyone.
4. **Destructive-looking actions have no confirmation.** Confirming a promotion batch
   is a single button press.
5. **No role-specific views.** Accountant and Teacher see nothing at all, so there is
   no design to review for them yet.
6. **No responsive layout.** Tables break on small screens.

## Roadmap

Documentation only. Nothing in this section is implemented.

### Phase A: Foundation

**Login redesign.** Currently a bare form in the middle of a white page. Needs a
proper sign-in screen: clear product identity, a real error state for bad credentials,
and a visible note about the local demo account. Password reset cannot be designed
until the reset flow is decided.

**Admin layout and sidebar.** Replace the flat link row with a persistent sidebar
grouping the product the way staff think:

- Overview (dashboard)
- People (families, students, guardians)
- Money (fee categories, fee structures, payments, receipts)
- Dues (generation, dashboard, reminders)
- School life (events)
- Records (promotion, audit)

The sidebar should show the current section, collapse on small screens, and keep the
sign-out control in a fixed place.

**Shared design tokens.** One place defining colour, spacing, typography, table
styling, form styling, button variants, and status badges. Everything after this
reuses it. This prevents a second round of inconsistency.

### Phase B: Core screens

**Dashboard redesign.** The current page is a list of counts and links. It should
answer the questions an Admin actually opens the app with: what is outstanding this
month, what is overdue, which families owe the most, and what needs attention today.
Add a short activity feed from the audit log while that data has no UI.

**Family and student registration.** A step-by-step wizard instead of three separate
forms. Step one creates the family and guardians, step two adds students and links
guardians, step three enrols. A wizard makes the guardian-linking rule obvious
instead of hidden in a multi-select list.

**Fee setup.** The current forms require knowing internal orderings. Show fees grouped
by category, make the academic year obvious, and warn clearly when saving a structure
that will affect next cycle.

**Payment collection screen.** The most important screen to redesign. It needs:
searchable, paginated outstanding due items; a running total as allocations are
entered; inline validation on each row; a clear receipt-number field with a generated
suggestion once numbering is decided; and a review step before submitting.

**Receipt view.** Needs a print stylesheet so printing produces a clean receipt, and a
PDF export once the numbering rule is settled.

**Promotion workflow.** Confirmation currently happens with one button press. It needs
a review screen showing exactly what will change, which students will graduate, which
have no target, and a clear warning that it cannot be reversed. Per-item target
editing should follow once the backend supports it.

**Reminder preview and delivery.** Today the Admin generates records and sees rows.
It should show a per-guardian preview of the message that would be sent, then make
delivery a separate, explicit action once a provider is chosen.

### Phase C: Role-specific experiences

**Accountant dashboard.** Currently an Accountant can sign in and reach nothing. They
need their own dashboard focused on collections, outstanding balances, and receipts,
with no access to promotion or event setup.

**Teacher dashboard.** Teachers need a read-only view of their sections and students.
Section scoping depends on the unresolved teacher access decision, so this must wait
for that.

**Guardian portal.** Needs a Guardian login and a family-scoped view showing only
linked children, their balances, and receipts. Blocked on the Guardian-to-User link
decision.

### Cross-cutting work

**Search and pagination** for every list, starting with students and due items.

**Status badges** so unpaid, partially paid, paid, draft, confirmed, and pending
reminders are readable at a glance rather than as lowercase strings.

**Empty states that teach.** "No families yet" is fine; "No families yet. Create the
first one." with a button is better.

**Confirmations and undo messaging** for anything that cannot be reversed.

**Accessibility and responsiveness.** Keyboard navigation, labels tied to inputs,
sufficient contrast, and tables that work on small screens.

**Error presentation.** Field-level messages next to inputs, with the summary
retained for screen readers.

## Sequencing note

UI work should not start before the product decisions that change what the screens
must show are settled. Specifically, the Accountant and Teacher views, the payment
allocation strategy, and receipt numbering all affect layout and interaction. The
Phase A foundation work, however, is safe to start now.
