# Local Demo Setup

This guide is for **local development and testing only**. The demo seeder refuses to
run when the application environment is `production`.

## Requirements

Nothing extra is needed. The project already ships the seeder and all demo data is
created through the existing models and workflow actions.

## 1. Reset the database

This drops every table and recreates the schema.

```bash
php artisan migrate:fresh
```

## 2. Load the demo data

```bash
php artisan db:seed --class=DemoDataSeeder
```

The seeder is safe to run more than once. Every record uses a deterministic key, and
due items, event dues, and reminders are produced by the existing actions, which
skip anything they already generated.

You can also seed the roles and a single Admin without the full demo content:

```bash
php artisan db:seed
```

## 3. Start the server

```bash
php artisan serve
```

The app is then available at <http://127.0.0.1:8000>.

## Demo login

| Role      | Email                     | Password   |
| --------- | ------------------------- | ---------- |
| Admin     | `admin@skooly.test`       | `password` |
| Accountant| `accountant@skooly.test`  | `password` |
| Teacher   | `teacher@skooly.test`     | `password` |

Only Admin can reach the admin pages. Accountant and Teacher accounts are included so
you can confirm that they are correctly denied access.

## What the demo data contains

- Two academic years, two grades, two sections, and an active school setting.
- Two families with three guardians and three students.
  - Chloe and Liam are active and enrolled in Grade 1.
  - Mia is `pending_registration` and enrolled in Grade 2.
  - Alice is linked to Chloe and Liam; Bob is linked only to Liam. Use this to
    check that a Guardian cannot see the child they are not linked to.
- Fee categories: Tuition (recurring), Transport (recurring, opt-in), Event Fees.
- Fee structures for the current academic year, a scholarship discount for Chloe, and
  an active Transport subscription for Chloe.
- Recurring due items, one partial payment with receipt `DEMO-REC-001`, and unpaid
  or partially paid balances for the dashboard.
- A `Demo Sports Day` event with a charge, one participation record, and its due items.
- One draft promotion batch, left unconfirmed so you can walk the confirm page.
- Internal payment reminder records. Nothing is sent anywhere.

## URLs worth clicking

| Page                                | URL                                      |
| ----------------------------------- | ---------------------------------------- |
| Admin dashboard                     | `/admin`                                 |
| Families                            | `/families`                              |
| Fee categories                      | `/fee-categories`                        |
| Fee structures                      | `/fee-structures`                        |
| Recurring due generation            | `/due-generation/recurring`              |
| Event due generation                | `/due-generation/events`                 |
| Dues dashboard                      | `/dues-dashboard`                        |
| Payment reminders                   | `/payment-reminders`                     |
| Events                              | `/events`                                |
| Promotion batches                   | `/promotion-batches`                     |

Payments are recorded from a family page: open a family and use **Record payment**.

## Safety notes

- Demo data is for local and testing environments only. The seeder exits early in
  production.
- Change the demo password before deploying anywhere real.
- The demo seeder does not send email, SMS, or WhatsApp. Reminders are internal
  outbox records only.
- Demo data contains no real personal information.
