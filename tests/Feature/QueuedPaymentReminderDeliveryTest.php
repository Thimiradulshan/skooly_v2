<?php

use App\Actions\Notifications\QueuePaymentReminderDeliveryAttempt;
use App\Jobs\DeliverPaymentReminderEmail;
use App\Mail\PaymentReminderEmail;
use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(LazilyRefreshDatabase::class);

function queuedReminderFixture(): array
{
    $year = AcademicYear::factory()->create();
    setActiveAcademicYear($year);
    $family = Family::factory()->create();
    $guardian = Guardian::factory()->for($family)->create(['email' => 'guardian@example.test']);
    $guardian->forceFill(['payment_reminder_email_opt_in_at' => now()->subMinute(), 'payment_reminder_email_verified_at' => now()])->save();
    $student = Student::factory()->for($family)->create(['name' => 'Only Student']);
    $guardian->students()->attach($student);
    $due = StudentDueItem::factory()->for($student)->for($year)->for(FeeCategory::factory())->create(['balance_amount' => 75, 'net_amount' => 75, 'paid_amount' => 0, 'due_date' => '2026-12-01']);
    $reminder = PaymentReminder::factory()->for($family)->for($guardian)->create(['due_item_ids' => [$due->id]]);
    $attempt = (new QueuePaymentReminderDeliveryAttempt)->handle($reminder);

    return compact('guardian', 'student', 'due', 'reminder', 'attempt');
}

it('sends a private single-student reminder to its authorized recipient', function () {
    Mail::fake();
    ['guardian' => $guardian, 'attempt' => $attempt] = queuedReminderFixture();

    (new DeliverPaymentReminderEmail($attempt->id))->handle();

    Mail::assertSent(PaymentReminderEmail::class, function (PaymentReminderEmail $mail) use ($guardian): bool {
        return $mail->hasTo($guardian->email)
            && $mail->studentName === 'Only Student'
            && $mail->outstandingAmount === '75.00'
            && $mail->envelope()->subject === 'Skooly payment reminder';
    });
    expect($attempt->refresh()->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_TRANSPORT_ACCEPTED);
});

it('does not send after consent revocation email change settlement cancellation or archival', function (string $change): void {
    Mail::fake();
    ['guardian' => $guardian, 'due' => $due, 'reminder' => $reminder, 'attempt' => $attempt] = queuedReminderFixture();

    match ($change) {
        'revoked' => $guardian->forceFill(['payment_reminder_email_opt_out_at' => now()])->save(),
        'email' => $guardian->update(['email' => 'changed@example.test']),
        'settled' => $due->update(['paid_amount' => 75, 'balance_amount' => 0, 'status' => StudentDueItem::STATUS_PAID]),
        'cancelled' => $reminder->update(['status' => PaymentReminder::STATUS_CANCELLED]),
        'archived' => $due->academicYear->update(['is_archived' => true]),
    };

    (new DeliverPaymentReminderEmail($attempt->id))->handle();

    Mail::assertNothingSent();
    expect($attempt->refresh()->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_FAILED);
})->with(['revoked', 'email', 'settled', 'cancelled', 'archived']);

it('does not send multi-student reminder data and ignores duplicate jobs', function () {
    Mail::fake();
    ['reminder' => $reminder, 'attempt' => $attempt] = queuedReminderFixture();
    $reminder->update(['due_item_ids' => [$reminder->due_item_ids[0], 999999]]);

    (new DeliverPaymentReminderEmail($attempt->id))->handle();
    (new DeliverPaymentReminderEmail($attempt->id))->handle();

    Mail::assertNothingSent();
    expect($attempt->refresh()->status)->toBe(PaymentReminderDeliveryAttempt::STATUS_FAILED);
});

it('blocks external transports and declares bounded retry delays', function () {
    Mail::fake();
    ['attempt' => $attempt] = queuedReminderFixture();
    config(['mail.default' => 'smtp']);
    $job = new DeliverPaymentReminderEmail($attempt->id);

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);
    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe([60, 300, 900]);
    Mail::assertNothingSent();
});
