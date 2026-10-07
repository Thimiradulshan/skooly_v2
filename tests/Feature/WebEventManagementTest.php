<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\EventParticipation;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    setActiveAcademicYear(AcademicYear::factory()->create());
});

function enrolledForEvent(AcademicYear $academicYear, Grade $grade): Student
{
    $student = Student::factory()->for(Family::factory())->create();
    $section = Section::factory()->for($grade)->create();

    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create();

    return $student;
}

function eventPayload(AcademicYear $academicYear, FeeCategory $feeCategory, array $overrides = []): array
{
    return array_merge([
        'academic_year_id' => $academicYear->id,
        'fee_category_id' => $feeCategory->id,
        'name' => 'Sports Day',
        'event_date' => '2026-10-15',
        'description' => 'Annual sports event',
        'is_mandatory' => '1',
    ], $overrides);
}

it('denies guests the events index', function () {
    $this->get(route('events.index'))->assertRedirect(route('login'));
});

it('denies teachers and accountants the events index', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('events.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('events.index'))->assertForbidden();
});

it('lets an admin access the event index and create page', function () {
    $this->actingAs(adminUser())->get(route('events.index'))->assertOk();
    $this->actingAs(adminUser())->get(route('events.create'))->assertOk();
});

it('lets an admin create an event without creating dues or payment records', function () {
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $feeCategory = FeeCategory::factory()->create();

    $this->actingAs(adminUser())
        ->post(route('events.store'), eventPayload($academicYear, $feeCategory))
        ->assertRedirect();

    $event = Event::query()->sole();

    expect($event->academic_year_id)->toBe($academicYear->id);
    expect($event->fee_category_id)->toBe($feeCategory->id);
    expect($event->name)->toBe('Sports Day');
    expect($event->is_mandatory)->toBeTrue();
    expect($event->confirmed_at)->toBeNull();
    expect(StudentDueItem::count())->toBe(0);
    expect(Payment::count())->toBe(0);
    expect(Receipt::count())->toBe(0);
    expect(PaymentAllocation::count())->toBe(0);
});

it('lets an admin view an event show page', function () {
    $event = Event::factory()->create(['name' => 'Science Fair']);

    $this->actingAs(adminUser())
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Science Fair')
        ->assertSee('Add charge')
        ->assertSee('Manage participation');
});

it('lets an admin update an event using existing fields', function () {
    $event = Event::factory()->create([
        'name' => 'Old Name',
        'is_mandatory' => true,
    ]);
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $feeCategory = FeeCategory::factory()->create();

    $this->actingAs(adminUser())
        ->put(route('events.update', $event), eventPayload($academicYear, $feeCategory, [
            'name' => 'New Name',
            'event_date' => '2026-11-20',
            'description' => 'Updated description',
            'is_mandatory' => '0',
        ]))
        ->assertRedirect(route('events.show', $event));

    $event->refresh();

    expect($event->name)->toBe('New Name');
    expect($event->event_date->toDateString())->toBe('2026-11-20');
    expect($event->description)->toBe('Updated description');
    expect($event->is_mandatory)->toBeFalse();
    expect(StudentDueItem::count())->toBe(0);
});

it('lets an admin add a grade specific event charge', function () {
    $event = Event::factory()->create();
    setActiveAcademicYear($event->academicYear);
    $grade = Grade::factory()->create();

    $this->actingAs(adminUser())
        ->get(route('events.charges.create', $event))
        ->assertOk();

    $this->actingAs(adminUser())
        ->post(route('events.charges.store', $event), [
            'grade_id' => $grade->id,
            'amount' => '35.50',
        ])
        ->assertRedirect(route('events.show', $event));

    $charge = EventCharge::query()->sole();

    expect($charge->event_id)->toBe($event->id);
    expect($charge->grade_id)->toBe($grade->id);
    expect($charge->amount)->toBe('35.50');
    expect(StudentDueItem::count())->toBe(0);
});

it('rejects negative and duplicate event charges', function () {
    $event = Event::factory()->create();
    $grade = Grade::factory()->create();

    $this->actingAs(adminUser())
        ->post(route('events.charges.store', $event), [
            'grade_id' => $grade->id,
            'amount' => '-1.00',
        ])
        ->assertSessionHasErrors('amount');

    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 20]);

    $this->actingAs(adminUser())
        ->post(route('events.charges.store', $event), [
            'grade_id' => $grade->id,
            'amount' => '30.00',
        ])
        ->assertSessionHasErrors('grade_id');

    expect(EventCharge::count())->toBe(1);
});

it('lets an admin create and update selected student participation safely', function () {
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    setActiveAcademicYear($academicYear);
    $grade = Grade::factory()->create();
    $student = enrolledForEvent($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create(['is_mandatory' => false]);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 25]);

    $this->actingAs(adminUser())
        ->get(route('events.participation.create', $event))
        ->assertOk()
        ->assertSee($student->name);

    $this->actingAs(adminUser())
        ->post(route('events.participation.store', $event), [
            'student_ids' => [$student->id],
            'status' => EventParticipation::STATUS_OPTED_IN,
        ])
        ->assertRedirect(route('events.show', $event));

    $this->actingAs(adminUser())
        ->post(route('events.participation.store', $event), [
            'student_ids' => [$student->id],
            'status' => EventParticipation::STATUS_OPTED_OUT,
        ])
        ->assertRedirect(route('events.show', $event));

    $participation = EventParticipation::query()->sole();

    expect(EventParticipation::count())->toBe(1);
    expect($participation->student_id)->toBe($student->id);
    expect($participation->status)->toBe(EventParticipation::STATUS_OPTED_OUT);
    expect(StudentDueItem::count())->toBe(0);
});

it('uses the existing due generation flow after web event setup', function () {
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $grade = Grade::factory()->create();
    $student = enrolledForEvent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create();

    $this->actingAs(adminUser())
        ->post(route('events.store'), eventPayload($academicYear, $feeCategory, [
            'name' => 'Web Event',
        ]));

    $event = Event::query()->sole();

    $this->actingAs(adminUser())
        ->post(route('events.charges.store', $event), [
            'grade_id' => $grade->id,
            'amount' => '45.00',
        ]);

    $this->actingAs(adminUser())
        ->post(route('due-generation.events.store'), ['event_id' => $event->id])
        ->assertRedirect(route('due-generation.events.create'));

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->student_id)->toBe($student->id);
    expect($dueItem->description)->toBe('Event: Web Event');
    expect($dueItem->net_amount)->toBe('45.00');
});

it('adds no event, charge, or participation delete route', function () {
    expect(Route::has('events.destroy'))->toBeFalse();
    expect(Route::has('events.charges.destroy'))->toBeFalse();
    expect(Route::has('events.participation.destroy'))->toBeFalse();

    $event = Event::factory()->create();

    $this->actingAs(adminUser())
        ->delete(route('events.show', $event))
        ->assertMethodNotAllowed();
});
