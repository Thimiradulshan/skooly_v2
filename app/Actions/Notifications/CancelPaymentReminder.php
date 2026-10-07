<?php

namespace App\Actions\Notifications;

use App\Models\PaymentReminder;
use RuntimeException;

class CancelPaymentReminder
{
    public function handle(PaymentReminder $paymentReminder): PaymentReminder
    {
        if ($paymentReminder->status !== PaymentReminder::STATUS_PENDING) {
            throw new RuntimeException('Only a pending payment reminder can be cancelled.');
        }

        $paymentReminder->update(['status' => PaymentReminder::STATUS_CANCELLED]);

        return $paymentReminder->refresh();
    }
}
