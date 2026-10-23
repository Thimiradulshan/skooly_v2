# Skooly Installation Guide (Windows / WAMP)

This guide installs the submitted Stage 1 Skooly application locally for supervisor
review. It uses synthetic demo data only. It does not describe production deployment.

## 1. Requirements

| Component | Requirement |
| --------- | ----------- |
| PHP       | 8.4 (project baseline; `composer.json` allows 8.3+) |
| Composer  | 2.x |
| Node.js   | 22 LTS recommended; Vite 8 needs a current Node release |
| npm       | 10 or newer |
| MySQL     | 8.0 or newer, `utf8mb4` |
| Git       | Any current version |
| Browser   | Current Chrome, Edge, or Firefox |

Required PHP extensions: `openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `dom`,
`xml`, `curl`, `zip`, and `tokenizer`. Dompdf uses `gd`, `dom`, and `mbstring`.

Check them with:

```powershell
php -v
php -m
composer --version
node --version
npm --version
mysql --version
git --version
```

### WAMP notes

1. Install WAMP with PHP 8.4 and MySQL 8.
2. Add the PHP directory to the Windows `PATH`, for example
   `C:\wamp64\bin\php\php8.4.x`.
3. Edit the active `php.ini` and remove the leading `;` from the required extensions.
4. Restart WAMP after changing `php.ini`.
5. Make sure the MySQL service is running before migrating.

## 2. Get the project

```powershell
git clone https://github.com/Thimiradulshan/skooly_v2.git
cd skooly_v2
```

If the repository is private, sign in to GitHub first or use the archive supplied by the
supervisor.

## 3. Create the local database

Start MySQL, then create an empty database. In phpMyAdmin or the MySQL console:

```sql
CREATE DATABASE skooly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Do not import a production database dump for the demonstration.

## 4. Configure the environment

```powershell
copy .env.example .env
php artisan key:generate
```

Edit `.env` and set the local database connection:

```dotenv
APP_NAME=Skooly
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skooly
DB_USERNAME=root
DB_PASSWORD=your-local-mysql-password
```

Set a private demo password. The seeder refuses to create demo accounts without it:

```dotenv
SKOOLY_DEMO_PASSWORD=choose-a-private-local-password
```

Never commit `.env` or share the value in a screenshot, issue, or chat message.

## 5. Install dependencies

```powershell
composer install
npm install
```

If Composer runs out of memory:

```powershell
$env:COMPOSER_MEMORY_LIMIT="-1"
composer install
```

## 6. Create the schema and demo data

```powershell
php artisan config:clear
php artisan migrate --no-interaction
php artisan db:seed --class=DemoDataSeeder --no-interaction
```

Do **not** use `migrate:fresh` on a database whose records you need to keep. On a
disposable demonstration database it is safe:

```powershell
php artisan migrate:fresh --no-interaction
php artisan db:seed --class=DemoDataSeeder --no-interaction
```

The seeder is idempotent. Re-running it refreshes the deterministic demo records without
duplicating them.

## 7. Build frontend assets

```powershell
npm run build
```

The application CSS is also served directly from `public/css`. A successful Vite build
prevents a missing-manifest error.

## 8. Start the application

```powershell
php artisan serve
```

Open the URL reported by Artisan, normally <http://127.0.0.1:8000>. The login page opens
at `/login`. See [Demo Accounts Setup](DEMO_ACCOUNTS_SETUP.md) for the five accounts.

## 9. Sign in to each account

| Role       | Email                    | First page            |
| ---------- | ------------------------ | --------------------- |
| Superadmin | `superadmin@skooly.test` | Users                 |
| Admin      | `admin@skooly.test`      | Operations dashboard  |
| Accountant | `accountant@skooly.test` | Collect payment       |
| Teacher    | `teacher@skooly.test`    | Teacher dashboard     |
| Guardian   | `alice@skooly.test`      | Guardian dashboard    |

Use the password supplied privately through `SKOOLY_DEMO_PASSWORD`. If a password is
lost, change it in `.env`, run `php artisan config:clear`, and re-run the demo seeder.

## 10. Verify the installation

```powershell
php artisan migrate:status
php artisan test --compact
php vendor/bin/pint --test
php -d xdebug.mode=off vendor/bin/phpstan analyse --memory-limit=2G --no-progress
composer audit
npm run build
git diff --check
```

The full suite covers authentication, role boundaries, Guardian privacy, fees, payments,
reversals, reminders, promotion, audit logs, exports, and UI workflows.

## Common errors

### `could not find driver`

`pdo_mysql` is disabled or the wrong `php.ini` is active. Enable it, restart the web
server, and run `php -m` to confirm.

### `SQLSTATE[HY000] [1045] Access denied`

The `DB_USERNAME` or `DB_PASSWORD` in `.env` is wrong, or MySQL is not running. Correct
the value and run `php artisan config:clear`.

### `Unknown database 'skooly'`

Create the database first, then run `php artisan migrate --no-interaction`.

### Demo seeder stops with `Set SKOOLY_DEMO_PASSWORD`

Add the variable to `.env`, run `php artisan config:clear`, and seed again. The seeder
must not use a committed default password.

### `ViteException: Unable to locate file in Vite manifest`

Run `npm install` and `npm run build`. For live development, run `npm run dev` instead.

### `419 Page Expired`

Confirm `APP_KEY` is set, clear configuration, and check that the browser allows cookies
for `localhost`.

### Port 8000 is already in use

```powershell
php artisan serve --port=8001
```

### `No active academic year is configured`

Run the demo seeder, or sign in as Admin and choose an active year in **School Settings**.
Most operational pages require one.

### Permission denied after sign-in

Use the account that owns the workflow. Accountants are finance-only; Teachers and
Guardians are read-only portals. See the role matrix in Demo Accounts Setup.

### Browser shows an old stylesheet

Run `npm run build` and perform a hard refresh (`Ctrl+F5`).

## Local-only safeguards

- `.env` is ignored and must remain untracked.
- `docs/private/` is ignored for credential handoff.
- No database dump, session data, or student photo is part of the repository.
- Production deployment, TLS, backups, monitoring, and real-user data are out of scope.
