# Laravel Architecture Audit

## Strengths

* Controllers are mostly adapters around Form Requests/actions; financial and promotion workflows use transactions/locks (`Payment.php:79-145`, `ConfirmPromotionBatch.php:30-59`).
* Models have explicit relationships/casts; restrictive foreign keys and snapshot records protect history.
* Policies/middleware segregate financial, teacher, Guardian, Admin, and Superadmin access.
* `GenerateRecurringDueItems` uses deterministic generation keys and DB transaction.

## Essential architecture changes

| ID | Evidence | Classification |
| --- | --- | --- |
| CA-01 | `EnsureUserHasRole.php:16-26` only checks role, not active lifecycle | Essential security correction |
| CA-05 | `FamilyController.php:57-67` omits `$request->user()` although `CreateFamily::handle` accepts it | Essential audit integrity correction |
| CA-21 | `PromotionBatchController.php:147-151` exposes any caught exception message | Essential exception boundary correction |

## Recommended changes

* Define an application-level exception/message convention so expected domain errors are safe to show but `QueryException`/unexpected errors are logged and generalized.
* Create a single active-account enforcement middleware or guard policy, rather than relying on each portal controller to repeat `is_active`.
* Apply date-window semantics once to subscription eligibility; current `hasActiveSubscription()` checks only `is_active` (`GenerateRecurringDueItems.php:106-114`).
* Do not add generic service/repository layers: current action boundaries are useful and concrete.

## Observability gap

Application logging, failed-job persistence, external alerting, backup monitoring, and production health handling cannot be claimed verified. The `/up` health route is configured by Laravel (`bootstrap/app.php:13`) but no monitored deployment was available.
