<?php

namespace App\Actions\Payments;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RequestPaymentReversal
{
    public function handle(Payment $payment, string $reason, User $requester): PaymentReversal
    {
        return DB::transaction(function () use ($payment, $reason, $requester): PaymentReversal {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (PaymentReversal::query()->where('original_payment_id', $payment->id)->lockForUpdate()->exists()) {
                throw new RuntimeException('A reversal has already been requested for this payment.');
            }

            $reversal = PaymentReversal::query()->create([
                'original_payment_id' => $payment->id,
                'requested_by_user_id' => $requester->id,
                'reason' => $reason,
                'status' => PaymentReversal::STATUS_REQUESTED,
            ]);

            (new RecordAuditLog)->handle(AuditLog::ACTION_PAYMENT_REVERSAL_REQUESTED, $reversal, $requester, [
                'payment_reversal_id' => $reversal->id,
                'original_payment_id' => $payment->id,
                'family_id' => $payment->family_id,
                'amount' => $payment->amount,
            ]);

            return $reversal;
        });
    }
}
