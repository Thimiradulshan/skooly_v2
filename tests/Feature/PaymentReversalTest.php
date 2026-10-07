<?php

use App\Actions\Payments\ApprovePaymentReversal;
use App\Actions\Payments\RequestPaymentReversal;
use App\Models\AuditLog;
use App\Models\CorrectionReceipt;
use App\Models\Family;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use RuntimeException;

uses(LazilyRefreshDatabase::class);

function reversalDueItem(Student $student, int $amount): StudentDueItem
{
    return StudentDueItem::factory()->for($student)->create([
        'original_amount' => $amount,
        'net_amount' => $amount,
        'balance_amount' => $amount,
    ]);
}

function reversalPayment(Family $family, array $allocations): Payment
{
    return Payment::recordManual(
        $family,
        'RCT-REV-'.fake()->unique()->numerify('#####'),
        'cash',
        number_format(array_sum(array_column($allocations, 'amount')), 2, '.', ''),
        array_map(fn (array $allocation): array => [
            'student_due_item_id' => $allocation['due_item']->id,
            'amount' => number_format($allocation['amount'], 2, '.', ''),
        ], $allocations),
    );
}

it('approves a full payment reversal by reopening each original due item and preserving original records', function () {
    $family = Family::factory()->create();
    $firstDueItem = reversalDueItem(Student::factory()->for($family)->create(), 40);
    $secondDueItem = reversalDueItem(Student::factory()->for($family)->create(), 60);
    $payment = reversalPayment($family, [
        ['due_item' => $firstDueItem, 'amount' => 40],
        ['due_item' => $secondDueItem, 'amount' => 60],
    ]);
    $originalReceipt = $payment->receipt->only([
        'id', 'payment_id', 'receipt_no', 'issued_at', 'family_snapshot', 'payment_snapshot', 'allocation_snapshot', 'total_amount',
    ]);
    $accountant = userWithRole(Role::ACCOUNTANT);
    $admin = adminUser();

    $reversal = (new RequestPaymentReversal)->handle($payment, 'Duplicate cash entry.', $accountant);
    (new ApprovePaymentReversal)->handle($reversal, 'CRR-REV-001', $admin);

    expect($firstDueItem->refresh()->only(['paid_amount', 'balance_amount', 'status']))->toBe([
        'paid_amount' => '0.00', 'balance_amount' => '40.00', 'status' => StudentDueItem::STATUS_UNPAID,
    ]);
    expect($secondDueItem->refresh()->only(['paid_amount', 'balance_amount', 'status']))->toBe([
        'paid_amount' => '0.00', 'balance_amount' => '60.00', 'status' => StudentDueItem::STATUS_UNPAID,
    ]);
    expect($payment->refresh()->only(['id', 'family_id', 'payment_reference', 'method', 'amount', 'notes']))->toBe([
        'id' => $payment->id,
        'family_id' => $family->id,
        'payment_reference' => null,
        'method' => 'cash',
        'amount' => '100.00',
        'notes' => null,
    ]);
    expect($payment->allocations()->count())->toBe(2);
    expect($payment->receipt->refresh()->allocation_snapshot)->toBe($originalReceipt['allocation_snapshot']);

    $reversal->refresh();
    expect($reversal->status)->toBe(PaymentReversal::STATUS_APPROVED);
    expect($reversal->approved_by_user_id)->toBe($admin->id);
    expect(CorrectionReceipt::query()->sole()->receipt_no)->toBe('CRR-REV-001');
    expect(CorrectionReceipt::query()->sole()->original_receipt_snapshot['receipt_no'])->toBe($originalReceipt['receipt_no']);
    expect(CorrectionReceipt::query()->sole()->original_receipt_snapshot['allocation_snapshot'])->toBe($originalReceipt['allocation_snapshot']);
    expect(AuditLog::query()->where('action', AuditLog::ACTION_PAYMENT_REVERSAL_REQUESTED)->where('auditable_id', $reversal->id)->count())->toBe(1);
    expect(AuditLog::query()->where('action', AuditLog::ACTION_PAYMENT_REVERSAL_APPROVED)->where('auditable_id', $reversal->id)->count())->toBe(1);
});

it('prevents duplicate reversal requests for the same original payment', function () {
    $family = Family::factory()->create();
    $payment = reversalPayment($family, [['due_item' => reversalDueItem(Student::factory()->for($family)->create(), 100), 'amount' => 100]]);
    $accountant = userWithRole(Role::ACCOUNTANT);

    (new RequestPaymentReversal)->handle($payment, 'First request.', $accountant);

    expect(fn () => (new RequestPaymentReversal)->handle($payment, 'Second request.', $accountant))
        ->toThrow(RuntimeException::class, 'A reversal has already been requested for this payment.');
    expect(PaymentReversal::count())->toBe(1);
});

it('prevents an account with both roles from approving its own request', function () {
    $family = Family::factory()->create();
    $payment = reversalPayment($family, [['due_item' => reversalDueItem(Student::factory()->for($family)->create(), 100), 'amount' => 100]]);
    $dualRoleUser = userWithRole(Role::ACCOUNTANT);
    $dualRoleUser->roles()->syncWithoutDetaching([Role::query()->firstOrCreate(['name' => Role::ADMIN])->id]);
    $reversal = (new RequestPaymentReversal)->handle($payment, 'Incorrect amount.', $dualRoleUser);

    expect(fn () => (new ApprovePaymentReversal)->handle($reversal, 'CRR-REV-SELF', $dualRoleUser))
        ->toThrow(RuntimeException::class, 'A requester cannot approve their own payment reversal.');
    expect($reversal->refresh()->status)->toBe(PaymentReversal::STATUS_REQUESTED);
    expect(CorrectionReceipt::count())->toBe(0);
});

it('rolls back approval when a due item can no longer be safely reopened', function () {
    $family = Family::factory()->create();
    $dueItem = reversalDueItem(Student::factory()->for($family)->create(), 100);
    $payment = reversalPayment($family, [['due_item' => $dueItem, 'amount' => 60]]);
    $accountant = userWithRole(Role::ACCOUNTANT);
    $admin = adminUser();
    $reversal = (new RequestPaymentReversal)->handle($payment, 'Entry was wrong.', $accountant);
    $dueItem->update(['paid_amount' => '10.00', 'balance_amount' => '90.00', 'status' => StudentDueItem::STATUS_PARTIALLY_PAID]);

    expect(fn () => (new ApprovePaymentReversal)->handle($reversal, 'CRR-REV-ROLLBACK', $admin))
        ->toThrow(RuntimeException::class, 'The original allocation can no longer be reopened safely.');

    expect($dueItem->refresh()->only(['paid_amount', 'balance_amount', 'status']))->toBe([
        'paid_amount' => '10.00', 'balance_amount' => '90.00', 'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
    ]);
    expect($reversal->refresh()->status)->toBe(PaymentReversal::STATUS_REQUESTED);
    expect(CorrectionReceipt::count())->toBe(0);
    expect(AuditLog::query()->where('action', AuditLog::ACTION_PAYMENT_REVERSAL_APPROVED)->count())->toBe(0);
});

it('limits accountant web access to finance history and reversal requesting', function () {
    $family = Family::factory()->create();
    $payment = reversalPayment($family, [['due_item' => reversalDueItem(Student::factory()->for($family)->create(), 100), 'amount' => 100]]);
    $accountant = userWithRole(Role::ACCOUNTANT);

    $this->actingAs($accountant)->get(route('payments.index'))->assertOk();
    $this->actingAs($accountant)->get(route('payments.show', $payment))->assertOk();
    $this->actingAs($accountant)->get(route('receipts.index'))->assertOk();
    $this->actingAs($accountant)->get(route('payments.reversals.create', $payment))->assertOk();
    $this->actingAs($accountant)->post(route('payments.reversals.store', $payment), ['reason' => 'Entered twice.'])->assertRedirect();
    $reversal = PaymentReversal::query()->sole();
    $this->actingAs($accountant)->get(route('payment-reversals.index'))->assertOk();
    $this->actingAs($accountant)->get(route('families.index'))->assertForbidden();
    $this->actingAs($accountant)->get(route('families.payments.create', $family))->assertForbidden();
    $this->actingAs($accountant)->post(route('payment-reversals.approve', $reversal), ['receipt_no' => 'CRR-REV-ROLE'])->assertForbidden();
});

it('enforces the reversal role matrix and correction receipt number validation', function () {
    $family = Family::factory()->create();
    $payment = reversalPayment($family, [['due_item' => reversalDueItem(Student::factory()->for($family)->create(), 100), 'amount' => 100]]);
    $admin = adminUser();
    $teacher = userWithRole(Role::TEACHER);

    $this->actingAs($teacher)->get(route('payment-reversals.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('payments.reversals.create', $payment))->assertForbidden();
    $this->actingAs($admin)->post(route('payments.reversals.store', $payment), ['reason' => 'Admin cannot request.'])->assertForbidden();

    $accountant = userWithRole(Role::ACCOUNTANT);
    $this->actingAs($accountant)->post(route('payments.reversals.store', $payment), [])->assertSessionHasErrors('reason');
    $reversal = (new RequestPaymentReversal)->handle($payment, 'Entered twice.', $accountant);
    CorrectionReceipt::factory()->create(['receipt_no' => 'CRR-REV-DUPLICATE']);

    $this->actingAs($admin)
        ->post(route('payment-reversals.approve', $reversal), ['receipt_no' => 'CRR-REV-DUPLICATE'])
        ->assertSessionHasErrors('receipt_no');
    expect($reversal->refresh()->status)->toBe(PaymentReversal::STATUS_REQUESTED);
});
