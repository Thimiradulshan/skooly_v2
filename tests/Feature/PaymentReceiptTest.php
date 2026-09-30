<?php

use App\Models\Family;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

it('associates a payment with a family', function () {
    $family = Family::factory()->create();
    $payment = Payment::factory()->for($family)->create();

    expect($payment->family->is($family))->toBeTrue();
});

it('manually allocates a payment to one student due item', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create();

    $payment = Payment::recordManual($family, 'RCT-001', 'cash', '40.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '40.00'],
    ]);

    expect($payment->allocations)->toHaveCount(1);
    expect($payment->allocations->sole()->studentDueItem->is($dueItem))->toBeTrue();
    expect($dueItem->refresh()->paid_amount)->toBe('40.00');
    expect($dueItem->balance_amount)->toBe('60.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_PARTIALLY_PAID);
});

it('manually allocates a payment across multiple student due items', function () {
    $family = Family::factory()->create();
    $firstStudent = Student::factory()->for($family)->create();
    $secondStudent = Student::factory()->for($family)->create();
    $firstDueItem = StudentDueItem::factory()->for($firstStudent)->create([
        'original_amount' => 40,
        'net_amount' => 40,
        'balance_amount' => 40,
    ]);
    $secondDueItem = StudentDueItem::factory()->for($secondStudent)->create([
        'original_amount' => 60,
        'net_amount' => 60,
        'balance_amount' => 60,
    ]);

    $payment = Payment::recordManual($family, 'RCT-002', 'cash', '100.00', [
        ['student_due_item_id' => $firstDueItem->id, 'amount' => '40.00'],
        ['student_due_item_id' => $secondDueItem->id, 'amount' => '60.00'],
    ]);

    expect($payment->allocations)->toHaveCount(2);
    expect($firstDueItem->refresh()->status)->toBe(StudentDueItem::STATUS_PAID);
    expect($secondDueItem->refresh()->status)->toBe(StudentDueItem::STATUS_PAID);
});

it('marks a fully paid due item as paid', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create();

    Payment::recordManual($family, 'RCT-003', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);

    expect($dueItem->refresh()->paid_amount)->toBe('100.00');
    expect($dueItem->balance_amount)->toBe('0.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_PAID);
});

it('rejects allocations above a due item balance', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create();

    expect(fn () => Payment::recordManual($family, 'RCT-004', 'cash', '110.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '110.00'],
    ]))->toThrow(InvalidArgumentException::class);

    expect(Payment::count())->toBe(0);
    expect($dueItem->refresh()->balance_amount)->toBe('100.00');
});

it('requires manual allocations to equal the payment amount', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create();

    expect(fn () => Payment::recordManual($family, 'RCT-005', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '90.00'],
    ]))->toThrow(InvalidArgumentException::class);

    expect(Payment::count())->toBe(0);
});

it('generates a receipt with payment and allocation snapshots', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-001']);
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create(['description' => 'Annual tuition']);

    $payment = Payment::recordManual($family, 'RCT-006', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);

    expect($payment->receipt->receipt_no)->toBe('RCT-006');
    expect($payment->receipt->family_snapshot['family_code'])->toBe('FAM-001');
    expect($payment->receipt->payment_snapshot['amount'])->toBe('100.00');
    expect($payment->receipt->allocation_snapshot[0]['description'])->toBe('Annual tuition');
});

it('does not rewrite receipt snapshots when a due item changes later', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = StudentDueItem::factory()->for($student)->create(['description' => 'Annual tuition']);
    $payment = Payment::recordManual($family, 'RCT-007', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);

    $dueItem->update(['description' => 'Changed tuition', 'original_amount' => 200]);

    expect($payment->receipt->refresh()->allocation_snapshot[0]['description'])->toBe('Annual tuition');
    expect($payment->receipt->allocation_snapshot[0]['original_amount'])->toBe('100.00');
});

it('does not automatically allocate payments', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    StudentDueItem::factory()->for($student)->create();
    $payment = Payment::factory()->for($family)->create();

    expect($payment->allocations)->toBeEmpty();
    expect($payment->receipt)->toBeNull();
});
