<?php

use App\Jobs\DeliverPaymentReminderEmail;
use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(LazilyRefreshDatabase::class);

function schedulableReminder(): PaymentReminder
{
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create(['email' => 'guardian@example.test']);
    $guardian->forceFill(['payment_reminder_email_opt_in_at' => now()->subMinute(), 'payment_reminder_email_verified_at' => now()])->save();
    $student = Student::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $due = StudentDueItem::factory()->for($student)->for($year)->for(FeeCategory::factory())->create(['net_amount' => 25, 'paid_amount' => 0, 'balance_amount' => 25, 'due_date' => '2026-12-01']);

    return PaymentReminder::factory()->for($family)->for($guardian)->create(['due_item_ids' => [$due->id]]);
}

it('schedules one eligible reminder attempt and remains idempotent within cooldown', function () {
    Bus::fake();
    schedulableReminder();

    $this->artisan('reminders:schedule-delivery')->assertSuccessful();
    $this->artisan('reminders:schedule-delivery')->assertSuccessful();

    expect(PaymentReminderDeliveryAttempt::count())->toBe(1);
    Bus::assertDispatchedTimes(DeliverPaymentReminderEmail::class, 1);
});

it('supports dry run and bounded batch limits', function () {
    Bus::fake();
    schedulableReminder();
    schedulableReminder();

    $this->artisan('reminders:schedule-delivery --dry-run --limit=1')->assertSuccessful();
    expect(PaymentReminderDeliveryAttempt::count())->toBe(0);
    Bus::assertNothingDispatched();

    schedulableReminder();
    $this->artisan('reminders:schedule-delivery --limit=3')->assertSuccessful();
    expect(PaymentReminderDeliveryAttempt::count())->toBe(1);
});

it('skips unverified guardians and multi-student reminders', function () {
    Bus::fake();
    $unverified = schedulableReminder();
    $unverified->guardian->forceFill(['payment_reminder_email_verified_at' => null])->save();
    $multi = schedulableReminder();
    $multi->update(['due_item_ids' => [$multi->due_item_ids[0], 999999]]);

    $this->artisan('reminders:schedule-delivery')->assertSuccessful();

    expect(PaymentReminderDeliveryAttempt::count())->toBe(0);
    Bus::assertNothingDispatched();
});
