<?php

use App\Actions\Notifications\MarkPaymentReminderDeliveryAttemptFailed;
use App\Actions\Notifications\MarkPaymentReminderDeliveryAttemptTransportAccepted;
use App\Actions\Notifications\QueuePaymentReminderDeliveryAttempt;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{guardian: Guardian, reminder: PaymentReminder}
 */
function paymentReminderDeliveryAttemptFixture(): array
{
    $academicYear = AcademicYear::factory()->create();
    setActiveAcademicYear($academicYear);
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create(['email' => 'guardian@example.test']);
    $student = Student::factory()->for($family)->create();
    $guardian->students()->attach($student);
    $dueItem = StudentDueItem::factory()
        ->for($student)
        ->for($academicYear)
        ->for(FeeCategory::factory())
        ->create([
            'net_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => StudentDueItem::STATUS_UNPAID,
        ]);
    $guardian->forceFill([
        'payment_reminder_email_opt_in_at' => now()->subMinute(),
        'payment_reminder_email_verified_at' => now(),
    ])->save();
    $reminder = PaymentReminder::factory()
        ->for($family)
        ->for($guardian)
        ->create(['due_item_ids' => [$dueItem->id]]);

    return compact('guardian', 'reminder');
}

it('creates an auditable queued delivery attempt with an encrypted recipient snapshot', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    $attempt = (new QueuePaymentReminderDeliveryAttempt)->handle($reminder, adminUser());

    expect($attempt->payment_reminder_id)->toBe($reminder->id);
    expect($attempt->guardian_id)->toBe($guardian->id);
    expect($attempt->attempt_number)->toBe(1);
    expect($attempt->recipient_email)->toBe('guardian@example.test');
    expect($attempt->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_QUEUED);
    expect($attempt->created_at)->not->toBeNull();
    expect($attempt->toArray())->not->toHaveKey('recipient_email');
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_PAYMENT_REMINDER_DELIVERY_ATTEMPT_QUEUED,
        'auditable_id' => $attempt->id,
    ]);
});

it('assigns unique sequential attempts and preserves delivery history', function () {
    ['reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    $queueAttempt = new QueuePaymentReminderDeliveryAttempt;

    $firstAttempt = $queueAttempt->handle($reminder);
    $secondAttempt = $queueAttempt->handle($reminder);

    expect($firstAttempt->attempt_number)->toBe(1);
    expect($secondAttempt->attempt_number)->toBe(2);
    expect($reminder->refresh()->deliveryAttempts->pluck('id')->all())->toBe([$firstAttempt->id, $secondAttempt->id]);
});

it('does not queue an attempt for an ineligible reminder', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    $guardian->forceFill(['payment_reminder_email_verified_at' => null])->save();

    expect(fn () => (new QueuePaymentReminderDeliveryAttempt)->handle($reminder))
        ->toThrow(RuntimeException::class);

    expect(PaymentReminderDeliveryAttempt::query()->count())->toBe(0);
});

it('records a transport acceptance only from the queued state', function () {
    ['reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    $attempt = (new QueuePaymentReminderDeliveryAttempt)->handle($reminder);

    $acceptedAttempt = (new MarkPaymentReminderDeliveryAttemptTransportAccepted)->handle($attempt, adminUser());

    expect($acceptedAttempt->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_TRANSPORT_ACCEPTED);
    expect($acceptedAttempt->transport_accepted_at)->not->toBeNull();
    expect($acceptedAttempt->failed_at)->toBeNull();
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_PAYMENT_REMINDER_DELIVERY_ATTEMPT_TRANSPORT_ACCEPTED,
        'auditable_id' => $attempt->id,
    ]);

    expect(fn () => (new MarkPaymentReminderDeliveryAttemptFailed)->handle(
        $attempt,
        PaymentReminderDeliveryAttempt::FAILURE_TRANSPORT_UNAVAILABLE,
    ))->toThrow(RuntimeException::class);
});

it('records a sanitized failure only from the queued state', function () {
    ['reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    $attempt = (new QueuePaymentReminderDeliveryAttempt)->handle($reminder);

    $failedAttempt = (new MarkPaymentReminderDeliveryAttemptFailed)->handle(
        $attempt,
        PaymentReminderDeliveryAttempt::FAILURE_TRANSPORT_UNAVAILABLE,
        adminUser(),
    );

    expect($failedAttempt->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_FAILED);
    expect($failedAttempt->failed_at)->not->toBeNull();
    expect($failedAttempt->failure_classification)->toBe(PaymentReminderDeliveryAttempt::FAILURE_TRANSPORT_UNAVAILABLE);
    expect($failedAttempt->failure_message)->toBe('Email transport is unavailable.');
    expect($failedAttempt->toArray())->not->toHaveKey('failure_message');
    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditLog::ACTION_PAYMENT_REMINDER_DELIVERY_ATTEMPT_FAILED,
        'auditable_id' => $attempt->id,
    ]);

    expect(fn () => (new MarkPaymentReminderDeliveryAttemptTransportAccepted)->handle($attempt))
        ->toThrow(RuntimeException::class);
});

it('rejects unsupported failure classifications without changing the attempt', function () {
    ['reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    $attempt = (new QueuePaymentReminderDeliveryAttempt)->handle($reminder);

    expect(fn () => (new MarkPaymentReminderDeliveryAttemptFailed)->handle($attempt, 'provider_response'))
        ->toThrow(InvalidArgumentException::class);

    expect($attempt->refresh()->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_QUEUED);
    expect($attempt->failure_message)->toBeNull();
});

it('enforces unique attempt numbers for a reminder', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    PaymentReminderDeliveryAttempt::factory()->for($reminder)->for($guardian)->create(['attempt_number' => 1]);

    expect(fn () => PaymentReminderDeliveryAttempt::factory()->for($reminder)->for($guardian)->create(['attempt_number' => 1]))
        ->toThrow(QueryException::class);
});

it('restricts deleting reminders and guardians with delivery attempt history', function () {
    ['guardian' => $guardian, 'reminder' => $reminder] = paymentReminderDeliveryAttemptFixture();
    (new QueuePaymentReminderDeliveryAttempt)->handle($reminder);

    expect(fn () => $reminder->delete())->toThrow(QueryException::class);
    expect(fn () => $guardian->delete())->toThrow(QueryException::class);

    $this->assertModelExists($reminder);
    $this->assertModelExists($guardian);
});
