# Skooly Production Gap Register

Every known gap between the current system and a production deployment. Severity is
about risk to real student and money data, not effort.

| Gap ID | Area | Current status | Why it matters | Severity | Suggested phase |
| --- | --- | --- | --- | --- | --- |
| GAP-01 | Deployment | No hosting provisioned or configured | Nothing is deployed. No server, domain, TLS, or process supervision exists yet. | Blocker | 10D-1 |
| GAP-02 | Deployment | No real Admin account created for production | The only Admin available comes from a demo seeder with a known password. | Blocker | 10D-1 |
| GAP-03 | Auth | Password reset via Laravel password broker | Reset-link requests have an opaque response for existing and unknown email addresses. | Resolved | Step 2 |
| GAP-04 | Auth | Email verification | Unverified users cannot access protected application routes; signed links verify ownership. | Resolved | Step 2 |
| GAP-05 | Auth | Login throttling | Five failed attempts per normalized email and IP are allowed per minute; successful login clears the limiter. | Resolved | Step 2 |
| GAP-06 | Auth | Two-factor authentication method undecided | A single stolen password gives full Admin access including money. | Medium | Decision required |
| GAP-07 | Roles | Accountant and Teacher have no web permissions | Finance staff cannot use the system at all. Every financial task needs an Admin. | High | 10C-5 |
| GAP-08 | Roles | Coarse single-role authorization | There is no per-resource permission model; either you are Admin or you see nothing. | Medium | 10C-5 |
| GAP-09 | Guardian | No Guardian login or Guardian-to-User link | Parents cannot see balances or receipts. No self-service exists. | High | 10C-7 |
| GAP-10 | Reminders | No sending provider (email, SMS, WhatsApp) | Reminders are written to an outbox and never delivered, so overdue fees are not chased. | High | 10C-6 |
| GAP-11 | Automation | No scheduler or cron | Recurring and event due generation is a manual click. Easy to forget, so fees go unbilled. | High | 10C-6 |
| GAP-12 | Finance | Receipt numbering rule unresolved | The operator types the number. Collisions are caught by the database rather than prevented, and numbers are inconsistent. | Medium | 10C-8 |
| GAP-13 | Finance | No receipt PDF or print export | Receipts can only be printed with browser defaults, which look unprofessional and are easy to mis-size. | Medium | 10C-8 |
| GAP-14 | Finance | No payment edit, refund, or reversal policy | Payments are immutable by design with no correction path. Real refunds and mistakes have no route. | High | 10C-9 |
| GAP-15 | Finance | Automatic payment allocation rule unresolved | Allocating a payment is fully manual. Large family balances require many entries. | Medium | 10C-9 |
| GAP-16 | Finance | Sibling discount rule unresolved | No sibling discount exists, and none is safe to guess. | Low | Deferred |
| GAP-17 | Finance | StudentDueItem database money constraints | MySQL CHECK constraint enforces non-negative amounts and `paid_amount + balance_amount = net_amount`. | Resolved | Step 2 |
| GAP-18 | Promotion | Reversal safety window unresolved | A confirmed promotion batch cannot be undone, so a mistake is only fixable by direct database work. | High | 10C-10 |
| GAP-19 | Audit | Audit log workflow | Admins can filter stored audit rows, download CSV/PDF exports, and logs are retained forever without archive or purge. | Resolved | Complete |
| GAP-20 | Audit | Audit retention policy | Forever retention is approved. Audit logs remain append-only with no archive, purge, or deletion route. | Resolved | Complete |
| GAP-21 | Operations | No backup or restore procedure rehearsed | Migration steps warn about backups, but no automated or tested backup exists. | Blocker | 10D-1 |
| GAP-22 | Testing | No real-user acceptance testing | QA was performed by the development team only. No school staff have used the system. | Blocker | 10D-2 |
| GAP-23 | UI/UX | No design system, sidebar, or responsive layout | Pages are plain HTML tables. Staff will make errors, especially on payment collection. | High | 10C-12 |
| GAP-24 | UI/UX | No list search, sort, or pagination anywhere | Finding a student or family means scrolling. Unusable at real school size. | High | 10C-12 |
| GAP-25 | UI/UX | No confirmation step for irreversible actions | Confirming a promotion batch is a single button press with no review. | Medium | 10C-12 |
| GAP-26 | UI/UX | No web screens for academic setup | Years, terms, grades, and sections require console access, blocking non-technical staff. | High | 10C-13 |
| GAP-27 | UI/UX | No audit, promotion-item edit, or subscription management screens | Some data can only be changed via the console. | Medium | 10C-12 |

## Severity summary

- **Blocker (4):** GAP-01 hosting, GAP-02 real Admin, GAP-21 backups, GAP-22 real-user testing
- **High (9):** GAP-07, GAP-09, GAP-10, GAP-11, GAP-14, GAP-18, GAP-23, GAP-24, GAP-26
- **Medium (8):** GAP-06, GAP-08, GAP-12, GAP-13, GAP-15, GAP-19, GAP-25, GAP-27
- **Low (3):** GAP-16, GAP-20

## Decision dependencies

Several gaps cannot start until a product decision is made. These are recorded in
`.ai/context/open-business-decisions.md` and must not be guessed:

| Decision | Blocks |
| --- | --- |
| Default payment allocation strategy | GAP-15, and the payment screen design |
| Sibling discount percentage and rule | GAP-16 |
| Promotion reversal safety window | GAP-18 |
| Duplicate-family detection, warn or block | Registration flow validation |
| Accountant and Teacher web permissions | GAP-07, GAP-08, and their dashboards |
| Receipt numbering rule | GAP-12, GAP-13 |
| Guardian login and User link | GAP-09 |
| Reminder channel and delivery | GAP-10 |
