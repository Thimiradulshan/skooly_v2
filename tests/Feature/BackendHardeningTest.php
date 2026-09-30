<?php

use App\Actions\Audit\RecordAuditLog;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\DueItemDiscount;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventDueItem;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentReminder;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

function hardeningDueItem(Student $student, array $attributes = []): StudentDueItem
{
    return StudentDueItem::factory()
        ->for($student)
        ->for(AcademicYear::factory())
        ->for(FeeCategory::factory())
        ->create(array_merge([
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => StudentDueItem::STATUS_UNPAID,
        ], $attributes));
}

it('refuses to delete a payment that has allocations and a receipt', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = hardeningDueItem($student);
    $payment = Payment::recordManual($family, 'RCT-H1', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);

    expect(fn () => $payment->delete())->toThrow(QueryException::class);

    $this->assertModelExists($payment);
    expect(PaymentAllocation::where('payment_id', $payment->id)->count())->toBe(1);
    expect(Receipt::where('payment_id', $payment->id)->count())->toBe(1);
});

it('refuses to delete a due item that has payment allocations', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = hardeningDueItem($student);
    $payment = Payment::recordManual($family, 'RCT-H2', 'cash', '100.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '100.00'],
    ]);

    expect(fn () => $dueItem->delete())->toThrow(QueryException::class);

    $this->assertModelExists($dueItem);
    expect(PaymentAllocation::where('student_due_item_id', $dueItem->id)->count())->toBe(1);
});

it('refuses to delete a due item that has discount snapshots or event links', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $dueItem = hardeningDueItem($student, ['fee_category_id' => $feeCategory->id]);
    $discount = Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create();
    DueItemDiscount::factory()->for($dueItem, 'studentDueItem')->for($discount)->create();
    $event = Event::factory()->for(AcademicYear::factory())->for($feeCategory)->create();
    EventDueItem::factory()->for($event)->for($dueItem, 'studentDueItem')->create();

    expect(fn () => $dueItem->delete())->toThrow(QueryException::class);

    $this->assertModelExists($dueItem);
});

it('refuses to delete a family that still has payment reminders', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = hardeningDueItem($student);
    PaymentReminder::factory()->for($family)->for($guardian)->create([
        'student_due_item_id' => $dueItem->id,
        'due_item_ids' => [$dueItem->id],
    ]);

    expect(fn () => $family->delete())->toThrow(QueryException::class);

    $this->assertModelExists($family);
});

it('refuses to delete a source enrollment referenced by a promotion batch item', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    $enrollment = Enrollment::factory()
        ->for($student)
        ->for($source)
        ->for($grade)
        ->for($section)
        ->create();
    $batch = PromotionBatch::factory()->for($source, 'sourceAcademicYear')->for($target, 'targetAcademicYear')->create();
    PromotionBatchItem::factory()->for($batch)->for($student)->for($enrollment, 'sourceEnrollment')->create([
        'source_section_id' => $section->id,
    ]);

    expect(fn () => $enrollment->delete())->toThrow(QueryException::class);

    $this->assertModelExists($enrollment);
});

it('keeps an audit log readable after its actor is deleted', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create();
    $log = app(RecordAuditLog::class)->handle(
        AuditLog::ACTION_FAMILY_CREATED,
        $family,
        $actor,
        ['source' => 'test'],
    );

    $actor->delete();

    expect($log->refresh()->actor_user_id)->toBeNull();
    expect($log->action)->toBe(AuditLog::ACTION_FAMILY_CREATED);
    expect($log->metadata)->toBe(['source' => 'test']);
});

it('refuses a second payment that would overpay a partially paid due item', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = hardeningDueItem($student);

    Payment::recordManual($family, 'RCT-H3', 'cash', '60.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '60.00'],
    ]);

    expect($dueItem->refresh()->balance_amount)->toBe('40.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_PARTIALLY_PAID);

    expect(fn () => Payment::recordManual($family, 'RCT-H4', 'cash', '50.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '50.00'],
    ]))->toThrow(InvalidArgumentException::class);

    expect($dueItem->refresh()->balance_amount)->toBe('40.00');
    expect($dueItem->paid_amount)->toBe('60.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_PARTIALLY_PAID);
    expect(Payment::count())->toBe(1);
    expect(PaymentAllocation::count())->toBe(1);
    expect(AuditLog::where('action', AuditLog::ACTION_PAYMENT_RECORDED)->count())->toBe(1);
});

it('settles a due item to exactly zero rather than a negative balance', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = hardeningDueItem($student, [
        'net_amount' => 30,
        'balance_amount' => 30,
    ]);

    Payment::recordManual($family, 'RCT-H5', 'cash', '30.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '30.00'],
    ]);

    expect($dueItem->refresh()->balance_amount)->toBe('0.00');
    expect($dueItem->paid_amount)->toBe('30.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_PAID);
    expect((float) $dueItem->balance_amount)->toBeGreaterThanOrEqual(0.0);
});

it('records an audit log without an actor but with auditable metadata', function () {
    $family = Family::factory()->create();

    $log = app(RecordAuditLog::class)->handle(
        AuditLog::ACTION_DISCOUNT_APPLIED,
        $family,
        null,
        ['applied' => true, 'fee_category_id' => 7],
    );

    expect($log->actor_user_id)->toBeNull();
    expect($log->auditable->is($family))->toBeTrue();
    expect($log->metadata)->toBe(['applied' => true, 'fee_category_id' => 7]);
    expect($log->occurred_at)->not->toBeNull();
});

it('keeps audit log metadata historical when the auditable record changes', function () {
    $family = Family::factory()->create(['family_code' => 'FAM-BEFORE']);
    $student = Student::factory()->for($family)->create();
    $log = app(RecordAuditLog::class)->handle(
        AuditLog::ACTION_STUDENT_REGISTERED,
        $student,
        null,
        ['family_code' => 'FAM-BEFORE', 'admission_no' => $student->admission_no],
    );

    $family->update(['family_code' => 'FAM-AFTER']);
    $student->update(['admission_no' => 'ADM-CHANGED']);

    expect($log->refresh()->metadata['family_code'])->toBe('FAM-BEFORE');
    expect($log->metadata['admission_no'])->not->toBe('ADM-CHANGED');
    expect($log->auditable->is($student))->toBeTrue();
});

it('keeps a due item discount snapshot after the source discount is deleted', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $dueItem = hardeningDueItem($student, ['fee_category_id' => $feeCategory->id]);
    $discount = Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'type' => 'scholarship',
        'value' => 10,
        'value_type' => 'amount',
    ]);
    $snapshot = DueItemDiscount::factory()->for($dueItem, 'studentDueItem')->for($discount)->create([
        'type' => 'scholarship',
        'value' => 10,
        'value_type' => 'amount',
        'amount_applied' => 10,
    ]);

    $discount->update(['value' => 99]);
    expect($snapshot->refresh()->value)->toBe('10.00');

    $discount->delete();

    $snapshot = $snapshot->refresh();

    expect($snapshot->discount_id)->toBeNull();
    expect($snapshot->type)->toBe('scholarship');
    expect($snapshot->value)->toBe('10.00');
    expect($snapshot->amount_applied)->toBe('10.00');
});
