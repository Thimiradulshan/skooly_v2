<?php

namespace App\Actions\Notifications;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\PaymentReminderDeliveryAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MarkPaymentReminderDeliveryAttemptTransportAccepted
{
    /**
     * Record a successful handoff to a future email transport.
     */
    public function handle(PaymentReminderDeliveryAttempt $attempt, ?User $actor = null): PaymentReminderDeliveryAttempt
    {
        return DB::transaction(function () use ($attempt, $actor): PaymentReminderDeliveryAttempt {
            $attempt = PaymentReminderDeliveryAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($attempt->status !== PaymentReminderDeliveryAttempt::STATUS_QUEUED) {
                throw new RuntimeException('Only queued delivery attempts can be accepted by a transport.');
            }

            $attempt->forceFill([
                'status' => PaymentReminderDeliveryAttempt::STATUS_TRANSPORT_ACCEPTED,
                'transport_accepted_at' => now(),
            ])->save();

            (new RecordAuditLog)->handle(AuditLog::ACTION_PAYMENT_REMINDER_DELIVERY_ATTEMPT_TRANSPORT_ACCEPTED, $attempt, $actor, [
                'payment_reminder_id' => $attempt->payment_reminder_id,
                'attempt_number' => $attempt->attempt_number,
            ]);

            return $attempt->refresh();
        });
    }
}
