# Skooly User Workflows

Practical steps for each administrative task: what it is for, where to click, what
must exist first, what gets written, and what the current limits are.

Throughout: sign in at `/login` as `admin@skooly.test` / `password`. Every page is
reachable from the navigation row at the top of the screen.

---

## 1. Admin login

**Purpose:** get into the admin area.

**Where:** `/login`.

**Requires:** an Admin user. Seed one with
`php artisan db:seed --class=DemoDataSeeder`.

**Writes:** nothing. A session is created and the session ID is regenerated.

**Limits:** no password reset, no email verification, no lockout after repeated
failures. There is no "forgot password" link.

**Note:** after signing in you land on the Families list, not the dashboard. Use the
`/admin` link or the Dashboard navigation entry to reach the dashboard.

---

## 2. Create the academic setup

**Purpose:** give the school years, grades, and sections before anything else.

**Where:** there is **no web page** for academic years, terms, grades, or sections
yet. They are created through `php artisan tinker` or a seeder.

**Requires:** nothing.

**Writes:** `academic_years`, `grades`, `sections`, optionally `terms` and
`school_settings`.

**Limits:** this is a real gap. The demo seeder supplies these. Add a web screen for
this before inviting non-technical staff.

**Tip:** you need at least two academic years to use promotion.

---

## 3. Register a family

**Purpose:** create the household that is billed for its students.

**Where:** Families → **Create family** (`/families/create`).

**Requires:** nothing.

**Writes:** one `Family` row, plus one `Guardian` row per guardian entered.

**Result:** the family appears at `/families` and can be opened.

**Limits:**
- One guardian set can be entered on this form. Add more later, which is not yet
  available in the UI.
- Guardians created here are **not** linked to any student. That linking happens at
  student registration.
- `family_code` must be unique.

---

## 4. Register a student

**Purpose:** add a child to a family, link their guardians, and optionally enrol them.

**Where:** open the family → **Register student**
(`/families/{family}/students/create`).

**Requires:** a family with at least one guardian if you want to link guardians.

**Writes:**
- one `Student` row with status `pending_registration`
- one `guardian_student` row per guardian you ticked
- optionally one `Enrollment` for the chosen year, grade, and section

**Limits:**
- The form shows every student in the family, not a filter, so it is slow for large
  families.
- Enrolment is one row. There is no UI to move a student between sections later.
- A student is never activated automatically. Activation after registration payment
  is unresolved and not built.
- `admission_no` must be globally unique. There is no auto-generation.

---

## 5. Add fees

**Purpose:** decide what can be charged and how much.

**Where:**
- Fee categories: Fees → `/fee-categories`
- Fee structures: `/fee-structures` → **Create fee structure**

**Requires:** grades, sections, and academic years must already exist.

**Writes:** `fee_categories` and `fee_structures`.

**Limits:**
- A fee structure cannot be edited or deleted in the UI, on purpose: it is versioned
  by academic year and may already have produced due items.
- There is no bulk import or fee copy-for-next-year action.
- A discount is applied from the student page, not here: open the student → **Discount**.

---

## 6. Generate recurring dues

**Purpose:** turn fee structures into payable amounts for a cycle.

**Where:** Due Generation → `/due-generation/recurring`.

**Requires:** at least one recurring fee category, a fee structure for the year, and
students enrolled in the matching grades.

**Writes:** one `StudentDueItem` per matching student, plus `DueItemDiscount` rows for
any discount applied.

**Limits:**
- The `upcoming window` field only affects **reminders**, not generation.
- There is no scheduler. This must be clicked each cycle.
- Opt-in categories are only charged to students with an active subscription.
- Running it twice for the same cycle is safe and does nothing the second time.

---

## 7. View the dashboard

**Purpose:** see what is owed, collected, and outstanding.

**Where:** Dues Dashboard → `/dues-dashboard`.

**Requires:** nothing.

**Writes:** nothing. It is read-only and reads stored snapshots.

**Filters:** academic year, grade, section, fee category, family, due-date range.
Grade and section require an academic year, because enrolment is year-specific.

**Limits:** totals are whole-database aggregates. There is no charting, no date
comparison, no export.

---

## 8. Record a payment

**Purpose:** settle one or more outstanding due items.

**Where:** open the family → **Record payment**
(`/families/{family}/payments/create`).

**Requires:** at least one due item with an outstanding balance.

**Writes:** a `Payment`, one `PaymentAllocation` per due item, balance and status
updates on each due item, a `Receipt`, and audit entries.

**Limits:**
- **You must type the receipt number.** There is no generated sequence, so duplicates
  are rejected by the database rather than prevented.
- The form lists every outstanding due item for the family as a row, with no search
  or pagination.
- Allocation is manual only. Nothing is auto-split.
- An allocation cannot exceed a due item's remaining balance.

---

## 9. View a receipt

**Purpose:** show proof of payment.

**Where:** the payment page → **View receipt**, or `/receipts/{id}`.

**Requires:** a recorded payment.

**Writes:** nothing.

**Limits:**
- There is **no print layout and no PDF**. Printing uses the browser's default.
- Receipt numbers are operator-typed.
- The receipt shows the snapshot from payment time. It will not reflect later
  changes to the family or the due item, by design.

---

## 10. Create an event

**Purpose:** run a one-off school event that students pay for.

**Where:** Events → `/events` → **Create event**, then:
- **Add charge** for each grade and amount
- **Manage participation** for opt-in events

**Requires:** an academic year, a fee category, at least one grade, and at least one
enrolled student in the charge grade.

**Writes:** `events`, `event_charges`, `event_participations`.

**Limits:**
- Charges cannot be edited or deleted once created.
- Creating an event generates **no** dues. That is a separate step.
- Participation only affects opt-in events. Mandatory events ignore it.

---

## 11. Generate event dues

**Purpose:** charge the applicable students for an event.

**Where:** Due Generation → `/due-generation/events`, pick the event, submit.

**Requires:** an event that already has at least one charge, and students enrolled in
those grades for the event's year.

**Writes:** `StudentDueItem` rows plus `event_due_items` links back to the event.

**Limits:**
- If no events exist the page says so and offers no create link.
- Running it twice does not duplicate.
- Discounts for the event's fee category are applied automatically.

---

## 12. Generate reminders

**Purpose:** produce records noting which guardians need telling about unpaid dues.

**Where:** Reminders → `/payment-reminders` → **Generate reminder records**.

**Requires:** due items with a balance, an unpaid or partially paid status, and a due
date.

**Writes:** `payment_reminders` rows with a `message_snapshot`, plus one audit entry.

**Limits:**
- **Nothing is sent.** There is no email, SMS, or WhatsApp integration.
- Reminders are never marked as sent by any UI.
- Reminders cannot be edited or deleted.
- Only guardians explicitly linked to the student receive one.
- Combined-billing families get one consolidated record per guardian.

---

## 13. Promote students

**Purpose:** move students into the next academic year.

**Where:** Promotion → `/promotion-batches` → **Create promotion batch**.

**Requires:** a source year, a target year, and at least one source section.

**Writes:**
- `promotion_batches` as a **draft**
- `promotion_batch_sections`
- `promotion_batch_items`, one per **active** student found

Then open the batch and press **Confirm promotion batch**, which writes target-year
`enrollments`, sets `graduated` status where applicable, and marks the batch confirmed.

**Limits:**
- Only **active** students are listed. A `pending_registration` student is skipped.
- You cannot edit an item's target grade or action in the UI. Defaults only.
- A batch with an unresolvable target section **fails to confirm** until the underlying
  section exists.
- Confirmation is all-or-nothing.
- Promotion creates **no** due items for the new year. Run generation separately.
- There is no reversal and no discard action.

---

## 14. Review what has happened (audit)

**Purpose:** see who did what.

**Where:** **there is no page for this.** Audit records are written but cannot be
viewed in the application.

**Requires:** database access.

**Writes:** nothing.

**Limits:** no audit UI, no export, and no retention policy. This is a known gap.
