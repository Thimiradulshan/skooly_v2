<?php

use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\DueItemDiscount;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

it('supports recurring and non-recurring fee categories', function () {
    $recurringCategory = FeeCategory::factory()->create(['is_recurring' => true]);
    $nonRecurringCategory = FeeCategory::factory()->create(['is_recurring' => false]);

    expect($recurringCategory->is_recurring)->toBeTrue();
    expect($nonRecurringCategory->is_recurring)->toBeFalse();
});

it('associates a fee structure with its category grade and academic year', function () {
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $feeStructure = FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($academicYear)
        ->create();

    expect($feeStructure->feeCategory->is($feeCategory))->toBeTrue();
    expect($feeStructure->grade->is($grade))->toBeTrue();
    expect($feeStructure->academicYear->is($academicYear))->toBeTrue();
});

it('prevents duplicate fee structures in the same academic year and frequency', function () {
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($academicYear)
        ->create(['frequency' => 'monthly']);

    expect(fn () => FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($academicYear)
        ->create(['frequency' => 'monthly']))
        ->toThrow(QueryException::class);
});

it('versions fee structures by academic year', function () {
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $firstAcademicYear = AcademicYear::factory()->create();
    $secondAcademicYear = AcademicYear::factory()->create();
    $firstStructure = FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($firstAcademicYear)
        ->create(['frequency' => 'yearly']);
    $secondStructure = FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($secondAcademicYear)
        ->create(['frequency' => 'yearly']);

    expect($firstStructure->academicYear->is($firstAcademicYear))->toBeTrue();
    expect($secondStructure->academicYear->is($secondAcademicYear))->toBeTrue();
});

it('does not rewrite a due item when its fee structure amount changes', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $feeStructure = FeeStructure::factory()
        ->for($feeCategory)
        ->for($grade)
        ->for($academicYear)
        ->create(['amount' => 100, 'frequency' => 'yearly']);
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for($academicYear)
        ->for($feeCategory)
        ->for($feeStructure)
        ->create([
            'original_amount' => 100,
            'net_amount' => 100,
        ]);

    $feeStructure->update(['amount' => 150]);

    expect($dueItem->refresh()->original_amount)->toBe('100.00');
    expect($dueItem->feeStructure->amount)->toBe('150.00');
});

it('stores a due item for one student with its amount snapshot and description', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for($academicYear)
        ->for($feeCategory)
        ->create([
            'description' => 'Annual tuition',
            'original_amount' => 100,
            'discount_amount' => 15,
            'net_amount' => 85,
            'balance_amount' => 85,
        ]);

    expect($dueItem->student->is($student))->toBeTrue();
    expect($dueItem->description)->toBe('Annual tuition');
    expect($dueItem->original_amount)->toBe('100.00');
    expect($dueItem->discount_amount)->toBe('15.00');
    expect($dueItem->net_amount)->toBe('85.00');
});

it('keeps due items per student in the same family', function () {
    $family = Family::factory()->create();
    $firstStudent = Student::factory()->for($family)->create();
    $secondStudent = Student::factory()->for($family)->create();
    $academicYear = AcademicYear::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $firstDueItem = StudentDueItem::factory()->for($firstStudent)->for($academicYear)->for($feeCategory)->create();
    $secondDueItem = StudentDueItem::factory()->for($secondStudent)->for($academicYear)->for($feeCategory)->create();

    expect($firstDueItem->student->is($firstStudent))->toBeTrue();
    expect($secondDueItem->student->is($secondStudent))->toBeTrue();
    expect($firstStudent->studentDueItems)->toHaveCount(1);
    expect($secondStudent->studentDueItems)->toHaveCount(1);
});

it('associates a discount with one student and one fee category', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $discount = Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create();

    expect($discount->student->is($student))->toBeTrue();
    expect($discount->feeCategory->is($feeCategory))->toBeTrue();
});

it('does not automatically apply a discount to a due item', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    Discount::factory()->for($student)->for($feeCategory, 'feeCategory')->create();
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for($academicYear)
        ->for($feeCategory)
        ->create();

    expect($dueItem->discount_amount)->toBe('0.00');
    expect($dueItem->dueItemDiscounts)->toBeEmpty();
});

it('snapshots an applied discount on a due item', function () {
    $student = Student::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $discount = Discount::factory()
        ->for($student)
        ->for($feeCategory, 'feeCategory')
        ->create(['type' => 'scholarship', 'value' => 10, 'value_type' => 'amount']);
    $dueItem = StudentDueItem::factory()->for($student)->for($academicYear)->for($feeCategory)->create();
    $dueItemDiscount = DueItemDiscount::factory()
        ->for($dueItem, 'studentDueItem')
        ->for($discount)
        ->create([
            'type' => $discount->type,
            'value' => $discount->value,
            'value_type' => $discount->value_type,
            'amount_applied' => 10,
        ]);

    $discount->update(['value' => 20]);

    expect($dueItemDiscount->studentDueItem->is($dueItem))->toBeTrue();
    expect($dueItemDiscount->discount->is($discount))->toBeTrue();
    expect($dueItemDiscount->refresh()->value)->toBe('10.00');
    expect($dueItemDiscount->amount_applied)->toBe('10.00');
});

it('represents an opt-in student fee subscription', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create(['name' => 'Transport']);
    $academicYear = AcademicYear::factory()->create();
    $subscription = StudentFeeSubscription::factory()
        ->for($student)
        ->for($feeCategory)
        ->for($academicYear)
        ->create();

    expect($subscription->student->is($student))->toBeTrue();
    expect($subscription->feeCategory->is($feeCategory))->toBeTrue();
    expect($subscription->academicYear->is($academicYear))->toBeTrue();
    expect($subscription->is_active)->toBeTrue();
});

it('prevents duplicate student fee subscriptions in an academic year', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    StudentFeeSubscription::factory()->for($student)->for($feeCategory)->for($academicYear)->create();

    expect(fn () => StudentFeeSubscription::factory()->for($student)->for($feeCategory)->for($academicYear)->create())
        ->toThrow(QueryException::class);
});

it('creates payment and payment allocation tables in Phase 6', function () {
    expect(Schema::hasTable('payments'))->toBeTrue();
    expect(Schema::hasTable('payment_allocations'))->toBeTrue();
});
