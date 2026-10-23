<?php

namespace App\Actions\Notifications;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QueuePaymentReminderDeliveryAttempt
{
    /**
     * Persist a new queued attempt without contacting an email provider.
     */
    public function handle(PaymentReminder $paymentReminder, ?User $actor = null): PaymentReminderDeliveryAttempt
    {
        return DB::transaction(function () use ($paymentReminder, $actor): PaymentReminderDeliveryAttempt {
            $paymentReminder = PaymentReminder::query()->lockForUpdate()->findOrFail($paymentReminder->id);

            if (! (new PaymentReminderEmailDeliveryEligibility)->handle($paymentReminder)) {
                throw new RuntimeException('The payment reminder is not eligible for email delivery.');
            }

            $guardian = $paymentReminder->guardian()->firstOrFail();
            $recipientEmail = $this->recipientEmail($guardian);
            $attemptNumber = (int) PaymentReminderDeliveryAttempt::query()
                ->where('payment_reminder_id', $paymentReminder->id)
                ->lockForUpdate()
                ->max('attempt_number') + 1;
            $attempt = PaymentReminderDeliveryAttempt::query()->create([
                'payment_reminder_id' => $paymentReminder->id,
                'attempt_number' => $attemptNumber,
                'guardian_id' => $guardian->id,
                'recipient_email' => $recipientEmail,
                'status' => PaymentReminderDeliveryAttempt::STATUS_QUEUED,
            ]);

            (new RecordAuditLog)->handle(AuditLog::ACTION_PAYMENT_REMINDER_DELIVERY_ATTEMPT_QUEUED, $attempt, $actor, [
                'payment_reminder_id' => $paymentReminder->id,
                'guardian_id' => $guardian->id,
                'attempt_number' => $attemptNumber,
            ]);

            return $attempt;
        });
    }

    private function recipientEmail(Guardian $guardian): string
    {
        if ($guardian->email === null || trim($guardian->email) === '') {
            throw new RuntimeException('The Guardian does not have a recipient email address.');
        }

        return $guardian->email;
    }
}
