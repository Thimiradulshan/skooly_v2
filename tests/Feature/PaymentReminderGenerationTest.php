<?php

use App\Actions\Notifications\GeneratePaymentReminders;
use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentReminder;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function reminderStudent(Family $family): Student
{
    return Student::factory()->for($family)->create();
}

function eligibleDueItem(Student $student, array $attributes = []): StudentDueItem
{
    return StudentDueItem::factory()
        ->for($student)
        ->for(AcademicYear::factory())
        ->for(FeeCategory::factory())
        ->create(array_merge([
            'due_date' => '2026-10-10',
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => StudentDueItem::STATUS_UNPAID,
        ], $attributes));
}

function remindersFor(string $asOfDate = '2026-10-05', int $window = 7, ?int $academicYearId = null, ?int $familyId = null): int
{
    return app(GeneratePaymentReminders::class)->handle($asOfDate, $window, $academicYearId, $familyId);
}

it('generates an upcoming reminder for a due item inside the window', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $dueItem = eligibleDueItem($student, ['due_date' => '2026-10-10']);

    $created = remindersFor('2026-10-05', 7);

    $reminder = PaymentReminder::query()->sole();

    expect($created)->toBe(1);
    expect($reminder->reminder_type)->toBe(PaymentReminder::TYPE_UPCOMING);
    expect($reminder->status)->toBe(PaymentReminder::STATUS_PENDING);
    expect($reminder->student_due_item_id)->toBe($dueItem->id);
    expect($reminder->due_item_ids)->toBe([$dueItem->id]);
    expect($reminder->sent_at)->toBeNull();
});

it('generates an overdue reminder for a past due item', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    eligibleDueItem($student, ['due_date' => '2026-09-01']);

    remindersFor('2026-10-05', 7);

    expect(PaymentReminder::query()->sole()->reminder_type)->toBe(PaymentReminder::TYPE_OVERDUE);
});

it('does not generate a reminder for a fully paid due item', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    eligibleDueItem($student, [
        'net_amount' => 100,
        'paid_amount' => 100,
        'balance_amount' => 0,
        'status' => StudentDueItem::STATUS_PAID,
    ]);

    expect(remindersFor('2026-10-05'))->toBe(0);
    expect(PaymentReminder::count())->toBe(0);
});

it('does not generate a reminder for a due item with zero balance', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    eligibleDueItem($student, [
        'net_amount' => 100,
        'paid_amount' => 100,
        'balance_amount' => 0,
        'status' => StudentDueItem::STATUS_UNPAID,
    ]);

    expect(remindersFor('2026-10-05'))->toBe(0);
});

it('does not generate a reminder for a due item without a due date', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    eligibleDueItem($student, ['due_date' => null]);

    expect(remindersFor('2026-10-05'))->toBe(0);
});

it('does not generate a reminder outside the upcoming window', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    eligibleDueItem($student, ['due_date' => '2026-11-20']);

    expect(remindersFor('2026-10-05', 7))->toBe(0);
});

it('sends only to guardians explicitly linked to the student', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $linkedGuardian = Guardian::factory()->for($family)->create();
    $linkedGuardian->students()->attach($student);
    eligibleDueItem($student);

    $created = remindersFor('2026-10-05');

    expect($created)->toBe(1);
    expect(PaymentReminder::query()->sole()->guardian_id)->toBe($linkedGuardian->id);
});

it('does not send to unrelated guardians in the same family', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $linkedGuardian = Guardian::factory()->for($family)->create();
    $linkedGuardian->students()->attach($student);
    $unrelatedGuardian = Guardian::factory()->for($family)->create();
    $unrelatedGuardian->students()->attach(reminderStudent($family));
    eligibleDueItem($student);

    $created = remindersFor('2026-10-05');

    expect($created)->toBe(1);
    expect(PaymentReminder::query()->sole()->guardian_id)->toBe($linkedGuardian->id);
    expect($unrelatedGuardian->paymentReminders()->count())->toBe(0);
});

it('creates one consolidated reminder per guardian for combined billing', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $firstStudent = reminderStudent($family);
    $secondStudent = reminderStudent($family);
    $guardian->students()->attach([$firstStudent->id, $secondStudent->id]);
    $firstDueItem = eligibleDueItem($firstStudent, ['due_date' => '2026-10-06']);
    $secondDueItem = eligibleDueItem($secondStudent, ['due_date' => '2026-10-07']);

    $created = remindersFor('2026-10-05');

    $reminder = PaymentReminder::query()->sole();

    expect($created)->toBe(1);
    expect(PaymentReminder::count())->toBe(1);
    expect($reminder->student_due_item_id)->toBeNull();
    expect($reminder->due_item_ids)->toHaveCount(2);
    expect($reminder->due_item_ids)->toContain($firstDueItem->id, $secondDueItem->id);
});

it('consolidates only the students the guardian is linked to', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $linkedStudent = reminderStudent($family);
    $unlinkedStudent = reminderStudent($family);
    $guardian->students()->attach($linkedStudent);
    $linkedDueItem = eligibleDueItem($linkedStudent, ['due_date' => '2026-10-06']);
    $unlinkedDueItem = eligibleDueItem($unlinkedStudent, ['due_date' => '2026-10-07']);

    remindersFor('2026-10-05');

    $reminder = PaymentReminder::query()->sole();

    expect($reminder->due_item_ids)->toBe([$linkedDueItem->id]);
    expect($reminder->due_item_ids)->not->toContain($unlinkedDueItem->id);
});

it('creates one reminder per due item when combined billing is off', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $firstDueItem = eligibleDueItem($student, ['due_date' => '2026-10-06']);
    $secondDueItem = eligibleDueItem($student, ['due_date' => '2026-10-07']);

    $created = remindersFor('2026-10-05');

    expect($created)->toBe(2);
    expect(PaymentReminder::count())->toBe(2);
    expect(PaymentReminder::query()->pluck('student_due_item_id')->all())
        ->toEqualCanonicalizing([$firstDueItem->id, $secondDueItem->id]);
});

it('prevents duplicate reminders when the action runs twice', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $student = reminderStudent($family);
    $guardian->students()->attach($student);
    eligibleDueItem($student);

    $first = remindersFor('2026-10-05');
    $second = remindersFor('2026-10-05');
    $otherDate = remindersFor('2026-10-06');

    expect($first)->toBe(1);
    expect($second)->toBe(0);
    expect($otherDate)->toBe(1);
    expect(PaymentReminder::count())->toBe(2);
});

it('filters by academic year', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => false]);
    $student = reminderStudent($family);
    $guardian = Guardian::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $matchedYear = AcademicYear::factory()->create();
    $otherYear = AcademicYear::factory()->create();
    $matchedDueItem = StudentDueItem::factory()->for($student)->for($matchedYear)->for(FeeCategory::factory())->create([
        'due_date' => '2026-10-10',
        'balance_amount' => 100,
        'status' => StudentDueItem::STATUS_UNPAID,
    ]);
    StudentDueItem::factory()->for($student)->for($otherYear)->for(FeeCategory::factory())->create([
        'due_date' => '2026-10-10',
        'balance_amount' => 50,
        'status' => StudentDueItem::STATUS_UNPAID,
    ]);

    $created = remindersFor('2026-10-05', 7, $matchedYear->id);

    expect($created)->toBe(1);
    expect(PaymentReminder::query()->sole()->student_due_item_id)->toBe($matchedDueItem->id);
});

it('filters by family', function () {
    $firstFamily = Family::factory()->create(['combined_billing_enabled' => false]);
    $secondFamily = Family::factory()->create(['combined_billing_enabled' => false]);
    $firstStudent = reminderStudent($firstFamily);
    $firstGuardian = Guardian::factory()->for($firstFamily)->create();
    $firstGuardian->students()->attach($firstStudent);
    eligibleDueItem($firstStudent);
    $secondStudent = reminderStudent($secondFamily);
    $secondGuardian = Guardian::factory()->for($secondFamily)->create();
    $secondGuardian->students()->attach($secondStudent);
    eligibleDueItem($secondStudent);

    $created = remindersFor('2026-10-05', 7, null, $firstFamily->id);

    expect($created)->toBe(1);
    expect(PaymentReminder::query()->sole()->family_id)->toBe($firstFamily->id);
});

it('stores family guardian due item and total data in the message snapshot', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true, 'family_code' => 'FAM-SNAP']);
    $guardian = Guardian::factory()->for($family)->create(['name' => 'Parent One']);
    $student = reminderStudent($family);
    $guardian->students()->attach($student);
    $dueItem = eligibleDueItem($student, [
        'due_date' => '2026-10-06',
        'net_amount' => 100,
        'discount_amount' => 10,
        'paid_amount' => 30,
        'balance_amount' => 60,
        'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
        'description' => 'Annual tuition',
    ]);

    remindersFor('2026-10-05');

    $snapshot = PaymentReminder::query()->sole()->message_snapshot;
    $item = $snapshot['due_items'][0];

    expect($snapshot['family_id'])->toBe($family->id);
    expect($snapshot['family_code'])->toBe('FAM-SNAP');
    expect($snapshot['guardian_id'])->toBe($guardian->id);
    expect($snapshot['guardian_name'])->toBe('Parent One');
    expect($snapshot['reminder_type'])->toBe(PaymentReminder::TYPE_UPCOMING);
    expect($snapshot['as_of_date'])->toBe('2026-10-05');
    expect($snapshot['total_balance_amount'])->toBe('60.00');
    expect($snapshot['due_item_count'])->toBe(1);
    expect($item['student_due_item_id'])->toBe($dueItem->id);
    expect($item['student_id'])->toBe($student->id);
    expect($item['student_name'])->toBe($student->name);
    expect($item['admission_no'])->toBe($student->admission_no);
    expect($item['description'])->toBe('Annual tuition');
    expect($item['due_date'])->toBe('2026-10-06');
    expect($item['balance_amount'])->toBe('60.00');
    expect($item['status'])->toBe(StudentDueItem::STATUS_PARTIALLY_PAID);
});

it('does not create payment records or modify due items', function () {
    $family = Family::factory()->create(['combined_billing_enabled' => true]);
    $guardian = Guardian::factory()->for($family)->create();
    $student = reminderStudent($family);
    $guardian->students()->attach($student);
    $dueItem = eligibleDueItem($student, [
        'due_date' => '2026-10-06',
        'net_amount' => 100,
        'paid_amount' => 20,
        'balance_amount' => 80,
        'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
    ]);
    $before = $dueItem->only(['net_amount', 'paid_amount', 'balance_amount', 'status']);

    $created = remindersFor('2026-10-05');

    expect($created)->toBe(1);
    expect(Payment::count())->toBe(0);
    expect(PaymentAllocation::count())->toBe(0);
    expect(Receipt::count())->toBe(0);
    expect($dueItem->refresh()->only(array_keys($before)))->toBe($before);
});
