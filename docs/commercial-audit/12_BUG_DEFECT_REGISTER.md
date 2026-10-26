# Confirmed Defect Register

| ID | Severity / effort | Title | Affected evidence | Status |
| --- | --- | --- | --- | --- |
| CA-01 | P0 / Small | Inactive staff session retains mutation access | `EnsureUserHasRole.php:16-26`; `CommercialAuditEvidenceTest.php:41-50` | Open |
| CA-02 | P1 / Medium | Combined receipt lacks child identity | `Payment.php:122-130`; `receipts/show.blade.php:58-73`; test :52-67 | Open |
| CA-03 | P1 / Small | Pending-registration student appears in teacher roster | `TeacherPortalController.php:79-90`; test :69-83 | Open |
| CA-05 | P1 / Small | Family-created audit log has no authenticated actor | `FamilyController.php:57-67`; `CreateFamily.php:21-45`; test :107-113 | Open |
| CA-06 | P1 / Small | Retain action permits grade change | `UpdatePromotionBatchItem.php:48-58`; test :115-131 | Open |
| CA-07 | P1 / Small | Future opt-in subscription is billed early | `GenerateRecurringDueItems.php:106-114`; test :133-146 | Open |
| CA-14 | P1 / Medium | No initial SchoolSetting creation path | `SchoolSettingController`; `ReportedRouteRepairTest` | Resolved 2026-10-25 |
| CA-18 | P1 / Small | Staff email change preserves old verification | `UserController.php:84-91`; test :183-194 | Open |
| CA-04 | P2 / Medium | Reminder dispatch can synchronously block UI | `PaymentReminderController.php:75-90`; job :60-65; test :85-105 | Open |
| CA-21 | P2 / Small | SQL diagnostics shown after promotion conflict | `PromotionBatchController.php:147-151`; test :196-213 | Open |
| CA-22 | P2 / Small | NPM dependency audit is not clean | `package-lock.json`; `npm audit` output | Open |

## Defect details

### CA-01 — inactive-session access (A01)
**Role/module:** Admin, all Admin mutations. **Reproduction:** authenticate Admin; set that user `is_active=false`; POST `grades.store`; grade persists. **Expected:** disable immediately denies/invalidates session. **Actual:** redirect and write succeed. **Impact:** unauthorized changes by deprovisioned staff. **Fix/test:** active-user middleware plus session invalidation; prove GET and POST are denied after deactivation.

### CA-02 — receipt lineage loss (M7.5/M12.1/M12.3)
**Reproduction:** allocate one family payment to two children; open receipt. **Expected:** each line identifies child. **Actual:** only description/amounts render; snapshot contains no student identity. **Impact:** reconciliation and dispute handling are impaired. **Fix/test:** snapshot immutable student identifier/name/admission number at payment time; render HTML/PDF; test historical name changes do not rewrite it.

### CA-03 — ineligible teacher roster (M6.5)
**Reproduction:** create pending-registration student/enrollment in a teacher’s assigned section; open `/teacher/students`. **Expected:** active students only. **Actual:** pending learner listed. **Impact:** academic workflow breaches SRS eligibility. **Fix/test:** constrain roster/detail to active status; test status matrix.

### CA-04 — synchronous delivery responsiveness
**Reproduction:** queue eligible reminder under sync behavior; job sends mail before request returns. **Expected:** button queues work without provider latency. **Actual:** controller dispatch can execute work inline. **Impact:** long Processing state/timeouts and duplicate-retry uncertainty. **Fix/test:** production config guard/health check for non-sync queue; queue fake and integration worker test.

### CA-05 — audit actor loss (A09/M14)
**Reproduction:** authenticated Admin POSTs family creation. **Expected:** audit `actor_user_id` is Admin. **Actual:** null. **Root cause:** controller omits action actor parameter. **Fix/test:** pass `$request->user()`; test actor and rollback semantics.

### CA-06 — invalid retain semantics (M15.3)
**Reproduction:** set draft item action `retain`, select next grade/section, confirm. **Expected:** retained student stays source grade in target year. **Actual:** different target grade is accepted. **Fix/test:** derive/require source grade for retain; test rejected mismatch.

### CA-07 — subscription date ignored (M10.2)
**Reproduction:** opt-in subscription starts 2027-01-01; generate October 2026 due. **Expected:** no due. **Actual:** one 100.00 due generated. **Fix/test:** date-window predicates; boundary-date tests.

### CA-14 — active-year bootstrap unavailable (M1.3)
**Resolved 2026-10-25:** the Admin can open School Settings with no singleton row, select an active non-archived Academic Year, and create row ID 1 through the validated update route. New year-scoped routes redirect to this setup path rather than returning a misleading 404. `ReportedRouteRepairTest` covers rendering, setup persistence, dependent-route redirects, and unchanged role denial.

### CA-18 — stale email verification (A07)
**Reproduction:** change verified staff email via Admin. **Expected:** verification reset for new address. **Actual:** timestamp unchanged. **Fix/test:** clear timestamp and require new verification; test protected routes blocked until verified.

### CA-21 — database error leakage (A10)
**Reproduction:** introduce target-year enrollment conflict then confirm promotion. **Expected:** generic conflict error. **Actual:** flash includes `SQLSTATE`. **Fix/test:** catch expected persistence conflict safely/log unexpected exception; assert no SQLSTATE or table names response-side.

### CA-22 — vulnerable frontend dependencies (A03)
**Evidence:** npm advisory output identifies shell-quote/concurrently and Vite/source-map-js paths. **Fix/test:** reviewed dependency upgrade and lockfile change, `npm audit`, build, smoke test.
