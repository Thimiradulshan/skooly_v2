# Frontend/Backend Acceptance

Date: 2026-10-25

## Automated acceptance status

| Area | Evidence | Status |
|---|---|---|
| Four reported routes | Named URL, view, Admin, guest and unauthorized-role assertions | Passed |
| First-run active year | Empty settings view, validated create/update, singleton persisted | Passed |
| Similar active-year workflows | Middleware redirect tests for term, event, due generation, reminder and promotion creation | Passed |
| Admin navigation | Runtime links and shared shell tests | Passed |
| Accountant navigation/scope | Finance links visible; family/academic/event/promotion access denied | Passed |
| Teacher navigation/scope | Portal routes and section-scoped Student access tests | Passed in focused portal suite |
| Guardian navigation/privacy | Explicit-link portal tests; privacy 404 behavior retained | Passed in focused portal suite |
| Route resolution | 383 Blade references compared with runtime named routes | Passed; zero missing names |
| Loading/notifications | Dependency-free handler review; timeout recovery, BFCache restore, ARIA busy/status, flash toast fallback | Passed by source and feature coverage; browser interaction not verified |
| Build/static checks | Full Pest, PHPStan, Pint formatter, Composer audit, Vite build, JS syntax, and diff check | Passed; npm audit separately reports existing advisories |

## Manual browser acceptance steps

Run with MySQL available and the current project served on port 8000.

1. Sign in as Admin and confirm `/school-settings` renders with no settings row.
2. Create/select an active academic year and save. Confirm success feedback.
3. Open all four reported URLs and confirm the expected headings/forms.
4. Sign in as Accountant, Teacher, and Guardian. Confirm each sees only its categorized navigation.
5. Attempt each reported Admin URL as Accountant and Teacher; confirm 403.
6. As Teacher, attempt a Student URL outside assigned sections; confirm privacy-preserving 404.
7. As Guardian, attempt an unlinked Student URL; confirm privacy-preserving 404.
8. At 1440px, collapse/expand the desktop sidebar and reload to confirm preference persistence.
9. At 768px, 375px, and 320px, open the drawer with keyboard and touch; press Escape; confirm focus returns to the menu button.
10. Keyboard-tab through groups, links, forms, confirmation dialog, and notifications; confirm visible focus.
11. Submit invalid forms and confirm controls are not permanently disabled and field/global errors remain visible.
12. Create an Event and archive/restore a Term; confirm Processing clears after navigation and success feedback is visible.
13. Inspect wide financial tables at mobile widths; confirm horizontal scrolling occurs without page-wide overflow.
14. Test payment, reversal, promotion, reminder, and archive confirmations; cancel once and confirm no mutation occurred.

## Performance/form findings

- The shared script prevents duplicate submission only after a valid submit event.
- A 45-second no-navigation timeout restores controls and warns the user rather than leaving indefinite “Processing”.
- `pageshow` restores controls after back/forward-cache navigation.
- Existing audit timing coverage measured Event create/update and Term archive/restore at request level; no confirmed slow synchronous defect was found in this package.
- No financial query or business action was changed for cosmetic performance.

## Browser status

**NOT VERIFIED** — the local MySQL service refused connections and no browser automation integration was available.

## Final verification

- `php artisan test --compact`: **passed — 655 tests, 3,235 assertions** (10 warnings reported without details).
- `php -d xdebug.mode=off vendor/bin/phpstan analyse --memory-limit=2G --no-progress --debug`: **passed — 0 errors**.
- `php vendor/bin/pint --dirty --format agent`: **passed** after formatting one import-order/strict-types issue. The project instructions require this formatter command instead of Pint test mode.
- `composer audit`: **passed — no security vulnerability advisories**.
- `npm run build`: **passed**; only the optional Fontaine optimized-fallback warning was emitted.
- `node --check public/js/admin-ui.js`: **passed**.
- `git diff --check`: **passed**; Git emitted only a CRLF-to-LF normalization warning for `public/css/admin.css`.
- `npm audit` (additional security evidence): **failed — 4 development dependency advisories (2 high, 2 critical)** affecting `shell-quote`/`concurrently`, `source-map-js`, and Vite. Dependencies were not changed because this package did not authorize dependency upgrades; CA-22 remains open.

Request-level isolated test measurements:

- Event create: 16.93 ms, 11 queries / 0.86 ms query time.
- Event update: 12.18 ms, 10 queries / 0.47 ms query time.
- Term archive: 11.78 ms, 5 queries / 0.31 ms query time.
- Term restore: 8.12 ms, 5 queries / 0.29 ms query time.
