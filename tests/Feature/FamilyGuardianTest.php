<?php

use App\Models\Family;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

it('allows a family to have multiple guardians', function () {
    $family = Family::factory()->create();
    Guardian::factory()->count(2)->recycle($family)->create();

    expect($family->guardians)->toHaveCount(2);
});

it('associates each guardian with one family', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();

    expect($guardian->family->is($family))->toBeTrue();
});

it('enables combined billing by default', function () {
    $family = Family::factory()->create()->refresh();

    expect($family->combined_billing_enabled)->toBeTrue();
});

it('defaults payment reminder email consent timestamps to null and persists them', function () {
    $guardian = Guardian::factory()->create();

    expect($guardian->payment_reminder_email_opt_in_at)->toBeNull();
    expect($guardian->payment_reminder_email_verified_at)->toBeNull();
    expect($guardian->payment_reminder_email_opt_out_at)->toBeNull();

    $optedInAt = now()->startOfSecond();
    $verifiedAt = $optedInAt->copy()->addMinute();
    $optedOutAt = $verifiedAt->copy()->addMinute();

    $guardian->forceFill([
        'payment_reminder_email_opt_in_at' => $optedInAt,
        'payment_reminder_email_verified_at' => $verifiedAt,
        'payment_reminder_email_opt_out_at' => $optedOutAt,
    ])->save();

    $guardian->refresh();

    expect($guardian->payment_reminder_email_opt_in_at)->toEqual($optedInAt);
    expect($guardian->payment_reminder_email_verified_at)->toEqual($verifiedAt);
    expect($guardian->payment_reminder_email_opt_out_at)->toEqual($optedOutAt);
});

it('restricts deleting a family with guardians', function () {
    $family = Family::factory()->create();
    Guardian::factory()->for($family)->create();

    expect(fn () => $family->delete())->toThrow(QueryException::class);

    $this->assertModelExists($family);
});

it('creates the guardian student table', function () {
    expect(Schema::hasTable('guardian_student'))->toBeTrue();
});

it('links guardians to specific students', function () {
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create();
    $linkedStudent = Student::factory()->for($family)->create();
    $unlinkedStudent = Student::factory()->for($family)->create();

    $guardian->students()->attach($linkedStudent);

    expect($guardian->students->sole()->is($linkedStudent))->toBeTrue();
    expect($linkedStudent->guardians->sole()->is($guardian))->toBeTrue();
    expect($unlinkedStudent->guardians)->toBeEmpty();
});
