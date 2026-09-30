# Deployment Readiness

This project is a Laravel 13 application backed by MySQL. The backend is feature
complete through Phase 10C-2, but it is **not yet hardened for a public production
deployment**. Read the blockers section at the end before going live.

## Local verification

Run these before every commit and before every release.

```bash
php artisan migrate:fresh --no-interaction
php artisan test --compact
php vendor/bin/phpstan analyse
php vendor/bin/pint --test
composer audit
```

## Local demo data

```bash
php artisan migrate:fresh
php artisan db:seed --class=DemoDataSeeder
php artisan serve
```

See [local-demo.md](local-demo.md). Never run the demo seeder in production.

## Required production environment

| Key                     | Value                                | Notes                                        |
| ----------------------- | ------------------------------------ | -------------------------------------------- |
| `APP_ENV`               | `production`                         | Required. Guards the seeders.                |
| `APP_DEBUG`             | `false`                              | Required. Never ship `true`.                 |
| `APP_KEY`               | generated                            | `php artisan key:generate --show`            |
| `APP_URL`               | real https URL                       | No trailing slash.                           |
| `DB_CONNECTION`         | `mysql`                              | Matches the shipped migrations.             |
| `DB_HOST` / `DB_PORT`   | real server values                   | `3306` unless the provider says otherwise.   |
| `DB_DATABASE`           | real database name                   | Must already exist.                          |
| `DB_USERNAME`           | least-privilege user                 | Not `root`.                                  |
| `DB_PASSWORD`           | real secret                          | Never commit.                                |
| `SESSION_DRIVER`        | `database`                           | Already the default.                         |
| `SESSION_SECURE_COOKIE` | `true`                               | Required for HTTPS.                          |
| `CACHE_STORE`           | `database` or `redis`                | Must be shared across instances.             |
| `QUEUE_CONNECTION`      | `database`                           | No jobs are dispatched yet.                  |
| `LOG_LEVEL`             | `info` or higher                     | Avoid `debug` in production.                 |
| `MAIL_MAILER`           | real transport                       | Required before any mail is sent.            |

Set `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to real values. The
shipped `.env.example` contains placeholders only.

## Deployment steps

1. **Back up first.** Take a database backup before migrating. Migrations are not
   reversible once they have run against real data.

2. **Install dependencies without dev packages.**

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Configure the environment.** Copy `.env.example` to `.env`, then set every
   value in the table above. Never commit `.env`.

4. **Generate the application key** on the server.

   ```bash
   php artisan key:generate
   ```

5. **Run migrations.**

   ```bash
   php artisan migrate --force
   ```

6. **Create the first Admin securely.** Do not rely on any seeded account. Create
   an Admin user with a strong, unique password:

   ```bash
   php artisan tinker
   ```

   ```php
   $user = App\Models\User::create([
       'name' => 'Your Name',
       'email' => 'you@example.com',
       'password' => 'a-long-unique-passphrase',
   ]);

   $role = App\Models\Role::firstOrCreate(['name' => App\Models\Role::ADMIN]);
   $user->roles()->syncWithoutDetaching([$role->id]);
   ```

   Delete any demo account that exists on the server. `admin@skooly.test` must not
   exist in production.

7. **Never run the demo seeder in production.** `DemoDataSeeder` exits early when
   `APP_ENV=production`, but treat it as a development-only tool regardless.

8. **Cache configuration only after `.env` is final.**

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

   If you change `.env` afterwards, re-run `php artisan config:clear`.

9. **Fix storage permissions** so PHP can write logs and cache.

   ```bash
   chmod -R ug+rwX storage bootstrap/cache
   ```

10. **Serve over HTTPS.** Set `SESSION_SECURE_COOKIE=true`. Do not expose the app
    over plain HTTP, because the session cookie is the only thing protecting the
    admin area.

## Rollback basics

- Application code only: redeploy the previous commit and run
  `php artisan migrate:rollback --step=1` if the release included a migration.
- Do not roll back by dropping tables. Historical due items, payments, receipts, and
  audit logs must stay intact.
- If a migration must be reversed in production, restore from the backup taken in
  step 1 rather than editing the migration.

## Current deferred production blockers

These are known and unresolved. Resolve them before serving real student data.

1. **No password reset flow.** A forgotten Admin password currently requires server
   access to reset.
2. **No email verification.** Any user with a valid password is accepted.
3. **No login rate limiting.** Brute-force protection is not implemented.
4. **Default demo credentials.** `admin@skooly.test` / `password` exists only in
   local and testing seeds, but must be verified absent from any real database.
5. **Admin-only access.** Accountant and Teacher have no web access at all yet, so
   the Accountant and Teacher demo accounts cannot sign in to anything.
6. **Single-role authorization model.** Permissions are coarse: a user either has
   Admin or does not.
7. **Database-level money constraints are still open.** Balances are protected in
   application code, not by a database check constraint.
8. **No automated scheduling.** Recurring and event due generation is manual.
9. **No notification delivery.** Reminders are internal outbox records only.
10. **No audit retention or export.** Audit logs exist and are append-only, but there
    is no review UI, export, or retention policy.
