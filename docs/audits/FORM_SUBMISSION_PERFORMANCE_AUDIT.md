# Form Submission Performance Audit

**Date:** 2026-10-23
**Scope:** Shared non-AJAX form behavior, Event creation, academic archive/restore, server-side feedback, and representative mutation workflows.

## Executive summary

The slow/stuck behavior was investigated across the shared JavaScript, Blade layout, mutation controllers/actions, local MySQL database activity, logs, and feature tests.

Two distinct findings were confirmed:

1. **The shared confirmation/loading script was defective.** For every form with both `data-confirm` and `data-loading`, it prevented the original submission, immediately disabled the button, and never submitted the form after the user confirmed. The confirmation overlay also remained hidden. This directly affected confirmed archive, restore, generate, approve, discard, and similar actions.
2. **Event creation itself is not a slow database operation.** On the configured local MySQL database, a warm Event creation request completed in 30.47 ms, issued nine queries, and spent 4.00 ms in database queries. The apparent delay was dominated by development cold-start rendering after the redirect, not Event creation logic or a slow database query.

## Investigation method

### Shared frontend behavior

Searched the project for loading text, submit handlers, `preventDefault`, fetch/AJAX, loading attributes, flash messages, and submit controls.

Findings:

- The only shared submit interceptor is `public/js/admin-ui.js`.
- There are no `fetch`, `XMLHttpRequest`, or other AJAX form-submit handlers.
- Write forms use normal browser form submission and Laravel redirects.
-  Confirmed actions use both `data-confirm` and `data-loading` throughout the application.
- The shared layout includes the script once, after the rendered page.
- Server success/error flashes had been rendered as `hidden` elements, making them dependent on JavaScript to become visible as toasts.

### Backend behavior

Inspected:

- `EventController::store()` and `CreateEvent`.
- `TermController::archive()` / `restore()` and `SetAcademicRecordArchived`.
- Audit logging, transactions, queued jobs, mail calls, middleware, session configuration, and query patterns.

Confirmed implementation details:

- Event creation validates the active academic year, creates exactly one `events` row in a short transaction, and does not generate dues, send mail, dispatch a job, or write an audit entry.
- Term archive updates the one `terms.is_archived` flag and writes one audit record. It has no cascade, external request, queued job, or mail operation.
- No observers are registered for either workflow.
- Database-backed sessions, cache, and queue are configured locally. These paths do not synchronously run queued jobs.
- Recent logs did not show slow-query, lock-wait, deadlock, retry-loop, or Event/Term exception evidence. Historical local database-connection failures were present and are unrelated to these successful measured requests.

## Measured timings

Measurements used the configured local MySQL `skooly` database and a rollback-only synthetic test fixture. No existing demo or user record was changed. Framework and views were warmed before the steady-state measurement. Query time is Laravel's reported cumulative database execution time.

| Workflow | HTTP result | Queries | DB time | End-to-end test request time | Result |
| --- | --- | ---: | ---: | ---: | --- |
| Event create `POST /events` | 302 to Event detail | 9 | 4.00 ms | 30.47 ms | Event persisted; `Event created.` flashed |
| Event detail redirect target `GET /events/{event}` | 200 | 16 | 8.51 ms | 27.27 ms | Success feedback rendered |
| Term archive `POST /terms/{term}/archive` | 302 to Term detail | 5 | 2.90 ms | 8.53 ms | Archive persisted; audit row and flash created |
| Term redirect target `GET /terms/{term}` | 200 | not separately sampled | — | 11.87 ms | Success feedback rendered |

### Cold-development observation

After explicitly clearing compiled Blade views, the first `GET /events/{event}` took 3.17 seconds while only 19.36 ms was spent in database queries. The first process run also showed an 8.11 second Event POST that was not reproducible after warm-up. Subsequent requests were consistently fast.

This identifies local development cold rendering (Blade compilation/process warm-up, with Xdebug enabled during the first probe) as the source of the measured one-off delay. It is not a slow Event insert, Term archive query, lock wait, or N+1 query. Production deployments should use normal config, route, and view caching and should not run Xdebug.

## Confirmed root causes

### Event creation

- **Backend root cause:** none found. The Event action is a one-row transaction and is fast under measurement.
- **Perceived delay:** the normal redirect destination could be slow only on a cold local development process/view cache. This was outside the Event write operation.
- **Missing feedback cause:** success flash markup was hidden, so success visibility relied entirely on JavaScript. A script failure, stale asset, or interrupted navigation left no visible server-rendered message.

### Term archive and other confirmed actions

The exact frontend defect was in `public/js/admin-ui.js`:

1. The confirmation handler called `preventDefault()`.
2. The independent loading handler still ran during that prevented submit and changed the button to `Processing` and disabled it.
3. `buildConfirm()` never changed `host.hidden` to `false`, so the dialog could not be seen.
4. The confirmation button called only `setSubmitting()`; it never called `requestSubmit()` or otherwise submitted the form.

The form therefore remained on the page with a disabled `Processing` control. The same defect applied system-wide to forms carrying both attributes.

## Changes made

### Shared loading and confirmation behavior

`public/js/admin-ui.js` now:

- Uses one coordinated submit handler rather than competing confirmation and loading listeners.
- Leaves a confirmed form enabled while the confirmation dialog is displayed.
- Displays the confirmation overlay.
- Resubmits a confirmed form through `requestSubmit()`, preserving standard browser submission, keyboard submission, Laravel redirects, and native client validation.
- Marks the confirmation as one-shot only while that second submit event runs, preventing recursion without skipping future confirmation after validation failure.
- Disables all submit controls for the active form only when the request is genuinely proceeding.
- Supports submit buttons associated with a form through `form="…"`.
- Sets `aria-busy` and announces `Submitting. Please wait.` through a polite live region.
- Restores original controls and labels on `pageshow`, including a back/forward-cache restoration.
- Restores controls after 45 seconds without navigation and gives a safe error message telling the user to check the record before retrying. This avoids asserting that the mutation did not happen and avoids exposing backend details.
- Keeps normal browser navigation and does not introduce AJAX.

### Feedback behavior

`resources/views/partials/flash.blade.php` now renders visible, accessible server-side alerts for `status` and `error` flashes.

- With JavaScript enabled, the shared script converts those alerts into dismissible toasts.
- With JavaScript unavailable, blocked, stale, or failed, the alert remains visible in the page.
- Success alerts use a polite status announcement; errors use an alert role.
- Existing Laravel validation summaries remain server-rendered and continue to preserve old input.

## Affected modules identified

All forms using both `data-confirm` and `data-loading` were affected by the confirmed handler defect, including:

- Academic Year, Term, Grade, and Section archive/restore.
- Recurring dues, event dues, and payment reminder generation.
- Payment recording and payment-reversal approval.
- Promotion confirmation and draft discard.
- User updates that require confirmation.
- Teaching-assignment corrections.
- Guardian/student unlinking, discount deactivation, subscription ending, reminder cancellation, and subject deletion.

Ordinary create/update forms with only `data-loading`, including Event creation, submitted normally. They still benefit from recovery on restored/cancelled navigation and from robust server-rendered feedback.

## Regression coverage

Added `tests/Feature/FormSubmissionFeedbackTest.php` covering:

- Visible and accessible success/error server flashes.
- Event create redirect, persisted data, and success feedback.
- Event validation failure, validation summary, and retained input.
- Unauthorized Event creation with no database change.
- Event update with no duplicate record.
- Term archive/restore redirects, flashes, audit entries, state transitions, and unauthorized failure paths.
- Presence of the archive form's `data-confirm` and `data-loading` markers.

The shared JavaScript behavior was additionally exercised with a local DOM harness because no browser automation package is installed in this repository. It verified before/after behavior:

| Scenario | Before | After |
| --- | --- | --- |
| Confirmed submit initially opens visible dialog | No; dialog remained hidden | Yes |
| Button disabled before confirmation | Yes | No |
| Confirm action performs standard submit | No | Yes |
| Back/forward-cache `pageshow` restores control | No | Yes |
| Ordinary loading form submits and disables while navigating | Yes | Yes |

## Verification

Completed successfully:

```text
php artisan test tests/Feature/FormSubmissionFeedbackTest.php --compact
8 passed, 51 assertions

php artisan test tests/Feature/WebEventManagementTest.php tests/Feature/WebAcademicSetupTest.php tests/Feature/WebUiUxTest.php tests/Feature/WebCommercialUiTest.php tests/Feature/WebAdminUsabilityTest.php tests/Feature/WebManualQaRegressionTest.php --compact
82 passed, 539 assertions

php artisan test --compact
618 passed, 3135 assertions

php -d xdebug.mode=off vendor/bin/phpstan analyse --memory-limit=2G --no-progress --debug
0 errors

php vendor/bin/pint --test
passed

npm run build
passed (existing optional Fontaine font-fallback warning only)

git diff --check
passed
```

Also completed:

```text
php vendor/bin/pint --dirty --format agent
passed

node --check public/js/admin-ui.js
passed
```

## Browser verification and limitations

No real browser automation tool (Playwright or Puppeteer) is installed in this environment, so the exact rendered-browser interaction was not automated. The JavaScript was syntax-checked and exercised with a local DOM harness, and server behavior is covered by feature tests. A manual browser check remains recommended after deployment, especially against the target web server and browser cache policy.

The performance timings are local-development measurements. They do not substitute for production APM or database slow-query monitoring under production data volume and concurrency. No index was added because the measured Event and Term write paths do not show an index-related bottleneck.

## Files changed

- `public/js/admin-ui.js`
- `resources/views/partials/flash.blade.php`
- `tests/Feature/FormSubmissionFeedbackTest.php`
- `docs/audits/FORM_SUBMISSION_PERFORMANCE_AUDIT.md`
