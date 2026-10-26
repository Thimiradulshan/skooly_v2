# 404 Route Repair Report

Date: 2026-10-25

## Outcome

The four reported URLs are registered in the current Laravel 13 runtime and now have regression coverage for URL generation, Admin rendering, guest denial, and Teacher/Accountant denial.

| URL | Route | Root cause | Correct result |
|---|---|---|---|
| `/fee-structures/create` | `fee-structures.create` | `ActiveAcademicYear::current()` called `SchoolSetting::firstOrFail()` when initial settings had not been created. | Admin sees `fee-structures.create` after configuration; otherwise receives an explained redirect to School Settings. |
| `/school-settings` | `school-settings.edit` | The setup screen itself called `SchoolSetting::firstOrFail()`, making it impossible to create the singleton through the UI. | Admin sees `school-settings.edit`, chooses an available year, and creates singleton row ID 1 through the validated update endpoint. |
| `/section-year-assignments/create` | `section-year-assignments.create` | Same missing `SchoolSetting`/active-year prerequisite. | Admin sees `section-year-assignments.create` after configuration; otherwise receives the setup redirect. |
| `/teacher-assignments/create` | `teacher-assignments.create` | Same missing `SchoolSetting`/active-year prerequisite. Teacher qualifications were not the 404 source; an empty qualified-teacher list remains a valid form state. | Admin sees `teacher-assignments.create` after configuration; otherwise receives the setup redirect. |

## Runtime evidence

- `php artisan route:list --except-vendor`: 185 application routes at initial diagnosis.
- Full runtime JSON inventory after changes: 189 routes, 187 named.
- Port 8000 process: PHP 8.4.15 built-in server, launched with this checkout's `vendor/laravel/framework/.../server.php`.
- No route cache file existed in `bootstrap/cache`; stale route cache was not identified and no cache was cleared.
- The route middleware remained `auth`, `verified`, and `role:Admin`; no authorization rule was removed or bypassed.
- The local MySQL listener was unavailable during live HTTP diagnosis (`SQLSTATE[HY000] [2002]`, connection refused). Runtime browser authentication against MySQL could therefore not be completed.

## Repair

- Added an explicit first-run School Settings state rather than silently selecting a year.
- `UpdateSchoolSettingRequest` continues to require an existing, non-archived Academic Year.
- Singleton ID 1 and the database exactly-one-row constraint remain authoritative.
- Added `active-academic-year` middleware to new year-scoped Admin create/store routes. This prevents similar prerequisite failures and guides the Admin to the authorized setup screen.
- Historical index, detail, report, and existing-edit routes were not restricted.

## Tests

- `tests/Feature/ReportedRouteRepairTest.php`: 26 dataset-expanded cases after the system-wide prerequisite additions.
- Covered correct view names, named URL resolution, first-run setup, missing-configuration redirects, guest redirects, and Teacher/Accountant 403 responses.
- The former CA-14 characterization test was inverted to prove the repaired behavior.
- Focused year-scoped workflow run: 87 tests, 324 assertions passed.
- Full application run: 655 tests, 3,235 assertions passed; PHPStan reported 0 errors.

## Files directly involved

- `app/Academic/ActiveAcademicYear.php`
- `app/Http/Middleware/EnsureActiveAcademicYearIsConfigured.php`
- `app/Http/Controllers/Web/SchoolSettingController.php`
- `app/Http/Controllers/Web/FeeStructureController.php`
- `app/Http/Controllers/Web/SectionYearAssignmentController.php`
- `app/Http/Controllers/Web/TeacherAssignmentController.php`
- `bootstrap/app.php`
- `routes/web.php`
- `resources/views/school-settings/edit.blade.php`
- `tests/Feature/ReportedRouteRepairTest.php`

## Remaining limitation

Live MySQL/browser confirmation is not claimed. Automated HTTP tests use the test database and passed; browser acceptance remains required when MySQL is running.
