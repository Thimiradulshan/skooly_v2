<?php

namespace App\Actions\Payments;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentReversal;
use App\Models\PaymentReversalAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RequestPaymentReversal
{
    /**
     * @param  array<int, array{payment_allocation_id: int, amount: string}>  $allocations
     */
    public function handle(Payment $payment, string $reason, array $allocations, User $requester): PaymentReversal
    {
        if ($allocations === []) {
            throw new InvalidArgumentException('Select at least one original allocation to reverse.');
        }

        return DB::transaction(function () use ($payment, $reason, $allocations, $requester): PaymentReversal {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $originalAllocations = PaymentAllocation::query()
                ->where('payment_id', $payment->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $reversals = PaymentReversal::query()
                ->where('original_payment_id', $payment->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $approvedReversedAmounts = PaymentReversalAllocation::query()
                ->whereIn('payment_reversal_id', $reversals->where('status', PaymentReversal::STATUS_APPROVED)->modelKeys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->groupBy('payment_allocation_id')
                ->map(fn ($reversalAllocations): int => $reversalAllocations->sum(fn (PaymentReversalAllocation $allocation): int => $this->toCents($allocation->selected_amount)));
            $selectedAmounts = [];

            foreach ($allocations as $allocation) {
                $paymentAllocationId = $allocation['payment_allocation_id'];

                if (isset($selectedAmounts[$paymentAllocationId])) {
                    throw new InvalidArgumentException('Each original allocation may be selected only once per reversal.');
                }

                $originalAllocation = $originalAllocations->get($paymentAllocationId);

                if ($originalAllocation === null) {
                    throw new InvalidArgumentException('Selected allocations must belong to the original payment.');
                }

                $selectedAmount = $this->toCents($allocation['amount']);
                $remainingAmount = $this->toCents($originalAllocation->amount) - ($approvedReversedAmounts->get($paymentAllocationId) ?? 0);

                if ($selectedAmount <= 0 || $selectedAmount > $remainingAmount) {
                    throw new RuntimeException('A selected reversal amount exceeds the remaining reversible amount.');
                }

                $selectedAmounts[$paymentAllocationId] = $selectedAmount;
            }

            $reversal = PaymentReversal::query()->create([
                'original_payment_id' => $payment->id,
                'requested_by_user_id' => $requester->id,
                'reason' => $reason,
                'status' => PaymentReversal::STATUS_REQUESTED,
            ]);
            $reversalAllocations = [];

            foreach ($selectedAmounts as $paymentAllocationId => $selectedAmount) {
                $reversalAllocations[] = $reversal->allocations()->create([
                    'payment_allocation_id' => $paymentAllocationId,
                    'selected_amount' => $this->fromCents($selectedAmount),
                ]);
            }

            (new RecordAuditLog)->handle(AuditLog::ACTION_PAYMENT_REVERSAL_REQUESTED, $reversal, $requester, [
                'payment_reversal_id' => $reversal->id,
                'original_payment_id' => $payment->id,
                'family_id' => $payment->family_id,
                'total_amount' => $this->fromCents(array_sum($selectedAmounts)),
                'allocations' => array_map(fn (PaymentReversalAllocation $allocation): array => [
                    'payment_allocation_id' => $allocation->payment_allocation_id,
                    'selected_amount' => $allocation->selected_amount,
                ], $reversalAllocations),
            ]);

            return $reversal->load('allocations.paymentAllocation');
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
