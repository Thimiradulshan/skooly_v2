# Local Demo Setup

For local development and demonstration only. The demo seeder refuses to run when the
application environment is `production`.

Full Windows setup: [Installation Guide](INSTALLATION_GUIDE.md). Role and password
procedure: [Demo Accounts Setup](DEMO_ACCOUNTS_SETUP.md).

## 1. Set the local demo password

Demo accounts are never seeded with a committed password. Add a private value to `.env`:

```dotenv
SKOOLY_DEMO_PASSWORD=choose-a-private-local-password
```

Clear cached configuration after changing it:

```bash
php artisan config:clear
```

The seeder reads `config/demo.php`. Never commit, print, or reuse this value outside the
local demonstration database.

## 2. Prepare the database

For a disposable demo database only:

```bash
php artisan migrate:fresh --no-interaction
```

For an existing database, preserve the data:

```bash
php artisan migrate --no-interaction
```

## 3. Load demo data

```bash
php artisan db:seed --class=DemoDataSeeder --no-interaction
```

The seeder is repeatable. It creates deterministic records and reuses the existing
workflow actions, so repeated runs do not duplicate students, dues, payments, receipts,
or assignments. The base seeder creates roles and one Admin only and also requires the
local demo password:

```bash
php artisan db:seed --no-interaction
```

## 4. Build assets and run

```bash
npm install
npm run build
php artisan serve
```

Open <http://127.0.0.1:8000>.

## Demo accounts

All five accounts share the single password supplied in `SKOOLY_DEMO_PASSWORD`.

| Role       | Email                    | Login destination     |
| ---------- | ------------------------ | --------------------- |
| Superadmin | `superadmin@skooly.test` | `/users`              |
| Admin      | `admin@skooly.test`      | `/admin`              |
| Accountant | `accountant@skooly.test` | `/payments/collect`   |
| Teacher    | `teacher@skooly.test`    | `/teacher`            |
| Guardian   | `alice@skooly.test`      | `/guardian`           |

The Guardian account uses the existing verified User-to-Guardian link, not a Guardian
role. Alice is linked to Chloe and Liam, not to Mia, so Guardian privacy is visible.

## What the demo data contains

- Two academic years, two grades, two sections, and an active current year.
- Two families with three guardians and three students.
- Tuition, opt-in Transport, and Event Fees categories.
- Current-year fee structures, a scholarship discount, and a Transport subscription.
- Recurring dues, one partial payment with receipt `DEMO-REC-001`, and open balances.
- A `Demo Sports Day` event with a charge, participation, and generated dues.
- A qualified Teacher assigned to Grade 1 A.
- One verified, linked Guardian portal account with no reminder consent.
- One draft promotion batch, left unconfirmed.
- Internal payment reminder records. No external delivery is performed.

## Pages worth opening

| Page                     | URL                          |
| ------------------------ | ---------------------------- |
| Admin dashboard          | `/admin`                     |
| Families                 | `/families`                  |
| Student directory        | `/students`                  |
| Users                    | `/users`                     |
| Academic years           | `/academic-years`            |
| Teaching assignments     | `/teacher-assignments`       |
| Fee structures           | `/fee-structures`            |
| Recurring due generation | `/due-generation/recurring`  |
| Event due generation     | `/due-generation/events`     |
| Dues dashboard           | `/dues-dashboard`            |
| Payment history          | `/payments`                  |
| Receipts                 | `/receipts`                  |
| Reversals                | `/payment-reversals`         |
| Payment reminders        | `/payment-reminders`         |
| Events                   | `/events`                    |
| Promotion                | `/promotion-batches`         |
| Audit log                | `/audit-logs`                |
| Guardian portal          | `/guardian`                  |
| Teacher portal           | `/teacher`                   |

## Safety notes

- Demo data is for local and testing environments only and contains no real people.
- Do not commit `.env`, the credential sheet, database dumps, or private photos.
- The seeder sends no email, SMS, or WhatsApp messages.
- Change or remove demo accounts before deploying anywhere real.
- Production deployment is outside the local demonstration scope.
