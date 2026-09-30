<?php

use App\Actions\Events\GenerateEventDueItems;
use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\EventDueItem;
use App\Models\EventParticipation;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function eventStudent(AcademicYear $academicYear, Grade $grade, ?Family $family = null): Student
{
    $student = Student::factory()->for($family ?? Family::factory())->create();
    $section = Section::factory()->for($grade)->create();

    Enrollment::factory()
        ->for($student)
        ->for($academicYear)
        ->for($grade)
        ->for($section)
        ->create();

    return $student;
}

it('generates due items for enrolled students in the applicable grade', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = eventStudent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create([
        'name' => 'Sports Day',
        'event_date' => '2026-10-15',
        'is_mandatory' => true,
    ]);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 40]);

    $created = app(GenerateEventDueItems::class)->handle($event);

    $dueItem = StudentDueItem::query()->sole();

    expect($created)->toBe(1);
    expect($dueItem->student_id)->toBe($student->id);
    expect($dueItem->academic_year_id)->toBe($academicYear->id);
    expect($dueItem->fee_category_id)->toBe($event->fee_category_id);
    expect($dueItem->fee_structure_id)->toBeNull();
    expect($dueItem->description)->toBe('Event: Sports Day');
    expect($dueItem->frequency)->toBeNull();
    expect($dueItem->original_amount)->toBe('40.00');
    expect($dueItem->net_amount)->toBe('40.00');
    expect($dueItem->paid_amount)->toBe('0.00');
    expect($dueItem->balance_amount)->toBe('40.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_UNPAID);
    expect($dueItem->due_date->toDateString())->toBe('2026-10-15');
});

it('confirms the event when generating dues', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    eventStudent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create(['confirmed_at' => null]);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 10]);

    app(GenerateEventDueItems::class)->handle($event);

    expect($event->refresh()->confirmed_at)->not->toBeNull();
});

it('does not generate due items for students in another academic year', function () {
    $academicYear = AcademicYear::factory()->create();
    $otherAcademicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $otherYearStudent = eventStudent($otherAcademicYear, $grade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 10]);

    $created = app(GenerateEventDueItems::class)->handle($event);

    expect($created)->toBe(0);
    expect(StudentDueItem::count())->toBe(0);
    expect($otherYearStudent->studentDueItems()->count())->toBe(0);
});

it('does not generate due items for students in another grade', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $otherGrade = Grade::factory()->create();
    $otherGradeStudent = eventStudent($academicYear, $otherGrade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 10]);

    $created = app(GenerateEventDueItems::class)->handle($event);

    expect($created)->toBe(0);
    expect($otherGradeStudent->studentDueItems()->count())->toBe(0);
});

it('snapshots grade-specific charge amounts', function () {
    $academicYear = AcademicYear::factory()->create();
    $firstGrade = Grade::factory()->create();
    $secondGrade = Grade::factory()->create();
    $firstStudent = eventStudent($academicYear, $firstGrade);
    $secondStudent = eventStudent($academicYear, $secondGrade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($firstGrade)->create(['amount' => 30]);
    EventCharge::factory()->for($event)->for($secondGrade)->create(['amount' => 45]);

    $created = app(GenerateEventDueItems::class)->handle($event);

    expect($created)->toBe(2);
    expect($firstStudent->studentDueItems()->sole()->original_amount)->toBe('30.00');
    expect($secondStudent->studentDueItems()->sole()->original_amount)->toBe('45.00');
});

it('represents a uniform charge across multiple event charge rows', function () {
    $academicYear = AcademicYear::factory()->create();
    $firstGrade = Grade::factory()->create();
    $secondGrade = Grade::factory()->create();
    $firstStudent = eventStudent($academicYear, $firstGrade);
    $secondStudent = eventStudent($academicYear, $secondGrade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($firstGrade)->create(['amount' => 20]);
    EventCharge::factory()->for($event)->for($secondGrade)->create(['amount' => 20]);

    app(GenerateEventDueItems::class)->handle($event);

    expect($firstStudent->studentDueItems()->sole()->net_amount)->toBe('20.00');
    expect($secondStudent->studentDueItems()->sole()->net_amount)->toBe('20.00');
});

it('generates due items only for opted-in students on an opt-in event', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $optedIn = eventStudent($academicYear, $grade);
    $optedOut = eventStudent($academicYear, $grade);
    $noRecord = eventStudent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create(['is_mandatory' => false]);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 15]);
    EventParticipation::factory()->for($event)->for($optedIn)->create([
        'status' => EventParticipation::STATUS_OPTED_IN,
    ]);
    EventParticipation::factory()->for($event)->for($optedOut)->create([
        'status' => EventParticipation::STATUS_OPTED_OUT,
    ]);

    $created = app(GenerateEventDueItems::class)->handle($event);

    expect($created)->toBe(1);
    expect(StudentDueItem::query()->sole()->student_id)->toBe($optedIn->id);
    expect($optedOut->studentDueItems()->count())->toBe(0);
    expect($noRecord->studentDueItems()->count())->toBe(0);
});

it('prevents duplicate due items when the action runs twice', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    eventStudent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 12]);

    $first = app(GenerateEventDueItems::class)->handle($event);
    $second = app(GenerateEventDueItems::class)->handle($event);

    expect($first)->toBe(1);
    expect($second)->toBe(0);
    expect(StudentDueItem::count())->toBe(1);
    expect(EventDueItem::count())->toBe(1);
});

it('links generated due items back through event_due_items', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = eventStudent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 18]);

    app(GenerateEventDueItems::class)->handle($event);

    $link = EventDueItem::query()->sole();
    $dueItem = $student->studentDueItems()->sole();

    expect($link->event->is($event))->toBeTrue();
    expect($link->student_due_item_id)->toBe($dueItem->id);
    expect($dueItem->eventDueItem->is($link))->toBeTrue();
    expect($event->eventDueItems)->toHaveCount(1);
});

it('does not rewrite the due snapshot when the event charge changes later', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    eventStudent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create();
    $charge = EventCharge::factory()->for($event)->for($grade)->create(['amount' => 22]);

    app(GenerateEventDueItems::class)->handle($event);

    $charge->update(['amount' => 99]);

    expect(StudentDueItem::query()->sole()->original_amount)->toBe('22.00');
    expect(StudentDueItem::query()->sole()->net_amount)->toBe('22.00');
});

it('applies an active amount discount and snapshots it', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = eventStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create();
    $event = Event::factory()->for($academicYear)->for($feeCategory)->create(['event_date' => '2026-10-15']);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 100]);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'type' => 'event_concession',
        'value' => 30,
        'value_type' => 'amount',
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateEventDueItems::class)->handle($event);

    $dueItem = StudentDueItem::query()->sole();
    $snapshot = $dueItem->dueItemDiscounts()->sole();

    expect($dueItem->original_amount)->toBe('100.00');
    expect($dueItem->discount_amount)->toBe('30.00');
    expect($dueItem->net_amount)->toBe('70.00');
    expect($dueItem->balance_amount)->toBe('70.00');
    expect($snapshot->amount_applied)->toBe('30.00');
    expect($snapshot->type)->toBe('event_concession');
});

it('applies an active percentage discount and snapshots it', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = eventStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create();
    $event = Event::factory()->for($academicYear)->for($feeCategory)->create(['event_date' => '2026-10-15']);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 80]);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'value' => 25,
        'value_type' => 'percentage',
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateEventDueItems::class)->handle($event);

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->discount_amount)->toBe('20.00');
    expect($dueItem->net_amount)->toBe('60.00');
    expect($dueItem->dueItemDiscounts()->sole()->amount_applied)->toBe('20.00');
});

it('never lets an event discount make the net amount negative', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = eventStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create();
    $event = Event::factory()->for($academicYear)->for($feeCategory)->create(['event_date' => '2026-10-15']);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 30]);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'value' => 100,
        'value_type' => 'amount',
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateEventDueItems::class)->handle($event);

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->discount_amount)->toBe('30.00');
    expect($dueItem->net_amount)->toBe('0.00');
    expect($dueItem->balance_amount)->toBe('0.00');
    expect($dueItem->dueItemDiscounts()->sole()->amount_applied)->toBe('30.00');
});

it('ignores an inactive event discount', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = eventStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create();
    $event = Event::factory()->for($academicYear)->for($feeCategory)->create(['event_date' => '2026-10-15']);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 50]);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'value' => 10,
        'value_type' => 'amount',
        'is_active' => false,
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateEventDueItems::class)->handle($event);

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->discount_amount)->toBe('0.00');
    expect($dueItem->dueItemDiscounts)->toBeEmpty();
});

it('does not create payments or receipts during event generation', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    eventStudent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 60]);

    app(GenerateEventDueItems::class)->handle($event);

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->paid_amount)->toBe('0.00');
    expect($dueItem->paymentAllocations)->toBeEmpty();
    expect(Payment::count())->toBe(0);
    expect(Receipt::count())->toBe(0);
});
