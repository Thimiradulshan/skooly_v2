# Production Readiness

## Readiness classification

| Level | Decision |
| --- | --- |
| Local demo | Conditional yes — demo configuration/data only |
| Staging/UAT | Conditional yes after P0/P1 fixes and safe infrastructure |
| Pilot school | No |
| Commercial production | No |

## Release blockers

* All P0/P1 defects in `12_BUG_DEFECT_REGISTER.md`.
* NPM dependency advisories (CA-22).
* Verified production MySQL migrations/backups/restore exercise.
* HTTPS, `APP_DEBUG=false`, secure cookie, real `APP_KEY`, production secret handling, trusted proxy/host review.
* Queue worker supervision and scheduler configuration; production must not use synchronous reminder delivery.
* Failed job/application exception monitoring and alert routing.
* Mail provider, consent/legal privacy review, delivery/bounce policy, and no external-mail claim until provider verification.
* Role-specific browser UAT, including Guardian sibling privacy and Accountant finance boundaries.

## Configuration facts

`config/session.php:172` delegates secure cookies to environment; `.env.example:39-40` sets a local false example and warns production to override. Database sessions/cache/queue require the database service to be operational. The local MySQL service was unavailable during audit, so deployability is not proven.
