# Skooly Demo Accounts Setup

This guide explains how to provision and hand over the local demonstration accounts. It
contains **no passwords**. Credentials are supplied privately, never committed.

## Roles and accounts

| Role       | Email                    | Landing page after sign-in | Purpose                                      |
| ---------- | ------------------------ | -------------------------- | -------------------------------------------- |
| Superadmin | `superadmin@skooly.test` | `/users`                   | Account administration across all roles      |
| Admin      | `admin@skooly.test`      | `/admin`                   | Full academic, registration, and fee control |
| Accountant | `accountant@skooly.test` | `/payments/collect`        | Finance-only collection and reporting        |
| Teacher    | `teacher@skooly.test`    | `/teacher`                 | Read-only assigned classes and students      |
| Guardian   | `alice@skooly.test`      | `/guardian`                | Read-only portal for explicitly linked students |

Guardian access is **not** a fabricated role. `alice@skooly.test` is a verified `User`
record linked through `users.guardian_id` to the existing `Alice Demo` Guardian record.
The `Guardian` table has no authentication role of its own.

## One-time local password provisioning

The seeder refuses to invent or hard-code a password in a non-testing environment.

1. Choose a strong password known only to the demonstrator.
2. Set it in the local `.env` file:

   ```dotenv
   SKOOLY_DEMO_PASSWORD=your-private-local-demo-password
   ```

3. Clear cached configuration:

   ```bash
   php artisan config:clear
   ```

4. Apply migrations without resetting existing data:

   ```bash
   php artisan migrate --no-interaction
   ```

5. Seed or refresh the deterministic demo records:

   ```bash
   php artisan db:seed --class=DemoDataSeeder --no-interaction
   ```

All five demo accounts receive the same password on each run. Re-running the seeder
updates that password and does not duplicate Students, Guardians, dues, payments,
receipts, discounts, subscriptions, events, reminders, or assignments.

The base `DatabaseSeeder` also requires the same environment value when it is run
outside testing:

```bash
php artisan db:seed --no-interaction
```

## Private credential handoff

For a supervised local demonstration, write the current value and account list to:

```text
docs/private/DEMO_CREDENTIALS.local.md
```

That directory is excluded by `.gitignore`. Never paste its contents into a commit,
terminal transcript, screenshot, issue, or chat message. If the file is lost, reset the
password by editing `SKOOLY_DEMO_PASSWORD`, clearing configuration, and re-running the
demo seeder.

## Account state

Each seeder run guarantees:

- Active account (`is_active = true`).
- Verified email (`email_verified_at` is set).
- The correct fixed role, except the Guardian account, which is linked by
  `guardian_id`.
- No two-factor secret, so the first sign-in only asks for a password.
- No committed credential value.

## Login destinations

| Account    | Destination              | Why                                                |
| ---------- | ------------------------ | -------------------------------------------------- |
| Superadmin | Users index              | Account administration is its authorized workspace |
| Admin      | Operations dashboard     | Full school workspace                              |
| Accountant | Collect payment          | Finance-only entry point                           |
| Teacher    | Teacher dashboard        | Assigned active-year classes                       |
| Guardian   | Guardian dashboard       | Only explicitly linked students                    |

## What each account may and may not do

| Capability                          | Superadmin | Admin | Accountant | Teacher | Guardian |
| ----------------------------------- | :--------: | :---: | :--------: | :-----: | :------: |
| Manage all user accounts            | Yes        | No    | No         | No      | No       |
| Manage non-Admin staff accounts     | Yes        | Yes   | No         | No      | No       |
| Academic setup and registration     | No         | Yes   | No         | No      | No       |
| Families and students (staff view)  | No         | Yes   | No         | No      | No       |
| Fees, dues, payments, receipts      | No         | Yes   | Yes        | No      | No       |
| Request a payment reversal          | No         | No    | Yes        | No      | No       |
| Approve a payment reversal          | No         | Yes   | No         | No      | No       |
| Events and promotion                | No         | Yes   | No         | No      | No       |
| Audit log                           | No         | Yes   | No         | No      | No       |
| Assigned classes and students       | No         | No    | No         | Yes     | No       |
| Linked students and own dues        | No         | No    | No         | No      | Yes      |

A Superadmin deliberately has **no** general Admin workspace. Use the Admin account for
school operations and the Superadmin account only for account administration.

## Two-factor authentication

Two-factor authentication is per user and starts disabled on all demo accounts.

1. Sign in and open **Security** in the sidebar.
2. Copy the **Manual secret** or **Setup URI** into an authenticator app.
3. Enter the six-digit code and choose **Enable two-factor authentication**.
4. Store the eight one-time recovery codes shown once.
5. After that, sign-in asks for **Authentication code** at `/two-factor-challenge`.
6. Disabling requires both the account password and a current authenticator or recovery
   code.

The demo seeder does not reset an already-enabled second factor. Disable it from
**Security** before handing the account to someone else, or use a fresh local database.

## Verification

The demo accounts are covered by automated login, landing-page, role-denial, Guardian
privacy, and idempotency tests:

```bash
php artisan test --compact tests/Feature/DemoAccountAccessTest.php tests/Feature/DemoDataSeederTest.php
```

## Boundaries

- These accounts are for local demonstration only.
- Production authentication and password rules are unchanged.
- No email, SMS, or WhatsApp message is sent by the seeder.
- Do not add a Guardian role to solve portal access; use the existing verified
  `users.guardian_id` link.
