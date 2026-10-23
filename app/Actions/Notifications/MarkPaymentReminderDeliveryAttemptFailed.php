<?php

namespace App\Actions\Notifications;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MarkPaymentReminderDeliveryAttemptFailed
{
    /**
     * Record a sanitized, classified delivery failure without storing provider responses.
     */
    public function handle(
        PaymentReminderDeliveryAttempt $attempt,
        string $failureClassification,
        ?User $actor = null,
    ): PaymentReminderDeliveryAttempt {
        $failureMessage = PaymentReminderDeliveryAttempt::failureMessage($failureClassification);

        return DB::transaction(function () use ($attempt, $failureClassification, $failureMessage, $actor): PaymentReminderDeliveryAttempt {
            $attempt = PaymentReminderDeliveryAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($attempt->status !== PaymentReminderDeliveryAttempt::STATUS_QUEUED) {
                throw new RuntimeException('Only queued delivery attempts can be marked as failed.');
            }

            $attempt->forceFill([
                'status' => PaymentReminderDeliveryAttempt::STATUS_FAILED,
                'failed_at' => now(),
                'failure_classification' => $failureClassification,
                'failure_message' => $failureMessage,
            ])->save();

            (new RecordAuditLog)->handle(AuditLog::ACTION_PAYMENT_REMINDER_DELIVERY_ATTEMPT_FAILED, $attempt, $actor, [
                'payment_reminder_id' => $attempt->payment_reminder_id,
                'attempt_number' => $attempt->attempt_number,
                'failure_classification' => $failureClassification,
            ]);

            return $attempt->refresh();
        });
    }
}
