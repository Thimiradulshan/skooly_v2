<?php

use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\FeeCategory;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentFeeSubscription;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('deactivates a discount without changing its historical details', function () {
    $student = Student::factory()->create();
    $discount = Discount::factory()->for($student)->for(FeeCategory::factory(), 'feeCategory')->create([
        'value' => 25,
        'is_active' => true,
    ]);

    $this->actingAs(adminUser())->post(route('discounts.deactivate', $discount))
        ->assertRedirect(route('students.discounts.index', $student));

    expect($discount->refresh()->is_active)->toBeFalse();
    expect($discount->value)->toBe('25.00');
});

it('ends a fee subscription on the selected date', function () {
    $student = Student::factory()->create();
    $subscription = StudentFeeSubscription::factory()
        ->for($student)
        ->for(FeeCategory::factory())
        ->for(AcademicYear::factory())
        ->create(['is_active' => true]);

    $this->actingAs(adminUser())->post(route('student-fee-subscriptions.end', $subscription), [
        'ends_on' => '2026-10-07',
    ])->assertRedirect(route('students.fee-subscriptions.index', $student));

    expect($subscription->refresh()->is_active)->toBeFalse();
    expect($subscription->ends_on->toDateString())->toBe('2026-10-07');
});

it('blocks teachers and accountants from financial lifecycle controls', function () {
    $discount = Discount::factory()->create();
    $subscription = StudentFeeSubscription::factory()->create();

    $this->actingAs(userWithRole(Role::TEACHER))->post(route('discounts.deactivate', $discount))->assertForbidden();
    $this->actingAs(userWithRole(Role::ACCOUNTANT))->post(route('student-fee-subscriptions.end', $subscription), ['ends_on' => '2026-10-07'])->assertForbidden();
});
