# Destructive Action Policy Draft

Phase 10C-5. A **draft for discussion**. Nothing here is implemented, and no delete or
archive route exists in the application today.

The guiding rule is simple: **money and history are never deleted; configuration is
archived.** A delete button that can remove a payment or a receipt would be a defect,
not a feature.

---

## 1. When a hard delete is genuinely safe

Only for records that hold no financial meaning, no history, and no children:

- a row created by mistake seconds ago, before anything references it
- a draft or pending record with no children and no money attached
- a duplicate configuration row that was never used

Even then, the record must be referenced by nothing. The database already enforces a
large share of this through `restrictOnDelete` foreign keys, which is a safety net
rather than a policy.

## 2. When archive is safer than delete

Archive is the right tool for **configuration that was valid and may be valid again**.
An archived record stays visible to history and reporting but is hidden from new
selections.

Archive is better than delete when:

- the record is referenced by anything, even historically
- the record has been used at least once
- re-creating it later should not be required
- an auditor might ask "did this exist?"

Archive needs a way to un-archive. A record archived by mistake must be recoverable.

## 3. Records that should probably never be hard deleted

These are money or history. They should be append-only or reversible by a compensating
action, never removed.

| Record | Table | Why delete is unsafe |
| --- | --- | --- |
| Payment | `payments` | It is the proof money was received. Deleting it destroys the audit trail and silently reopens the due item. |
| Receipt | `receipts` | A receipt is issued to a parent. It cannot be revoked. |
| Payment Allocation | `payment_allocations` | It records which child's balance a payment settled. Deleting it breaks the family-billing story. |
| Due Item with payments | `student_due_items` | It carries a payment history. Deleting it erases settled balances. |
| Due Item Discount snapshot | `due_item_discounts` | It is the historical reason a due item was reduced. |
| Audit Log | `audit_logs` | Deleting an audit log defeats the purpose of having one. Append-only by design. |
| Confirmed promotion batch | `promotion_batches` | It created real target-year enrollments. Deleting it does not undo them. |
| Applied promotion item | `promotion_batch_items` | It records that a student was promoted or graduated. |
| Source enrollment | `enrollments` | It is the historical year-specific placement. `placeIn()` appends history for exactly this reason. |
| Enrollment placement | `enrollment_placements` | It is an immutable record of mid-year movement. |
| Guardian-Student link | `guardian_student` | It is the privacy boundary. Removing it silently changes who can see a child, so it needs an audit entry. |
| Discount | `discounts` | It affects future generation. Withdrawal should be a status or end date, not a delete. |
| Fee Subscription | `student_fee_subscriptions` | Same as discount. |
| Event Due Item | `event_due_items` | Immutable link between an event and a generated due item. |

**Correction is not deletion.** A mistaken payment should be handled by a refund or
reversal that records what happened, not by removing the original row. That capability
does not exist yet and is tracked as Phase 10D-7.

## 4. Records that may support archive later

These are configuration. Archive is plausible; delete is still usually wrong because
history may already reference them.

| Record | Table | Archive value | Delete verdict | Blocker |
| --- | --- | --- | --- | --- |
| Academic Year | `academic_years` | High. Old years should disappear from year pickers. | Needs Decision. Enrollments, fees, dues, and promotions all reference it | 10D-1 |
| Term | `terms` | High | Needs Decision. Fees reference terms | 10D-1 |
| Grade | `grades` | Medium | Needs Decision. `sequence_order` also drives promotion | 10D-1 |
| Section | `sections` | Medium | Needs Decision. Enrollments and event charges reference it | 10D-1 |
| Fee Category | `fee_categories` | High. Retired categories should leave pickers. | Needs Decision. Structures and due items reference it | 10D-4 |
| Fee Structure | `fee_structures` | High | **Not Recommended.** Versioned by academic year; it is the price a family was quoted | 10D-5 |
| Event | `events` | High | Needs Decision. Generated dues reference it | 10D-4 |
| Event Charge | `event_charges` | Low | Needs Decision. Already produced due items | 10D-5 |
| Subject | `subjects` | Medium | Needs Decision. Teacher assignments reference it | 10D-2 |
| Discount | `discounts` | High, as "active/inactive" rather than archived | Needs Decision | 10D-3 |
| Fee Subscription | `student_fee_subscriptions` | High, as "active/inactive" | Needs Decision | 10D-3 |
| Payment Reminder | `payment_reminders` | Already has a `cancelled` status | **Not Recommended** to delete | 10D-4 |
| Promotion Batch (draft only) | `promotion_batches` | Already has a `discarded` status | Needs Decision for confirmed batches | 10D-4 |
| User | `users` | Medium, as "inactive" | Needs Decision. Audit logs reference the actor | 10D-2 |
| Role | `roles` | Low | **Not Recommended.** Deleting a role silently strips permissions | 10D-2 |

## 5. What needs business approval before anything is built

### Required before implementing archive

1. **What does archived mean per module?** Hidden from new selections only, or hidden
   from reports as well? The two answers produce very different queries.
2. **Does archive cascade?** If a year is archived, must its terms, structures, and
   enrollments also become read-only? This needs a rule.
3. **Can a record be used and then archived?** A section with enrollments, or a fee
   category with structures, probably must be blocked rather than archived silently.
4. **Who may archive?** Admin only today. If Accountant ever gains pages, does archive
   follow?
5. **Is un-archive required?** Recommended. A mis-archived record must be recoverable.
6. **Should archive be audited?** Recommended for discounts, subscriptions, and
   guardian links, because each changes financial or privacy behaviour.

### Required before implementing any delete

1. Whether a hard delete is permitted at all for that record, and if so, under what
   guard.
2. What audit entry a delete writes. Deleting something should leave a trace.
3. Whether confirmation is required and what it says.

### Required before implementing payment correction

This is the most important open question and has no draft rule yet.

1. Is a refund a new negative payment, a reversal entry, or a state on the payment?
2. What is the maximum refundable amount? The full payment amount, or only the
   unallocated remainder?
3. What happens to due items the original payment settled? Do they reopen to unpaid, or
   does the refund create an offsetting balance?
4. Does a refund require a receipt, and is one issued to the parent?
5. Does anyone approve a refund? Accountant, Admin, or both?
6. Is a refund itself audited, and against which action constant?
7. Can a receipt be cancelled, and does cancelling one require a refund?

## 6. What must not be built

To keep the audit honest, these should stay absent unless a decision explicitly
reverses them:

- a delete button on payments, receipts, allocations, or audit logs
- a delete button on a due item that has any payment allocation
- a delete button on a confirmed promotion batch, or one that removes an applied item
- a delete button on source enrollments or enrollment placements
- a bulk delete anywhere
- a delete that silently also removes dependent rows, which would bypass `restrictOnDelete`

## 7. Current state

No delete route exists. No archive column exists on any table. No restore workflow
exists. The only irreversible-looking states already present are status values the
schema defines but no UI can reach: `discarded` on a promotion batch and `cancelled` on
a reminder and on a payment reminder.

Adding the ability to reach those statuses is the cheapest, lowest-risk improvement
available, and it is queued as Phase 10D-4.