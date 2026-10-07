<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    setActiveAcademicYear(AcademicYear::factory()->create());
});

it('denies guests the fee category index', function () {
    $this->get(route('fee-categories.index'))->assertRedirect(route('login'));
});

it('denies teacher and accountant the fee category index', function () {
    $this->actingAs(userWithRole(Role::TEACHER))->get(route('fee-categories.index'))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->get(route('fee-categories.index'))->assertForbidden();
});

it('lets an admin browse fee categories', function () {
    FeeCategory::factory()->create(['name' => 'Tuition']);

    $this->actingAs(adminUser())
        ->get(route('fee-categories.index'))
        ->assertOk()
        ->assertSee('Tuition');
});

it('lets an admin create a fee category', function () {
    $this->actingAs(adminUser())
        ->post(route('fee-categories.store'), [
            'name' => 'Transport',
            'is_opt_in' => '1',
        ])
        ->assertRedirect(route('fee-categories.index'));

    $feeCategory = FeeCategory::query()->sole();

    expect($feeCategory->name)->toBe('Transport');
    expect($feeCategory->is_opt_in)->toBeTrue();
    expect($feeCategory->is_recurring)->toBeFalse();
});

it('rejects a duplicate fee category name', function () {
    FeeCategory::factory()->create(['name' => 'Tuition']);

    $this->actingAs(adminUser())
        ->post(route('fee-categories.store'), ['name' => 'Tuition'])
        ->assertSessionHasErrors('name');

    expect(FeeCategory::count())->toBe(1);
});

it('lets an admin update a fee category', function () {
    $feeCategory = FeeCategory::factory()->create(['name' => 'Tuition', 'is_recurring' => false]);

    $this->actingAs(adminUser())
        ->put(route('fee-categories.update', $feeCategory), [
            'name' => 'School Tuition',
            'is_recurring' => '1',
        ])
        ->assertRedirect(route('fee-categories.index'));

    $feeCategory->refresh();

    expect($feeCategory->name)->toBe('School Tuition');
    expect($feeCategory->is_recurring)->toBeTrue();
});

it('lets an admin create a fee structure without creating due items', function () {
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);

    $this->actingAs(adminUser())->get(route('fee-structures.create'))->assertOk();

    $this->actingAs(adminUser())->post(route('fee-structures.store'), [
        'fee_category_id' => $feeCategory->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $academicYear->id,
        'amount' => '150.50',
        'frequency' => 'monthly',
    ])->assertRedirect(route('fee-structures.index'));

    $feeStructure = FeeStructure::query()->sole();

    expect($feeStructure->amount)->toBe('150.50');
    expect($feeStructure->frequency)->toBe('monthly');
    expect(StudentDueItem::count())->toBe(0);
});

it('rejects a duplicate fee structure', function () {
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $payload = [
        'fee_category_id' => $feeCategory->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $academicYear->id,
        'amount' => '100.00',
        'frequency' => 'monthly',
    ];
    FeeStructure::factory()->create([
        'fee_category_id' => $feeCategory->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $academicYear->id,
        'frequency' => 'monthly',
    ]);

    $this->actingAs(adminUser())
        ->post(route('fee-structures.store'), $payload)
        ->assertSessionHasErrors('fee_category_id');

    expect(FeeStructure::count())->toBe(1);
});

it('lets an admin apply a discount to only the selected student and category', function () {
    $student = Student::factory()->create();
    $otherStudent = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $otherCategory = FeeCategory::factory()->create();

    $this->actingAs(adminUser())->get(route('students.discounts.create', $student))->assertOk();

    $this->actingAs(adminUser())->post(route('students.discounts.store', $student), [
        'fee_category_id' => $feeCategory->id,
        'type' => 'scholarship',
        'value' => 25,
        'value_type' => 'amount',
        'is_active' => '1',
    ])->assertRedirect(route('families.show', $student->family_id));

    $discount = Discount::query()->sole();

    expect($discount->student_id)->toBe($student->id);
    expect($discount->applies_to_fee_category_id)->toBe($feeCategory->id);
    expect($discount->value)->toBe('25.00');
    expect($otherStudent->discounts)->toBeEmpty();
    expect($otherCategory->discounts)->toBeEmpty();
    expect(AuditLog::where('action', AuditLog::ACTION_DISCOUNT_APPLIED)->count())->toBe(1);
});

it('never creates a sibling discount automatically', function () {
    $family = Family::factory()->create();
    $firstStudent = Student::factory()->for($family)->create();
    $secondStudent = Student::factory()->for($family)->create();
    $feeCategory = FeeCategory::factory()->create();

    $this->actingAs(adminUser())->post(route('students.discounts.store', $firstStudent), [
        'fee_category_id' => $feeCategory->id,
        'type' => 'scholarship',
        'value' => 25,
        'value_type' => 'amount',
    ]);

    expect(Discount::count())->toBe(1);
    expect($secondStudent->discounts)->toBeEmpty();
    expect(Discount::where('type', 'sibling')->count())->toBe(0);
});

it('does not modify existing due items when a discount is applied', function () {
    $student = Student::factory()->create();
    $feeCategory = FeeCategory::factory()->create();
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for(AcademicYear::factory())
        ->for($feeCategory)
        ->create([
            'original_amount' => 100,
            'discount_amount' => 0,
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
        ]);
    $before = $dueItem->only(['original_amount', 'discount_amount', 'net_amount', 'balance_amount']);

    $this->actingAs(adminUser())->post(route('students.discounts.store', $student), [
        'fee_category_id' => $feeCategory->id,
        'type' => 'scholarship',
        'value' => 25,
        'value_type' => 'amount',
    ]);

    expect($dueItem->refresh()->only(array_keys($before)))->toBe($before);
});

it('lets an admin subscribe a student to an opt-in category', function () {
    $student = Student::factory()->create();
    $optInCategory = FeeCategory::factory()->create(['name' => 'Transport', 'is_opt_in' => true]);
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);

    $this->actingAs(adminUser())->get(route('students.fee-subscriptions.create', $student))->assertOk();

    $this->actingAs(adminUser())->post(route('students.fee-subscriptions.store', $student), [
        'fee_category_id' => $optInCategory->id,
        'academic_year_id' => $academicYear->id,
        'is_active' => '1',
    ])->assertRedirect(route('families.show', $student->family_id));

    $subscription = StudentFeeSubscription::query()->sole();

    expect($subscription->student_id)->toBe($student->id);
    expect($subscription->fee_category_id)->toBe($optInCategory->id);
    expect($subscription->is_active)->toBeTrue();
    expect(StudentDueItem::count())->toBe(0);
});

it('rejects subscribing a student to a non opt-in category', function () {
    $student = Student::factory()->create();
    $standardCategory = FeeCategory::factory()->create(['is_opt_in' => false]);
    $academicYear = AcademicYear::factory()->create();

    $this->actingAs(adminUser())
        ->post(route('students.fee-subscriptions.store', $student), [
            'fee_category_id' => $standardCategory->id,
            'academic_year_id' => $academicYear->id,
        ])
        ->assertSessionHasErrors('fee_category_id');

    expect(StudentFeeSubscription::count())->toBe(0);
});

it('exposes no delete routes for fee categories, structures, discounts, or subscriptions', function () {
    expect(Route::has('fee-categories.destroy'))->toBeFalse();
    expect(Route::has('fee-structures.destroy'))->toBeFalse();
    expect(Route::has('students.discounts.destroy'))->toBeFalse();
    expect(Route::has('students.fee-subscriptions.destroy'))->toBeFalse();

    $feeCategory = FeeCategory::factory()->create();
    $this->actingAs(adminUser())->delete(route('fee-categories.index'))->assertMethodNotAllowed();
    $this->actingAs(adminUser())->delete(route('fee-categories.edit', $feeCategory))->assertMethodNotAllowed();
});

it('keeps fee structure versioned by academic year', function () {
    $feeCategory = FeeCategory::factory()->create();
    $grade = Grade::factory()->create();
    $firstYear = AcademicYear::factory()->create();
    $secondYear = AcademicYear::factory()->create();
    setActiveAcademicYear($firstYear);

    $this->actingAs(adminUser())->post(route('fee-structures.store'), [
        'fee_category_id' => $feeCategory->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $firstYear->id,
        'amount' => '100.00',
        'frequency' => 'monthly',
    ]);

    setActiveAcademicYear($secondYear);

    $this->actingAs(adminUser())->post(route('fee-structures.store'), [
        'fee_category_id' => $feeCategory->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $secondYear->id,
        'amount' => '120.00',
        'frequency' => 'monthly',
    ])->assertSessionHasNoErrors();

    expect(FeeStructure::count())->toBe(2);
    expect(FeeStructure::where('academic_year_id', $firstYear->id)->sole()->amount)->toBe('100.00');
    expect(FeeStructure::where('academic_year_id', $secondYear->id)->sole()->amount)->toBe('120.00');
});
