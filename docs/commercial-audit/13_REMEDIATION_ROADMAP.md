# Remediation Roadmap

## Wave 1 — P0/P1 security and blockers

* **CA-01:** active-account middleware/session invalidation; acceptance: deactivated user’s existing GET/POST is denied.
* **CA-18:** email change clears verification; acceptance: new address cannot use verified routes until signed verification.
* **CA-05:** propagate authenticated actor into family action; acceptance: immutable audit row has actor.
* **CA-14 (resolved 2026-10-25):** first-run active-year setup now creates the singleton only after an Admin selects a validated available year; dependent new-operation routes guide the Admin to setup.
* **CA-22:** upgrade vulnerable JS tree; acceptance: reviewed `npm audit` clean or formally risk-accepted with exact advisory scope.

**Dependencies:** no business-policy choice required. **Risk:** authentication/navigation regression; add focused feature tests plus full suite.

## Wave 2 — financial and workflow integrity

* **CA-02:** receipt child snapshots/rendering/PDF.
* **CA-03:** active-only teacher roster/detail.
* **CA-06:** retain-grade invariant.
* **CA-07:** subscription effective date predicates.
* **CA-21:** safe exception boundary and log correlation.

**Acceptance:** test each reported reproduction inverted; run payment/promotion/teacher tests. **Risk:** historical snapshot compatibility requires additive handling, not rewriting receipts.

## Wave 3 — notification reliability

* **CA-04:** prohibit sync production queue, make queue health operational, test queued path; decide actual external delivery policy.
* Fix M13 combined delivery behavior only after product approval: current job intentionally rejects consolidated/multi-item reminders.

## Wave 4 — MySQL/performance/operations

Run migrations and concurrency scenarios on managed MySQL; review indexes with representative data; backup encryption/restore drill; worker/scheduler/failed-job supervision; monitoring and deployment rollback rehearsal.

## Wave 5 — accessibility/UI

Real-browser WCAG pass: modal focus trap, keyboard navigation, mobile tables/sidebar, labels/errors/toast behavior, print/PDF. Use the acceptance checklist.

## Wave 6 — commercial UAT

School staff execute academic setup, family registration, due generation, payment, reversal, promotion, Guardian privacy and accountant restriction journeys with staging data; sign off and document unresolved policy decisions.

## Explicit approval dependencies

Do not implement default payment allocation, sibling discount, registration-fee classification, recurring billing calendar, promotion reversal window, external email delivery, grade-subject history, or teacher-assignment retention without product approval.
