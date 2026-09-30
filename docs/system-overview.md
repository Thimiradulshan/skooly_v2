# Skooly System Overview

## What Skooly is

Skooly is a private school management system. It handles the full financial and
administrative lifecycle of a school:

- registering families, guardians, and students
- setting up academic years, grades, and sections
- defining what can be charged and how much
- generating fee due items for students
- recording payments against those due items
- producing receipts
- running school events with per-grade charges
- promoting students between academic years
- keeping an audit trail of who did what

It is a **single-tenant** application for one school. There is no school-to-school
isolation.

## Who uses it

| Persona | Can they sign in today? | What they can do |
| --- | --- | --- |
| **Admin** | Yes | Everything. The only role with web access. |
| **Accountant** | Yes, but sees nothing | Denied every admin page. Finance permissions are built but not wired to the UI. |
| **Teacher** | Yes, but sees nothing | Denied every admin page. |
| **Guardian** | **No** | No login exists. A `Guardian` is only a contact record. |
| Parent/Student portal | No | Not built. |

Only Admin can actually use the application right now. This is deliberate: the other
roles exist in the data model, but their exact web permissions are an open decision.

## Current system status

**Stage 1 backend is feature complete. The UI is functional but plain.**

Every business rule in the SRS has been implemented and tested on the backend, and
there is a working web page for each one. What is missing is production hardening
(auth security features, real permissions) and real UI/UX design.

- 345 automated tests, 1,474 assertions, all passing
- PHPStan clean, Pint clean, `composer audit` clean
- No API, no mobile app, no external integrations

## Completed modules

| Area | Status |
| --- | --- |
| Academic Year, Term, School Settings | Complete |
| Grade, Section | Complete |
| User, Role, Teacher assignments | Complete |
| Family, Guardian, Guardian-Student link | Complete |
| Student, Enrollment, Placement history | Complete |
| Fee Category, Fee Structure, Subscription | Complete |
| Discount, Due Item Discount snapshot | Complete |
| Student Due Item generation (recurring + event) | Complete |
| Payment, Payment Allocation, Receipt | Complete (manual allocation) |
| Dues dashboard and reporting | Complete |
| Payment Reminder outbox | Complete (no sending) |
| Student Promotion | Complete (no reversal) |
| Authorization policies and privacy helpers | Complete |
| Audit Logs | Complete (no viewing UI) |
| Web admin pages for all of the above | Complete |

## Incomplete modules

| Area | What is missing |
| --- | --- |
| Authentication hardening | No password reset, email verification, 2FA, or login rate limiting. |
| Role-based web access | Accountant and Teacher exist but have no permissions wired. |
| Guardian portal | No Guardian login, no parent view. |
| Reminder delivery | Reminders are stored but nothing sends them. |
| Scheduling | No cron. Due generation is a manual button click. |
| Receipt numbering | The operator types the number; no generated sequence exists. |
| Receipt export | No PDF or print layout. |
| Promotion reversal | Deliberately not implemented; safety window is undecided. |
| Audit review | Logs are written but there is no screen to browse them. |
| UI/UX | Functional plain HTML. No design system, no responsive design. |

## What is demo-ready

Everything needed to show the system working end to end:

- Seed the database with a realistic school
- Sign in as Admin
- Register a family, guardians, and students
- Set up fees, generate dues, take a payment, print a receipt
- Create an event and generate its dues
- Generate reminders
- Run a promotion batch

Demo login: `admin@skooly.test` / `password`. See [local-demo.md](local-demo.md).

## What is not production-ready

1. **No login rate limiting.** Password guessing is unmitigated.
2. **Default demo password.** `admin@skooly.test` / `password` must not exist in production.
3. **No password recovery.** A locked-out Admin needs server access.
4. **Accountant and Teacher cannot use the app.** Finance workflows are unusable by
   anyone but an Admin.
5. **No Guardian access at all.** Parents cannot see balances or receipts.
6. **No scheduled jobs.** Nothing runs automatically.
7. **No notification delivery.** Reminders sit in an outbox forever.
8. **No money constraints at the database level.** Balances are protected in
   application code only.
9. **No real user acceptance testing.** The QA pass was done by the development team.
10. **Plain UI.** No design system, and tables are hard to read on small screens.

See [production-gap-register.md](production-gap-register.md) for the full list.

## Section walkthrough

### Foundation setup

Before anything else, the school needs academic years, grades, and sections. Two
academic years are needed to promote students. Sections belong to grades and are
persistent across years, so "Grade 1 Section A" is one record used by every year.

### Registration

A **Family** is the household and billing unit. A **Family** holds one or more
**Guardians** and one or more **Students**. A **Student** belongs to exactly one
Family and is linked to Guardians through explicit link rows. An **Enrollment** puts
a Student in a grade and section for one academic year.

Key point: guardians created with a family are **not** automatically linked to its
students. The link is made deliberately during registration.

### Fees

A **Fee Category** says what can be charged (for example Tuition) and whether it
recurs or is opt-in. A **Fee Structure** sets the amount for one category, one grade,
one academic year, one frequency. Because structures are versioned by year, changing
next year's price never affects what was already generated.

### Due generation

A **Due Item** is the actual payable amount for one student. It is created by
re-running generation for a cycle. Generation reads fee structures, applies the
student's discounts, respects opt-in subscriptions, and writes a snapshot of the
amount. Running it twice for the same cycle is safe.

### Payments

A **Payment** belongs to a Family and is the money received. It is split across one
or more **Allocations**, each pointing at a due item. Recording a payment updates
each due item's paid amount, balance, and status, then creates a **Receipt**. All of it
happens in one database transaction.

Allocation is entirely manual. There is no automatic split.

### Events

An **Event** has one or more per-grade **Charges**. A mandatory event charges every
enrolled student in those grades. An opt-in event charges only students with an
opted-in **Participation** row. Generating event dues creates ordinary due items and
links them back to the event.

### Promotion

A **Promotion Batch** is created as a draft from a source year, target year, and a set
of source sections. It lists the active students as **Items** with a proposed target
grade and section. Nothing happens until the batch is confirmed, which creates
target-year enrollments atomically. Promotion never creates fee items.

### Reminders

A **Payment Reminder** is an internal record noting that a guardian should be told
about outstanding due items. Combined-billing families get one consolidated record
per guardian; others get one per due item. Nothing is sent anywhere.

### Audit

Every significant write is recorded: student registration, family changes, discounts,
payments, allocations, promotion, and all generation runs. Logs are append-only and
live inside the same transaction as the change, so a failed operation writes nothing.

### Auth and security

Session login with a role middleware. Only Admin can reach the admin pages. Guardian
access is enforced in code through explicit link rows, never through family
membership.

### Demo data

`DemoDataSeeder` builds a small but complete school for local use. It refuses to run
in production and is safe to run repeatedly.
