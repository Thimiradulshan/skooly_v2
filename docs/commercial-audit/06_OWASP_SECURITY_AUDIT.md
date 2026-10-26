# OWASP Top 10:2025 Security Audit

| Category | Assessment | Evidence / finding |
| --- | --- | --- |
| A01 Broken Access Control | **Fail for deprovisioned sessions** | CA-01: middleware does not require active account. Guardian/teacher scopes otherwise have concrete checks. Relevant ASVS intent: V4 access control/session termination. |
| A02 Security Misconfiguration | Risk | Production config cannot be verified; `.env.example` is local/debug and documents secure-cookie change. Live MySQL unavailable. |
| A03 Software Supply Chain Failures | **Fail gate** | CA-22: `npm audit` reports 2 critical / 2 high advisories in `concurrently`, `shell-quote`, `vite`, `source-map-js`. Composer audit clean. |
| A04 Cryptographic Failures | Partial positive | Laravel hashing, encrypted TOTP secret/recovery-code casts (`User.php:113-120`); deployment key/TLS/backups not verified. |
| A05 Injection | No confirmed injection | Input validation and allow-listed sorting observed; `selectRaw` instances are static SQL. No unsafe raw Blade found. Manual dynamic query review only. |
| A06 Insecure Design | Material gaps | CA-06 retain semantics and CA-07 subscription date semantics remain; CA-14 bootstrap invariant was resolved 2026-10-25. Unresolved payment strategy remains decision-gated. |
| A07 Authentication Failures | **Fail** | CA-18 email change retains verification; CA-01 deactivation does not revoke active sessions. Login throttling, reset, verification, TOTP are positive controls. |
| A08 Software/Data Integrity Failures | Mixed | Transactions, locks, snapshots and DB constraints are strong; CA-02 snapshot omits required identity; CA-04 permits sync execution. |
| A09 Security Logging/Alerting Failures | **Fail** | CA-05: web family creation creates audit row with null actor. Audit views/exports exist; operational alerting not verified. |
| A10 Exceptional Conditions | **Fail** | CA-21 exposes database SQLSTATE in a user-visible validation error. |

## Confirmed security findings

| ID | Preconditions / impact / remediation |
| --- | --- |
| CA-01 | A staff user has an authenticated session, then is marked inactive. POST to an Admin mutation still succeeds. Impact: offboarded/disabled staff may alter data. Require `is_active` in an authenticated middleware and invalidate sessions on deactivation; add role and session regression tests. |
| CA-18 | Admin changes another staff email. Existing `email_verified_at` remains valid. Impact: verification no longer proves control of current email. Null verification timestamp, invalidate/reset verification workflow, notify appropriately, test. |
| CA-21 | Promotion confirmation hits an unexpected uniqueness conflict. Controller flashes exception message. Impact: schema/SQL disclosure. Log correlation ID server-side and return generic recoverable text; test no SQLSTATE text leaks. |
| CA-22 | Installation has audited NPM dependency advisories. Impact depends on dev/build server exposure, but supply chain release gate is not clean. Update via reviewed lockfile changes, rebuild and rerun audit. |

No penetration-test certification, external scan, production configuration inspection, or live attack was performed.
