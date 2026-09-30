<?php

use App\Actions\Audit\RecordAuditLog;
use App\Actions\Events\GenerateEventDueItems;
use App\Actions\Fees\GenerateRecurringDueItems;
use App\Actions\Notifications\GeneratePaymentReminders;
use App\Actions\Promotion\ConfirmPromotionBatch;
use App\Actions\Promotion\CreatePromotionBatch;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

uses(LazilyRefreshDatabase::class);

function auditDueItem(Student $student, array $attributes = []): StudentDueItem
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

it('records an audit log with actor action auditable metadata and occurred_at', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create();

    $log = app(RecordAuditLog::class)->handle(
        AuditLog::ACTION_FAMILY_CREATED,
        $family,
        $actor,
        ['source' => 'test'],
    );

    expect($log->action)->toBe(AuditLog::ACTION_FAMILY_CREATED);
    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($family))->toBeTrue();
    expect($log->metadata)->toBe(['source' => 'test']);
    expect($log->occurred_at)->toBeInstanceOf(Carbon::class);
});

it('records an audit log without an actor', function () {
    $family = Family::factory()->create();

    $log = app(RecordAuditLog::class)->handle(AuditLog::ACTION_FAMILY_UPDATED, $family);

    expect($log->actor_user_id)->toBeNull();
    expect($log->action)->toBe(AuditLog::ACTION_FAMILY_UPDATED);
    expect($log->auditable->is($family))->toBeTrue();
});

it('records an audit log without an auditable record', function () {
    $log = app(RecordAuditLog::class)->handle(AuditLog::ACTION_DISCOUNT_APPLIED, null, null, ['note' => 'manual']);

    expect($log->auditable_type)->toBeNull();
    expect($log->auditable_id)->toBeNull();
    expect($log->metadata)->toBe(['note' => 'manual']);
});

it('logs payment recording with actor and metadata', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = auditDueItem($student);

    Payment::recordManual($family, 'RCT-A1', 'cash', '40.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '40.00'],
    ], null, null, null, $actor);

    $log = AuditLog::query()
        ->where('action', AuditLog::ACTION_PAYMENT_RECORDED)
        ->sole();

    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable)->toBeInstanceOf(Payment::class);
    expect($log->metadata['family_id'])->toBe($family->id);
    expect($log->metadata['amount'])->toBe('40.00');
    expect($log->metadata['method'])->toBe('cash');
    expect($log->metadata['allocation_count'])->toBe(1);
    expect($log->metadata['due_item_ids'])->toBe([$dueItem->id]);
});

it('logs one payment allocation record per allocation', function () {
    $family = Family::factory()->create();
    $firstStudent = Student::factory()->for($family)->create();
    $secondStudent = Student::factory()->for($family)->create();
    $firstDueItem = auditDueItem($firstStudent);
    $secondDueItem = auditDueItem($secondStudent);

    Payment::recordManual($family, 'RCT-A2', 'cash', '100.00', [
        ['student_due_item_id' => $firstDueItem->id, 'amount' => '40.00'],
        ['student_due_item_id' => $secondDueItem->id, 'amount' => '60.00'],
    ]);

    $allocationLogs = AuditLog::query()
        ->where('action', AuditLog::ACTION_PAYMENT_ALLOCATION_RECORDED)
        ->get();

    expect($allocationLogs)->toHaveCount(2);
    expect($allocationLogs->pluck('metadata.due_item_id')->filter()->all())->toBeEmpty();

    foreach ($allocationLogs as $log) {
        expect($log->auditable)->toBeInstanceOf(PaymentAllocation::class);
        expect($log->metadata)->toHaveKeys([
            'family_id',
            'payment_id',
            'allocation_id',
            'amount',
            'student_due_item_id',
        ]);
    }
});

it('does not create audit logs when payment recording fails', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = auditDueItem($student);

    expect(fn () => Payment::recordManual($family, 'RCT-A3', 'cash', '110.00', [
        ['student_due_item_id' => $dueItem->id, 'amount' => '110.00'],
    ]))->toThrow(InvalidArgumentException::class);

    expect(AuditLog::query()->where('action', AuditLog::ACTION_PAYMENT_RECORDED)->count())->toBe(0);
    expect(AuditLog::query()->where('action', AuditLog::ACTION_PAYMENT_ALLOCATION_RECORDED)->count())->toBe(0);
    expect($dueItem->refresh()->balance_amount)->toBe('100.00');
    expect($dueItem->paid_amount)->toBe('0.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_UNPAID);
});

it('logs promotion batch creation when an actor is provided', function () {
    $actor = User::factory()->create();
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($student)->for($source)->for($grade)->for($section)->create();

    $batch = app(CreatePromotionBatch::class)->handle($source, $target, [$section->id], $actor);

    $log = AuditLog::query()
        ->where('action', AuditLog::ACTION_PROMOTION_BATCH_CREATED)
        ->sole();

    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($batch))->toBeTrue();
    expect($log->metadata['promotion_batch_id'])->toBe($batch->id);
    expect($log->metadata['source_academic_year_id'])->toBe($source->id);
    expect($log->metadata['target_academic_year_id'])->toBe($target->id);
    expect($log->metadata['source_section_ids'])->toBe([$section->id]);
    expect($log->metadata['item_count'])->toBe(1);
});

it('logs promotion batch confirmation inside the transaction', function () {
    $actor = User::factory()->create();
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $promoted = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($promoted)->for($source)->for($grade)->for($section)->create();
    $excluded = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($excluded)->for($source)->for($grade)->for($section)->create();

    $batch = app(CreatePromotionBatch::class)->handle($source, $target, [$section->id]);
    $batch->items()->orderBy('id')->first()->update(['action' => PromotionBatchItem::ACTION_EXCLUDE]);

    app(ConfirmPromotionBatch::class)->handle($batch, $actor);

    $log = AuditLog::query()
        ->where('action', AuditLog::ACTION_PROMOTION_BATCH_CONFIRMED)
        ->sole();

    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($batch))->toBeTrue();
    expect($log->metadata['promotion_batch_id'])->toBe($batch->id);
    expect($log->metadata['applied_count'])->toBe(1);
    expect($log->metadata['skipped_count'])->toBe(1);
    expect($log->metadata['graduated_count'])->toBe(0);
});

it('does not log promotion confirmation when confirmation fails', function () {
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($student)->for($source)->for($grade)->for($section)->create();

    $batch = app(CreatePromotionBatch::class)->handle($source, $target, [$section->id]);
    $batch->items->sole()->update(['target_section_id' => null]);

    expect(fn () => app(ConfirmPromotionBatch::class)->handle($batch))
        ->toThrow(InvalidArgumentException::class);

    expect(AuditLog::query()->where('action', AuditLog::ACTION_PROMOTION_BATCH_CONFIRMED)->count())->toBe(0);
    expect(Enrollment::where('academic_year_id', $target->id)->count())->toBe(0);
    expect($batch->refresh()->status)->toBe(PromotionBatch::STATUS_DRAFT);
});

it('logs one entry per recurring due generation run', function () {
    $actor = User::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = Student::factory()->create();
    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create();
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create([
        'amount' => 100,
        'frequency' => 'monthly',
    ]);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10', $actor);

    $log = AuditLog::query()
        ->where('action', AuditLog::ACTION_RECURRING_DUES_GENERATED)
        ->sole();

    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($academicYear))->toBeTrue();
    expect($log->metadata['academic_year_id'])->toBe($academicYear->id);
    expect($log->metadata['cycle_key'])->toBe('2026-10');
    expect($log->metadata['created_count'])->toBe(1);
});

it('logs one entry per event due generation run', function () {
    $actor = User::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $section = Section::factory()->for($grade)->create();
    $student = Student::factory()->create();
    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create();
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 25]);

    app(GenerateEventDueItems::class)->handle($event, $actor);

    $log = AuditLog::query()
        ->where('action', AuditLog::ACTION_EVENT_DUES_GENERATED)
        ->sole();

    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable->is($event))->toBeTrue();
    expect($log->metadata['event_id'])->toBe($event->id);
    expect($log->metadata['created_count'])->toBe(1);
});

it('logs one entry per payment reminder generation run', function () {
    $actor = User::factory()->create();
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = Student::factory()->for($family)->create();
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    auditDueItem($student, ['due_date' => '2026-10-10']);

    app(GeneratePaymentReminders::class)->handle('2026-10-05', 7, null, null, $actor);

    $log = AuditLog::query()
        ->where('action', AuditLog::ACTION_PAYMENT_REMINDERS_GENERATED)
        ->sole();

    expect($log->actor->is($actor))->toBeTrue();
    expect($log->auditable_type)->toBeNull();
    expect($log->metadata['as_of_date'])->toBe('2026-10-05');
    expect($log->metadata['upcoming_window_days'])->toBe(7);
    expect($log->metadata['created_count'])->toBe(1);
});

it('does not change payment allocation behaviour when audit logging is active', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $firstStudent = Student::factory()->for($family)->create();
    $secondStudent = Student::factory()->for($family)->create();
    $firstDueItem = auditDueItem($firstStudent, ['due_date' => '2026-10-06']);
    $secondDueItem = auditDueItem($secondStudent, [
        'due_date' => '2026-10-07',
        'net_amount' => 60,
        'balance_amount' => 60,
    ]);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($firstStudent);

    $payment = Payment::recordManual($family, 'RCT-A4', 'cash', '100.00', [
        ['student_due_item_id' => $firstDueItem->id, 'amount' => '40.00'],
        ['student_due_item_id' => $secondDueItem->id, 'amount' => '60.00'],
    ]);

    expect($payment->allocations)->toHaveCount(2);
    expect($firstDueItem->refresh()->status)->toBe(StudentDueItem::STATUS_PARTIALLY_PAID);
    expect($firstDueItem->balance_amount)->toBe('60.00');
    expect($secondDueItem->refresh()->status)->toBe(StudentDueItem::STATUS_PAID);
    expect($secondDueItem->balance_amount)->toBe('0.00');
    expect($payment->receipt)->not->toBeNull();
    expect(Receipt::count())->toBe(1);
    expect(PaymentAllocation::count())->toBe(2);
});

it('does not change due item balances or statuses when audit logging is active', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $dueItem = auditDueItem($student);
    $before = $dueItem->only(['net_amount', 'paid_amount', 'balance_amount', 'status']);

    app(RecordAuditLog::class)->handle(AuditLog::ACTION_DISCOUNT_APPLIED, $dueItem, null, ['note' => 'noop']);

    expect($dueItem->refresh()->only(array_keys($before)))->toBe($before);
});

it('does not generate due items during promotion confirmation', function () {
    $actor = User::factory()->create();
    $source = AcademicYear::factory()->create();
    $target = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['sequence_order' => 1]);
    $nextGrade = Grade::factory()->create(['sequence_order' => 2]);
    Section::factory()->for($nextGrade)->create(['name' => 'A']);
    $section = Section::factory()->for($grade)->create(['name' => 'A']);
    $student = Student::factory()->create(['status' => Student::STATUS_ACTIVE]);
    Enrollment::factory()->for($student)->for($source)->for($grade)->for($section)->create();
    $dueItemCount = StudentDueItem::count();
    $reminderCount = PaymentReminder::count();
    $batch = app(CreatePromotionBatch::class)->handle($source, $target, [$section->id]);

    app(ConfirmPromotionBatch::class)->handle($batch, $actor);

    expect(StudentDueItem::count())->toBe($dueItemCount);
    expect(StudentDueItem::where('academic_year_id', $target->id)->count())->toBe(0);
    expect(PaymentReminder::count())->toBe($reminderCount);
    expect(Payment::count())->toBe(0);
    expect(AuditLog::query()->where('action', AuditLog::ACTION_PROMOTION_BATCH_CONFIRMED)->count())->toBe(1);
});
