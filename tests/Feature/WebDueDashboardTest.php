<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
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

function enrolled(AcademicYear $academicYear, Grade $grade, ?Family $family = null): Student
{
    $student = Student::factory()->for($family ?? Family::factory())->create();
    $section = Section::factory()->for($grade)->create();

    Enrollment::factory()->for($student)->for($academicYear)->for($grade)->for($section)->create();

    return $student;
}

function recurringSetup(AcademicYear $academicYear, Grade $grade): FeeCategory
{
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create([
        'amount' => 100,
        'frequency' => 'monthly',
    ]);

    return $feeCategory;
}

it('denies guests the recurring due generation page', function () {
    $this->get(route('due-generation.recurring.create'))->assertRedirect(route('login'));
});

it('denies teacher and accountant the recurring due generation page', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('due-generation.recurring.create'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('due-generation.recurring.create'))->assertForbidden();
});

it('lets an admin open the recurring due generation page', function () {
    AcademicYear::factory()->create(['name' => '2026/2027']);

    $this->actingAs(adminUser())
        ->get(route('due-generation.recurring.create'))
        ->assertOk()
        ->assertSee('2026/2027');
});

it('generates recurring due items through the web route', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $feeCategory = recurringSetup($academicYear, $grade);
    $student = enrolled($academicYear, $grade);

    $this->actingAs(adminUser())
        ->post(route('due-generation.recurring.store'), [
            'academic_year_id' => $academicYear->id,
            'due_date' => '2026-10-05',
            'cycle_key' => '2026-10',
        ])
        ->assertRedirect(route('due-generation.recurring.create'))
        ->assertSessionHas('status');

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->student_id)->toBe($student->id);
    expect($dueItem->fee_category_id)->toBe($feeCategory->id);
    expect($dueItem->net_amount)->toBe('100.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_UNPAID);
});

it('does not duplicate recurring due items when run twice', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    recurringSetup($academicYear, $grade);
    enrolled($academicYear, $grade);
    $payload = [
        'academic_year_id' => $academicYear->id,
        'due_date' => '2026-10-05',
        'cycle_key' => '2026-10',
    ];

    $this->actingAs(adminUser())->post(route('due-generation.recurring.store'), $payload);
    $this->actingAs(adminUser())->post(route('due-generation.recurring.store'), $payload);

    expect(StudentDueItem::count())->toBe(1);
});

it('lets an admin open the event due generation page', function () {
    $this->actingAs(adminUser())->get(route('due-generation.events.create'))->assertOk();
    $this->actingAs(adminUser())->get(route('due-generation.events.create'))->assertSee('No events exist yet');
});

it('generates event due items through the web route', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolled($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create(['name' => 'Sports Day']);
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 25]);

    $this->actingAs(adminUser())
        ->post(route('due-generation.events.store'), ['event_id' => $event->id])
        ->assertRedirect(route('due-generation.events.create'))
        ->assertSessionHas('status');

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->student_id)->toBe($student->id);
    expect($dueItem->net_amount)->toBe('25.00');
    expect($dueItem->fee_structure_id)->toBeNull();
});

it('does not duplicate event due items when run twice', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    enrolled($academicYear, $grade);
    $event = Event::factory()->for($academicYear)->create();
    EventCharge::factory()->for($event)->for($grade)->create(['amount' => 25]);

    $this->actingAs(adminUser())->post(route('due-generation.events.store'), ['event_id' => $event->id]);
    $this->actingAs(adminUser())->post(route('due-generation.events.store'), ['event_id' => $event->id]);

    expect(StudentDueItem::count())->toBe(1);
});

it('creates no payment receipt or allocation records during due generation', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    recurringSetup($academicYear, $grade);
    enrolled($academicYear, $grade);

    $this->actingAs(adminUser())->post(route('due-generation.recurring.store'), [
        'academic_year_id' => $academicYear->id,
        'due_date' => '2026-10-05',
    ]);

    expect(Payment::count())->toBe(0);
    expect(Receipt::count())->toBe(0);
    expect(PaymentAllocation::count())->toBe(0);
});

it('lets an admin view the dues dashboard', function () {
    $this->actingAs(adminUser())->get(route('dues-dashboard.index'))->assertOk()->assertSee('Outstanding balance');
});

it('shows dashboard totals from student due item snapshots', function () {
    $academicYear = AcademicYear::factory()->create();
    $family = Family::factory()->create(['family_code' => 'FAM-DASH']);
    $student = Student::factory()->for($family)->create(['name' => 'Dashboard Child']);
    $feeCategory = FeeCategory::factory()->create(['name' => 'Tuition']);
    StudentDueItem::factory()->for($student)->for($academicYear)->for($feeCategory)->create([
        'original_amount' => 200,
        'net_amount' => 200,
        'paid_amount' => 50,
        'balance_amount' => 150,
        'status' => StudentDueItem::STATUS_PARTIALLY_PAID,
    ]);

    $this->actingAs(adminUser())->get(route('dues-dashboard.index'))
        ->assertOk()
        ->assertSee('200.00')
        ->assertSee('150.00')
        ->assertSee('FAM-DASH')
        ->assertSee('Dashboard Child')
        ->assertSee('Tuition');
});

it('filters the dashboard by academic year', function () {
    $firstYear = AcademicYear::factory()->create();
    $otherYear = AcademicYear::factory()->create();
    $family = Family::factory()->create();
    $student = Student::factory()->for($family)->create();
    $feeCategory = FeeCategory::factory()->create();
    StudentDueItem::factory()->for($student)->for($firstYear)->for($feeCategory)->create([
        'net_amount' => 10, 'balance_amount' => 10,
    ]);
    StudentDueItem::factory()->for($student)->for($otherYear)->for($feeCategory)->create([
        'net_amount' => 999, 'balance_amount' => 999,
    ]);

    $this->actingAs(adminUser())
        ->get(route('dues-dashboard.index', ['academic_year_id' => $firstYear->id]))
        ->assertOk()
        ->assertSee('10.00')
        ->assertDontSee('999.00');
});

it('filters the dashboard by fee category', function () {
    $academicYear = AcademicYear::factory()->create();
    $student = Student::factory()->create();
    $tuition = FeeCategory::factory()->create(['name' => 'Tuition']);
    $transport = FeeCategory::factory()->create(['name' => 'Transport']);
    StudentDueItem::factory()->for($student)->for($academicYear)->for($tuition)->create([
        'net_amount' => 10, 'balance_amount' => 10,
    ]);
    StudentDueItem::factory()->for($student)->for($academicYear)->for($transport)->create([
        'net_amount' => 555, 'balance_amount' => 555,
    ]);

    $this->actingAs(adminUser())
        ->get(route('dues-dashboard.index', ['fee_category_id' => $tuition->id]))
        ->assertOk()
        ->assertSee('10.00')
        ->assertDontSee('555.00');
});

it('filters the dashboard by family', function () {
    $academicYear = AcademicYear::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $firstFamily = Family::factory()->create();
    $secondFamily = Family::factory()->create();
    $firstStudent = Student::factory()->for($firstFamily)->create();
    StudentDueItem::factory()->for($firstStudent)->for($academicYear)->for($feeCategory)->create([
        'net_amount' => 10, 'balance_amount' => 10,
    ]);
    $secondStudent = Student::factory()->for($secondFamily)->create();
    StudentDueItem::factory()->for($secondStudent)->for($academicYear)->for($feeCategory)->create([
        'net_amount' => 777, 'balance_amount' => 777,
    ]);

    $this->actingAs(adminUser())
        ->get(route('dues-dashboard.index', ['family_id' => $firstFamily->id]))
        ->assertOk()
        ->assertSee('10.00')
        ->assertDontSee('777.00');
});

it('rejects a grade filter without an academic year', function () {
    $this->actingAs(adminUser())
        ->get(route('dues-dashboard.index', ['grade_id' => Grade::factory()->create()->id]))
        ->assertSessionHasErrors('grade_id');
});

it('rejects a section filter without an academic year', function () {
    $this->actingAs(adminUser())
        ->get(route('dues-dashboard.index', ['section_id' => Section::factory()->create()->id]))
        ->assertSessionHasErrors('section_id');
});

it('keeps due dashboard routes while payment collection has no destructive route', function () {
    expect(Route::has('dues-dashboard.index'))->toBeTrue();
    expect(Route::has('due-generation.recurring.store'))->toBeTrue();
    expect(Route::has('due-generation.events.store'))->toBeTrue();
    expect(Route::has('payments.show'))->toBeTrue();
    expect(Route::has('receipts.show'))->toBeTrue();
    expect(Route::has('payments.edit'))->toBeFalse();
    expect(Route::has('payments.destroy'))->toBeFalse();
    expect(Route::has('payments.refund'))->toBeFalse();
    expect(Route::has('receipts.export'))->toBeFalse();
});

it('allows accountants to read the dues dashboard but denies teachers', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('dues-dashboard.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('dues-dashboard.index'))->assertOk();
});
