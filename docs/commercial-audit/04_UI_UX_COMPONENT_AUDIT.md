# UI/UX and Accessibility Audit

## Static findings

* The shared layout uses named routes, role-conditioned navigation, CSRF forms, escaped Blade interpolation, responsive viewport metadata, and local assets (`resources/views/layouts/app.blade.php:1-190`).
* `admin-ui.js` provides confirmation, busy state, flash-to-toast enhancement, timeout recovery after 45 seconds, and a `pageshow` restore (`public/js/admin-ui.js:269-383`). Server flashes remain visible when JS fails.
* 76 forms, 59 `data-loading` markers, and 35 confirmation markers were found. Mutating financial/archive/promotion actions reviewed use CSRF and loading; GET and auth forms explain much of the difference.
* Search controls generally preserve queries through `withQueryString()`; representative examples are `FamilyController.php:41-45`, `StudentController.php:59-62`, and guardian portal pagination.

## Confirmed / material UX findings

| ID | Finding | Evidence | Severity |
| --- | --- | --- | --- |
| CA-04 | User-visible “Processing” can remain until synchronous delivery/mail returns when queue is sync | queue route/controller + characterization evidence | P2 |
| CA-02 | A combined-payment receipt cannot identify the child for each line | receipt view + snapshot characterization | P1 |
| CA-21 | Promotion conflict exposes SQLSTATE rather than recoverable user feedback | controller error propagation, test | P2 |

## WCAG 2.2 AA static review limits

The confirmation dialog has `role=alertdialog`, `aria-modal`, labelled/described regions, Escape handling, and restores prior focus (`admin-ui.js:155-218`). It does **not** implement a Tab/Shift+Tab focus trap, and this was not exercised with a screen reader. Treat this as an accessibility risk requiring browser/assistive-technology verification, not a certified failure.

The responsive sidebar, tables, contrast, 320px/375px overflow, keyboard-only navigation, native validation announcements, toast timing, and print/PDF rendering were not browser tested. See checklist `14_BROWSER_ACCEPTANCE_CHECKLIST.md`.
