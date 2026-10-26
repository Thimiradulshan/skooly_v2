# Database and Financial Integrity Audit

## Positive evidence

* Financial payment writes lock due rows and run in one transaction (`Payment.php:79-145`).
* Payment allocation amount must equal payment amount and must not exceed current balance (`Payment.php:64-77`, `101-115`).
* Due item generation key is unique and deterministic (`GenerateRecurringDueItems.php:57-66`); reminders and receipts have uniqueness constraints.
* Historical payment, receipt, allocation and due-item foreign keys are restrictive; money check constraints are present in the later migration set.
* Promotion confirmation is atomic and does not modify source enrollments (`ConfirmPromotionBatch.php:30-118`).

## Confirmed integrity risks

| ID | Risk | Evidence |
| --- | --- | --- |
| CA-02 | Receipt cannot prove per-child settlement in combined household payments | `Payment.php:122-130`, `receipts/show.blade.php:58-73`, characterization test |
| CA-06 | Retain action can create a target enrollment in another grade | `UpdatePromotionBatchItem.php:48-58`; characterization test |
| CA-07 | Future-dated opt-in subscription is charged before its start | `GenerateRecurringDueItems.php:106-114`; characterization test |

## Decision dependencies

Do not invent allocation default, sibling discount, registration-fee classification, recurring billing calendar, promotion reversal window, receipt numbering policy, or refund policy. The existing manual-allocation/reversal model protects current records but does not satisfy every SRS automatic workflow.

## Not verified

No live MySQL schema introspection was possible because the local configured server refused connection. Migration status and a MySQL concurrency exercise are therefore not verified. SQLite test evidence is not a substitute for MySQL isolation/load behavior.
