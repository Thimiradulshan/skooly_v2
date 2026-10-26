# Skooly Commercial Readiness Audit — Executive Summary

**Audit date:** 2026-10-25  
**Scope:** Current working tree, Laravel 13 / PHP 8.4 / MySQL design, Blade, Vite.  
**Method:** static source review, route and UI trace ledger, Pest characterization evidence, safe quality gates. No production data was accessed and no application behavior was changed.

## Decision

**Not commercially deployable for a first school today.** The application is a well-tested Stage 1 foundation with broad Admin workflows, but confirmed authorization, financial-record, academic-state, audit, and dependency risks must be resolved before real student or payment data is accepted.

It is suitable for **local demonstration and controlled staging/UAT only**, subject to a non-production mail/queue configuration. It is not pilot-ready until the P0/P1 defects in `12_BUG_DEFECT_REGISTER.md` are closed and a restore/operations rehearsal is evidenced.

## Measured inventory

| Measure | Result |
| --- | ---: |
| Stage 1 SRS modules reviewed | 15 / 15 |
| SRS functional requirements recomputed | 65 |
| Application routes statically audited | 185 (73 mutation; 112 GET) |
| Named routes | 184 |
| Backend actions | 41 |
| Web controllers | 40 |
| Form Requests | 66 |
| Eloquent models | 38 |
| Migrations | 53 |
| Blade templates/components | 128 |
| Feature-test files | 62; 629 executed tests |
| Browser E2E interactions actually verified | 0 (no browser session available) |

## SRS result (current source)

| Classification | Count |
| --- | ---: |
| Complete | 43 |
| Partially complete | 9 |
| Missing | 2 |
| Defective | 8 |
| Requires business decision | 3 |
| **Total** | **65** |

The complete per-requirement evidence is in `02_SRS_COMPLIANCE_MATRIX.md`. Do not interpret the 43 complete requirements as commercial readiness: the defective requirements include payment receipt itemization, enrollment eligibility, and automatic billing dates.

## Confirmed defects

| Priority | Count | Defect IDs |
| --- | ---: | --- |
| P0 | 1 | CA-01 |
| P1 | 6 open / 1 resolved | CA-02, CA-03, CA-05, CA-06, CA-07, CA-18; CA-14 resolved 2026-10-25 |
| P2 | 3 | CA-04, CA-21, CA-22 |
| P3 | 0 | — |

CA-01 is classified P0 because deactivating a staff account does not terminate its already-authenticated session; the deactivated account can still mutate records. See characterization test `tests/Feature/CommercialAuditEvidenceTest.php:41-50`.

## Security and operational conclusion

* Access controls, CSRF, escaped Blade output, role middleware, policies, database foreign keys, money constraints, transactions, throttling, verification, and TOTP are substantial positive controls.
* OWASP material findings: broken access control (CA-01), security logging/audit attribution failure (CA-05), authentication/identity integrity (CA-18), exceptional-condition information disclosure (CA-21), and supply-chain advisories (CA-22).
* `npm audit` reports **2 critical and 2 high** dependency advisories. `composer audit` reports no advisories.
* Configured live MySQL at `127.0.0.1:3306` refused the connection. `migrate:status` and `schedule:list` against that configured database are **NOT VERIFIED**; the test suite used its isolated test database successfully.

## What must be fixed before commercial deployment

1. Invalidate / deny inactive users on every authenticated request (CA-01).
2. Preserve child identity in receipt allocation snapshots and render it in HTML/PDF (CA-02).
3. Enforce active-only student eligibility in teacher rosters (CA-03).
4. Preserve initiating actor for family creation and review other action/controller actor propagation (CA-05).
5. Enforce retain-to-source-grade semantics (CA-06).
6. Honor opt-in subscription start/end dates during due generation (CA-07).
7. Clear verification / force reverification when a staff email changes (CA-18).
8. Update/remediate front-end dependency advisories and record a production lockfile review (CA-22).
9. Complete production prerequisites: HTTPS, secret management, backups and restore drill, database/queue workers, monitoring, deployment rollback rehearsal, real-browser UAT, and agreed policy decisions.

CA-14 was resolved on 2026-10-25 with an explicit first-run active-year setup flow and route-level guidance for dependent workflows.

See `13_REMEDIATION_ROADMAP.md` for ordered acceptance criteria.
