# Skooly Active Context

Use this file before broad project reinspection.

## Current Status
- Phase 1 Academic Foundation: complete.
- Phase 2 Users, Roles & Teachers: complete after final MySQL verification.
- Phase 3 Families & Guardians: complete and verified; pending commit.
- MySQL fixed by removing obsolete MySQL 8.4 settings:
  - innodb_file_format=Barracuda
  - innodb_large_prefix=ON

## Database
- DB_CONNECTION=mysql
- DB_DATABASE=skooly
- MySQL version verified: 8.4.7
- Laravel config/database.php uses InnoDB for MySQL.

## Architecture Rules
- Family-based billing.
- Family is the household registration and billing unit, with a unique family_code.
- A Family has many Guardians; each Guardian belongs to one Family.
- A Family with Guardians cannot be deleted.
- Combined billing defaults to enabled but does not grant Guardian access to students.
- Users may have multiple roles.
- No Spatie permissions yet.
- Guardian access must be explicit through guardian_student later.
- Student grade/section history must use enrollments/placements, not mutable student fields.
- Financial workflows must use DB transactions later.
- Promotion must be draft/confirm, atomic, and must not overwrite historical enrollments.

## Phase 3 Deferred Work
- Students and guardian_student.
- Guardian student-visibility authorization.
- Duplicate-family detection beyond unique family_code.
- Payment allocation and combined-billing behavior.

## Verification Command Order
php artisan migrate:fresh
php artisan test
php vendor/bin/phpstan analyse
php vendor/bin/pint --test
composer audit
git diff --check
git status

## Latest Verification
- 2026-09-29: migrate:fresh passed; 20 tests / 45 assertions passed; PHPStan and Pint passed; Composer audit found no advisories; git diff --check passed.
