# Phase 6: Payments & Receipts - Implementation Log

## Overview
Phase 6 implements manual payment allocation against StudentDueItems with receipt snapshots. No automatic allocation strategy is implemented.

**Date**: 2026-09-30
**Build Mode**: Approved
**Commit**: No (pending review)

---

## Initial Worktree State
```bash
$ git status --short
(no output)
```
Worktree was clean - Phases 1-5 complete and committed.

---

## Files Inspected Before Implementation

```bash
# Core models
app/Models/Family.php
app/Models/Student.php
app/Models/StudentDueItem.php
app/Models/FeeCategory.php
app/Models/FeeStructure.php
app/Models/StudentFeeSubscription.php
app/Models/Discount.php
app/Models/DueItemDiscount.php

# Existing tests
tests/Feature/FeeDueDiscountTest.php
```

---

## Implementation Steps

### 1. Generated Scaffolding
```bash
# Add payment balance columns to existing student_due_items
php artisan make:migration add_payment_balances_to_student_due_items_table --table=student_due_items

# Payment models with migrations and factories
php artisan make:model Payment --migration --factory
php artisan make:model PaymentAllocation --migration --factory
php artisan make:model Receipt --migration --factory

# Feature test file
php artisan make:test --pest PaymentReceiptTest
```

### 2. Schema Implementation

**Migration: add_payment_balances_to_student_due_items_table**
```php
Schema::table('student_due_items', function (Blueprint $table) {
    $table->decimal('paid_amount', 12, 2)->default(0)->after('net_amount');
    $table->decimal('balance_amount', 12, 2)->after('paid_amount');
});
```

**Migration: create_payments_table**
```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('family_id')->constrained()->restrictOnDelete();
    $table->string('payment_reference')->nullable()->unique();
    $table->dateTime('paid_at');
    $table->string('method');
    $table->decimal('amount', 12, 2);
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

**Migration: create_payment_allocations_table**
```php
Schema::create('payment_allocations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payment_id')->constrained()->restrictOnDelete();
    $table->foreignId('student_due_item_id')->constrained()->restrictOnDelete();
    $table->decimal('amount', 12, 2);
    $table->timestamps();
});
```

**Migration: create_receipts_table**
```php
Schema::create('receipts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
    $table->string('receipt_no')->unique();
    $table->dateTime('issued_at');
    $table->json('family_snapshot');
    $table->json('payment_snapshot');
    $table->json('allocation_snapshot');
    $table->decimal('total_amount', 12, 2);
    $table->timestamps();
});
```

### 3. Model Implementation

**Payment.php** - Core domain model with `recordManual()` transactional method
- Family relationship (`belongsTo`)
- Allocations relationship (`hasMany<PaymentAllocation>`)
- Receipt relationship (`hasOne<Receipt>`)
- `recordManual()` static method:
  - Validates payment amount > 0
  - Validates allocation total equals payment amount
  - Uses DB transaction with row locking (`lockForUpdate()`)
  - Validates due items belong to payment family
  - Validates allocation doesn't exceed due item balance
  - Updates `paid_amount`, `balance_amount`, `status` on each due item
  - Creates PaymentAllocation records
  - Creates Receipt with immutable JSON snapshots
  - Cent-accurate monetary arithmetic (`toCents()` / `fromCents()`)

**PaymentAllocation.php** - Links payment to specific due items
- Payment relationship (`belongsTo`)
- StudentDueItem relationship (`belongsTo`)
- Decimal cast for amount

**Receipt.php** - Immutable receipt snapshot
- Payment relationship (`belongsTo`)
- JSON casts for snapshots
- Decimal cast for total_amount

**StudentDueItem.php updates**
- Added `paid_amount`, `balance_amount` to fillable
- Status constants: `STATUS_UNPAID`, `STATUS_PARTIALLY_PAID`, `STATUS_PAID`
- PaymentAllocations relationship (`hasMany`)
- Decimal casts for new balance fields

**Family.php updates**
- Payments relationship (`hasMany<Payment>`)

### 4. Factory Definitions

```php
// PaymentFactory
'family_id' => Family::factory(),
'payment_reference' => fake()->unique()->bothify('PAY-#####'),
'paid_at' => fake()->dateTimeBetween('-1 year'),
'method' => 'cash',
'amount' => 100,
'notes' => fake()->optional()->sentence(),

// PaymentAllocationFactory
'payment_id' => Payment::factory(),
'student_due_item_id' => StudentDueItem::factory(),
'amount' => 100,

// ReceiptFactory
'payment_id' => Payment::factory(),
'receipt_no' => fake()->unique()->bothify('RCT-#####'),
'issued_at' => fake()->dateTimeBetween('-1 year'),
'family_snapshot' => [],
'payment_snapshot' => [],
'allocation_snapshot' => [],
'total_amount' => 100,

// StudentDueItemFactory updates
'paid_amount' => 0,
'balance_amount' => 100,
'status' => StudentDueItem::STATUS_UNPAID,
```

### 5. Feature Tests (PaymentReceiptTest.php)

| Test | Purpose |
|------|---------|
| `associates a payment with a family` | Basic relationship |
| `manually allocates a payment to one student due item` | Single allocation |
| `manually allocates a payment across multiple student due items` | Multi-allocation |
| `marks a fully paid due item as paid` | Full payment status |
| `rejects allocations above a due item balance` | Balance validation |
| `requires manual allocations to equal the payment amount` | Total validation |
| `generates a receipt with payment and allocation snapshots` | Receipt creation |
| `does not rewrite receipt snapshots when a due item changes later` | Snapshot immutability |
| `does not automatically allocate payments` | No auto-allocation |

### 5b. Phase 5 Test Update
```php
// FeeDueDiscountTest.php - Updated for Phase 6
it('creates payment and payment allocation tables in Phase 6', function () {
    expect(Schema::hasTable('payments'))->toBeTrue();
    expect(Schema::hasTable('payment_allocations'))->toBeTrue();
});
```

---

## Issues Encountered & Fixes

### Issue 1: Payment Factory `optional()->bothify()` returned null
**Error**: `Call to a member function bothify() on null`
**Fix**: Removed `optional()` from payment_reference factory
```php
// Before
'payment_reference' => fake()->optional()->unique()->bothify('PAY-#####'),
// After
'payment_reference' => fake()->unique()->bothify('PAY-#####'),
```

### Issue 2: PHPStan type errors in Payment::recordManual()
**Errors**: 6 errors including:
- Access to undefined property `family_id` on Model
- `toCents()` expects string, float given
- Undefined method `allocations()` / `receipt()`

**Fixes Applied**:
1. Added generic PHPDoc return types to relationships:
```php
/** @return HasMany<Payment, $this> */
public function payments(): HasMany

/** @return HasOne<Receipt, $this> */
public function receipt(): HasOne
```

2. Changed `lockForUpdate()->findOrFail()` to `whereKey()->lockForUpdate()->firstOrFail()`

3. Normalized `toCents()` to accept `string|int|float`:
```php
private static function toCents(string|int|float $amount): int {
    if (is_int($amount)) return $amount * 100;
    if (is_float($amount)) $amount = number_format($amount, 2, '.', '');
    // string validation...
}
```

### Issue 3: Runtime LogicException from rawAmount guard
**Error**: `balance_amount must be stored as a decimal string`
**Root Cause**: MySQL driver returns DECIMAL as float, not string
**Fix**: Removed strict raw-string guard, added robust type coercion in `toCents()`

### Issue 4: Phase 5 test expected tables to not exist
**Error**: `Failed asserting that true is false`
**Fix**: Updated test to assert tables exist in Phase 6

---

## Full Verification Results

```bash
# Migration
$ php artisan migrate:fresh --no-interaction
# All 25 migrations passed

# Tests
$ php artisan test --compact
# 52 tests, 130 assertions passed

# Static Analysis
$ php vendor/bin/phpstan analyse
# 0 errors

# Code Style
$ php vendor/bin/pint --test
# passed

# Security Audit
$ composer audit
# No security vulnerability advisories found

# Git
$ git diff --check
# passed (no whitespace errors)

$ git status --short --untracked-files=all
# M app/Models/Family.php
# M app/Models/StudentDueItem.php
# M database/factories/StudentDueItemFactory.php
# M tests/Feature/FeeDueDiscountTest.php
# ?? app/Models/Payment.php
# ?? app/Models/PaymentAllocation.php
# ?? app/Models/Receipt.php
# ?? database/factories/PaymentAllocationFactory.php
# ?? database/factories/PaymentFactory.php
# ?? database/factories/ReceiptFactory.php
# ?? database/migrations/2026_09_30_014152_add_payment_balances_to_student_due_items_table.php
# ?? database/migrations/2026_09_30_014203_create_payments_table.php
# ?? database/migrations/2026_09_30_014211_create_payment_allocations_table.php
# ?? database/migrations/2026_09_30_014219_create_receipts_table.php
# ?? tests/Feature/PaymentReceiptTest.php
```

---

## Changed Files Summary

### Modified (4)
- `app/Models/Family.php` - added `payments()` relationship
- `app/Models/StudentDueItem.php` - added balance fields, status constants, paymentAllocations relationship
- `database/factories/StudentDueItemFactory.php` - added default balance values
- `tests/Feature/FeeDueDiscountTest.php` - updated Phase 5 table assertion

### New (17)
- `app/Models/Payment.php` - with `recordManual()` transactional method
- `app/Models/PaymentAllocation.php`
- `app/Models/Receipt.php`
- `database/factories/PaymentFactory.php`
- `database/factories/PaymentAllocationFactory.php`
- `database/factories/ReceiptFactory.php`
- `database/migrations/2026_09_30_014152_add_payment_balances_to_student_due_items_table.php`
- `database/migrations/2026_09_30_014203_create_payments_table.php`
- `database/migrations/2026_09_30_014211_create_payment_allocations_table.php`
- `database/migrations/2026_09_30_014219_create_receipts_table.php`
- `tests/Feature/PaymentReceiptTest.php`

---

## Out of Scope (Explicitly Not Implemented)
- Automatic even-split allocation
- Oldest-first allocation
- Online gateway integration
- Payment reminders
- Payment dashboard
- Events/notifications
- Promotion logic
- Audit logging
- UI/controllers/routes

---

## Unresolved Business Decisions (Preserved from Phase 5)
- Default payment allocation strategy
- Default sibling discount percentage/rule
- Promotion reversal safety window
- Duplicate-family detection: block vs warning

---

## Deferred Items
- Scheduled recurring due generation
- Payment-driven student registration activation
- Payments (now implemented - manual only)
- Receipts (now implemented)
- Payment allocation (now implemented - manual only)
- Dashboards
- Reminders
- Events
- UI/controllers/routes
- Promotion
- Audit

---

## Phase 6 Status: **Ready to Commit**

All verification checks pass:
- ✅ `php artisan migrate:fresh --no-interaction`
- ✅ `php artisan test --compact` (52 tests, 130 assertions)
- ✅ `php vendor/bin/phpstan analyse` (0 errors)
- ✅ `php vendor/bin/pint --test`
- ✅ `composer audit`
- ✅ `git diff --check`
- ✅ `git status --short --untracked-files=all`

No application code changes needed. Only checkpoint context files require update before commit.