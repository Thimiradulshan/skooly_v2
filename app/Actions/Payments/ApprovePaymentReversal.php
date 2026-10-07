<?php

namespace App\Actions\Payments;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\CorrectionReceipt;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentReversal;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ApprovePaymentReversal
{
    public function handle(PaymentReversal $paymentReversal, string $receiptNo, User $approver): PaymentReversal
    {
        return DB::transaction(function () use ($paymentReversal, $receiptNo, $approver): PaymentReversal {
            $reversal = PaymentReversal::query()->lockForUpdate()->findOrFail($paymentReversal->id);

            if ($reversal->status !== PaymentReversal::STATUS_REQUESTED) {
                throw new RuntimeException('Only requested reversals may be approved.');
            }

            if ($reversal->requested_by_user_id === $approver->id) {
                throw new RuntimeException('A requester cannot approve their own payment reversal.');
            }

            $payment = Payment::query()->lockForUpdate()->findOrFail($reversal->original_payment_id);
            $receipt = $payment->receipt()->lockForUpdate()->first();

            if ($receipt === null) {
                throw new RuntimeException('A payment reversal requires the original receipt.');
            }

            $allocations = PaymentAllocation::query()
                ->where('payment_id', $payment->id)
                ->orderBy('student_due_item_id')
                ->lockForUpdate()
                ->get();

            if ($allocations->isEmpty()) {
                throw new RuntimeException('A payment reversal requires original allocations.');
            }

            $dueItems = StudentDueItem::query()
                ->whereKey($allocations->pluck('student_due_item_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $reopenedAllocations = [];

            foreach ($allocations as $allocation) {
                $dueItem = $dueItems->get($allocation->student_due_item_id);

                if ($dueItem === null) {
                    throw new RuntimeException('An original payment allocation has no due item.');
                }

                $allocationAmount = $this->toCents($allocation->amount);
                $paidAmount = $this->toCents($dueItem->paid_amount);
                $balanceAmount = $this->toCents($dueItem->balance_amount);
                $netAmount = $this->toCents($dueItem->net_amount);

                if ($allocationAmount > $paidAmount || $balanceAmount + $allocationAmount > $netAmount) {
                    throw new RuntimeException('The original allocation can no longer be reopened safely.');
                }

                $newPaidAmount = $paidAmount - $allocationAmount;
                $newBalanceAmount = $balanceAmount + $allocationAmount;
                $dueItem->update([
                    'paid_amount' => $this->fromCents($newPaidAmount),
                    'balance_amount' => $this->fromCents($newBalanceAmount),
                    'status' => $newPaidAmount === 0
                        ? StudentDueItem::STATUS_UNPAID
                        : StudentDueItem::STATUS_PARTIALLY_PAID,
                ]);
                $reopenedAllocations[] = [
                    'payment_allocation_id' => $allocation->id,
                    'student_due_item_id' => $dueItem->id,
                    'amount' => $allocation->amount,
                ];
            }

            $reversal->update([
                'status' => PaymentReversal::STATUS_APPROVED,
                'approved_by_user_id' => $approver->id,
                'approved_at' => now(),
            ]);

            CorrectionReceipt::query()->create([
                'payment_reversal_id' => $reversal->id,
                'receipt_no' => $receiptNo,
                'issued_at' => now(),
                'original_receipt_snapshot' => $receipt->only([
                    'id', 'receipt_no', 'issued_at', 'family_snapshot', 'payment_snapshot', 'allocation_snapshot', 'total_amount',
                ]),
                'reversal_snapshot' => [
                    'payment_reversal_id' => $reversal->id,
                    'original_payment_id' => $payment->id,
                    'reason' => $reversal->reason,
                    'reopened_allocations' => $reopenedAllocations,
                ],
            ]);

            (new RecordAuditLog)->handle(AuditLog::ACTION_PAYMENT_REVERSAL_APPROVED, $reversal, $approver, [
                'payment_reversal_id' => $reversal->id,
                'original_payment_id' => $payment->id,
                'family_id' => $payment->family_id,
                'correction_receipt_no' => $receiptNo,
                'allocation_count' => count($reopenedAllocations),
            ]);

            return $reversal->load(['originalPayment', 'requestedBy', 'approvedBy', 'correctionReceipt']);
        });
    }

    private function toCents(string|int|float $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        if (is_float($amount)) {
            $amount = number_format($amount, 2, '.', '');
        }

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Amounts must use up to two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
