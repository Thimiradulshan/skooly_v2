# Test Coverage and Quality Gates

## Executed checks

| Check | Result |
| --- | --- |
| `php artisan test --compact` | PASS — 629 tests, 3,171 assertions (102.3s) |
| `php -d xdebug.mode=off vendor/bin/phpstan analyse --memory-limit=2G --no-progress --debug` | PASS — 0 errors |
| `php vendor/bin/pint --test` | FAIL — only untracked `tests/Feature/CommercialAuditEvidenceTest.php` import/order formatting |
| `composer audit` | PASS — no advisories |
| `npm audit` | FAIL — 2 critical, 2 high advisories |
| `npm run build` | PASS — optional Fontaine warning |
| `php artisan migrate:status` | NOT VERIFIED — configured MySQL refused connection |
| `php artisan route:list` | PASS — 185 routes listed |
| `git diff --check` | PASS |

## Coverage assessment

The suite meaningfully tests feature workflows, authorization, financial transactions, promotions, guardian privacy, UI markup, and delivery actions. Passing tests do not negate the audit findings: the untracked characterization test intentionally demonstrates ten failing business/security contracts while still passing.

Missing or insufficient test categories:

1. Real browser tests for every role, redirects, loading recovery, focus, keyboard and responsive behavior.
2. MySQL-specific migration/query/concurrency tests under the deployment engine.
3. Inactive-session enforcement (currently a characterization test proves the opposite; CA-01).
4. Receipt child identity, retain-grade invariant, subscription effective dates, initial SchoolSetting bootstrap, email verification reset, and safe unexpected-exception response (CA-02/06/07/14/18/21).
5. Production queue worker and real provider behavior; no actual external email was sent.
6. Automated accessibility/contrast and mobile viewport assertions.
