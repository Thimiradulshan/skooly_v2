<?php

use App\Actions\Fees\GenerateRecurringDueItems;
use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function enrolledStudent(AcademicYear $academicYear, Grade $grade, ?Family $family = null): Student
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

it('generates due items for enrolled students from recurring fee structures', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    $feeStructure = FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($academicYear)
        ->create(['amount' => 100, 'frequency' => 'monthly']);

    $created = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    $dueItem = StudentDueItem::query()->sole();

    expect($created)->toBe(1);
    expect($dueItem->student_id)->toBe($student->id);
    expect($dueItem->fee_category_id)->toBe($feeCategory->id);
    expect($dueItem->fee_structure_id)->toBe($feeStructure->id);
    expect($dueItem->original_amount)->toBe('100.00');
    expect($dueItem->discount_amount)->toBe('0.00');
    expect($dueItem->net_amount)->toBe('100.00');
    expect($dueItem->paid_amount)->toBe('0.00');
    expect($dueItem->balance_amount)->toBe('100.00');
    expect($dueItem->status)->toBe(StudentDueItem::STATUS_UNPAID);
});

it('does not generate due items for non-recurring fee categories', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => false]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['frequency' => 'monthly']);

    $created = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    expect($created)->toBe(0);
    expect(StudentDueItem::count())->toBe(0);
});

it('does not generate due items for students not enrolled in that academic year or grade', function () {
    $academicYear = AcademicYear::factory()->create();
    $otherAcademicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $otherGrade = Grade::factory()->create();
    $notEnrolled = Student::factory()->create();
    $otherYearStudent = enrolledStudent($otherAcademicYear, $grade);
    $otherGradeStudent = enrolledStudent($academicYear, $otherGrade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['frequency' => 'monthly']);

    $created = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    expect($created)->toBe(0);
    expect(StudentDueItem::count())->toBe(0);
    expect($notEnrolled->exists())->toBeTrue();
    expect($otherYearStudent->exists())->toBeTrue();
    expect($otherGradeStudent->exists())->toBeTrue();
});

it('keeps generated due amounts unchanged when the fee structure later changes', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    $feeStructure = FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($academicYear)
        ->create(['amount' => 100, 'frequency' => 'monthly']);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    $feeStructure->update(['amount' => 250]);

    expect(StudentDueItem::query()->sole()->original_amount)->toBe('100.00');
    expect(StudentDueItem::query()->sole()->net_amount)->toBe('100.00');
});

it('prevents duplicate due items for the same cycle', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['frequency' => 'monthly']);

    $first = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');
    $second = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');
    $nextCycle = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-11-05', '2026-11');

    expect($first)->toBe(1);
    expect($second)->toBe(0);
    expect($nextCycle)->toBe(1);
    expect(StudentDueItem::count())->toBe(2);
});

it('applies and snapshots a fixed amount discount', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 100, 'frequency' => 'monthly']);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'type' => 'scholarship',
        'value' => 15,
        'value_type' => 'amount',
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    $dueItem = StudentDueItem::query()->sole();
    $discountSnapshot = $dueItem->dueItemDiscounts()->sole();

    expect($dueItem->original_amount)->toBe('100.00');
    expect($dueItem->discount_amount)->toBe('15.00');
    expect($dueItem->net_amount)->toBe('85.00');
    expect($dueItem->balance_amount)->toBe('85.00');
    expect($discountSnapshot->amount_applied)->toBe('15.00');
    expect($discountSnapshot->value_type)->toBe('amount');
    expect($discountSnapshot->type)->toBe('scholarship');
});

it('applies a percentage discount', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 200, 'frequency' => 'monthly']);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'value' => 10,
        'value_type' => 'percentage',
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->discount_amount)->toBe('20.00');
    expect($dueItem->net_amount)->toBe('180.00');
});

it('never lets a discount make the net amount negative', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 50, 'frequency' => 'monthly']);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'value' => 500,
        'value_type' => 'amount',
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->discount_amount)->toBe('50.00');
    expect($dueItem->net_amount)->toBe('0.00');
    expect($dueItem->balance_amount)->toBe('0.00');
    expect($dueItem->dueItemDiscounts()->sole()->amount_applied)->toBe('50.00');
});

it('ignores an inactive discount', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 100, 'frequency' => 'monthly']);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'value' => 25,
        'value_type' => 'amount',
        'is_active' => false,
        'starts_on' => null,
        'ends_on' => null,
    ]);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    $dueItem = StudentDueItem::query()->sole();

    expect($dueItem->discount_amount)->toBe('0.00');
    expect($dueItem->dueItemDiscounts)->toBeEmpty();
});

it('ignores a discount outside its date range', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 100, 'frequency' => 'monthly']);
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create([
        'value' => 25,
        'value_type' => 'amount',
        'starts_on' => '2026-11-01',
    ]);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    expect(StudentDueItem::query()->sole()->discount_amount)->toBe('0.00');
});

it('generates an opt-in fee only for students with an active subscription', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $subscribedStudent = enrolledStudent($academicYear, $grade);
    $unsubscribedStudent = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true, 'is_opt_in' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 30, 'frequency' => 'monthly']);
    StudentFeeSubscription::factory()
        ->for($subscribedStudent)
        ->for($feeCategory)
        ->for($academicYear)
        ->create(['is_active' => true]);

    $created = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    expect($created)->toBe(1);
    expect(StudentDueItem::query()->sole()->student_id)->toBe($subscribedStudent->id);
    expect($unsubscribedStudent->studentDueItems()->count())->toBe(0);
});

it('skips an opt-in fee when the subscription is inactive', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    $student = enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true, 'is_opt_in' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 30, 'frequency' => 'monthly']);
    StudentFeeSubscription::factory()
        ->for($student)
        ->for($feeCategory)
        ->for($academicYear)
        ->create(['is_active' => false]);

    $created = app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    expect($created)->toBe(0);
    expect(StudentDueItem::count())->toBe(0);
});

it('does not create payments or receipts during generation', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create();
    enrolledStudent($academicYear, $grade);
    $feeCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    FeeStructure::factory()->for($feeCategory)->for($grade)->for($academicYear)->create(['amount' => 100, 'frequency' => 'monthly']);

    app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-05', '2026-10');

    expect(StudentDueItem::query()->sole()->paid_amount)->toBe('0.00');
    expect(StudentDueItem::query()->sole()->paymentAllocations)->toBeEmpty();
    expect(Payment::count())->toBe(0);
    expect(Receipt::count())->toBe(0);
});
