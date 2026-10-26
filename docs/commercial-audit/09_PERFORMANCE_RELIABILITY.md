# Performance and Reliability Audit

## Safe timing evidence (isolated SQLite test database)

From `CommercialAuditEvidenceTest` executed 2026-10-25:

| Operation | HTTP status | Request ms | Queries | Query ms |
| --- | ---: | ---: | ---: | ---: |
| Event create | 302 | 15.32 | 9 | 0.56 |
| Event update | 302 | 11.83 | 10 | 0.48 |
| Term archive | 302 | 11.25 | 5 | 0.30 |
| Term restore | 302 | 8.63 | 5 | 0.29 |

These are not production benchmarks: they exclude MySQL/network contention, queue workers, real mail, data volume, and browser navigation.

## Reported “Processing…” investigation

`public/js/admin-ui.js:269-305` disables controls and restores them after 45 seconds if navigation does not occur. Native POST redirects return server flash messages; the timing test confirms Event and Term mutations redirect with a status flash. Therefore the reported delayed state was **not reproduced** for those isolated test actions.

CA-04 is confirmed separately: reminder queue actions use `Bus::dispatch` (`PaymentReminderController.php:87`). If `QUEUE_CONNECTION=sync`, dispatch runs the job, including `Mail::send` (`DeliverPaymentReminderEmail.php:64`), in the request. The characterization test observed no queue push and immediate mail handling under its configuration. Production must forbid `sync`, run workers, and monitor latency/failures.

## Proposed commercial SLOs (not measured)

* p95 normal page/request under 500 ms at expected school-office load.
* p95 validated normal mutation under 1 s, excluding explicitly asynchronous reports/PDFs.
* Payment/reversal transaction failure rate 0; reconciliation/audit alerts for any invariant failure.
* Queue backlog/failed jobs visible within five minutes.

Run representative MySQL datasets and concurrent payment/reversal tests before setting contractual targets.
