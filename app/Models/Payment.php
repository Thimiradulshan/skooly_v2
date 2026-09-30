<?php

namespace App\Models;

use App\Actions\Audit\RecordAuditLog;
use Carbon\CarbonInterface;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

#[Fillable(['family_id', 'payment_reference', 'paid_at', 'method', 'amount', 'notes'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * @return HasOne<Receipt, $this>
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * @param  array<int, array{student_due_item_id: int, amount: string}>  $allocations
     */
    public static function recordManual(
        Family $family,
        string $receiptNo,
        string $method,
        string $amount,
        array $allocations,
        ?string $paymentReference = null,
        ?string $notes = null,
        ?CarbonInterface $paidAt = null,
        ?User $actor = null,
    ): self {
        $paymentAmount = self::toCents($amount);

        if ($paymentAmount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $allocatedAmount = array_sum(array_map(
            fn (array $allocation): int => self::toCents($allocation['amount']),
            $allocations,
        ));

        if ($allocatedAmount !== $paymentAmount) {
            throw new InvalidArgumentException('Allocation total must equal payment amount.');
        }

        return DB::transaction(function () use ($family, $receiptNo, $method, $amount, $allocations, $paymentReference, $notes, $paidAt, $actor): self {
            $payment = $family->payments()->create([
                'payment_reference' => $paymentReference,
                'paid_at' => $paidAt ?? now(),
                'method' => $method,
                'amount' => $amount,
                'notes' => $notes,
            ]);
            $allocationSnapshot = [];
            $allocationModels = [];
            $dueItemIds = [];

            foreach ($allocations as $allocation) {
                $dueItem = StudentDueItem::query()
                    ->whereKey($allocation['student_due_item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $dueItem->student->family_id !== $family->id) {
                    throw new InvalidArgumentException('Due items must belong to the payment family.');
                }

                $allocationAmount = self::toCents($allocation['amount']);
                $balanceAmount = self::toCents($dueItem->balance_amount);

                if ($allocationAmount > $balanceAmount) {
                    throw new InvalidArgumentException('Allocation cannot exceed the due item balance.');
                }

                $paidAmount = self::toCents($dueItem->paid_amount) + $allocationAmount;
                $newBalance = $balanceAmount - $allocationAmount;
                $dueItem->update([
                    'paid_amount' => self::fromCents($paidAmount),
                    'balance_amount' => self::fromCents($newBalance),
                    'status' => $newBalance === 0
                        ? StudentDueItem::STATUS_PAID
                        : StudentDueItem::STATUS_PARTIALLY_PAID,
                ]);
                $allocationModels[] = $payment->allocations()->create([
                    'student_due_item_id' => $dueItem->id,
                    'amount' => self::fromCents($allocationAmount),
                ]);
                $dueItemIds[] = $dueItem->id;
                $allocationSnapshot[] = [
                    'student_due_item_id' => $dueItem->id,
                    'description' => $dueItem->description,
                    'original_amount' => $dueItem->original_amount,
                    'discount_amount' => $dueItem->discount_amount,
                    'net_amount' => $dueItem->net_amount,
                    'allocation_amount' => self::fromCents($allocationAmount),
                    'balance_amount' => self::fromCents($newBalance),
                ];
            }

            $payment->receipt()->create([
                'receipt_no' => $receiptNo,
                'issued_at' => now(),
                'family_snapshot' => $family->only(['id', 'family_code', 'address', 'home_contact_no']),
                'payment_snapshot' => $payment->only(['id', 'payment_reference', 'paid_at', 'method', 'amount', 'notes']),
                'allocation_snapshot' => $allocationSnapshot,
                'total_amount' => $amount,
            ]);

            self::recordAuditLogs($payment, $allocationModels, $dueItemIds, $method, $amount, $family, $actor);

            return $payment->load(['allocations.studentDueItem', 'receipt']);
        });
    }

    /**
     * Audit the recorded payment and each of its allocations.
     *
     * @param  array<int, PaymentAllocation>  $allocationModels
     * @param  array<int, int>  $dueItemIds
     */
    private static function recordAuditLogs(
        Payment $payment,
        array $allocationModels,
        array $dueItemIds,
        string $method,
        string $amount,
        Family $family,
        ?User $actor,
    ): void {
        $audit = new RecordAuditLog;

        $audit->handle(AuditLog::ACTION_PAYMENT_RECORDED, $payment, $actor, [
            'family_id' => $family->id,
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'method' => $method,
            'allocation_count' => count($allocationModels),
            'due_item_ids' => $dueItemIds,
        ]);

        foreach ($allocationModels as $allocation) {
            $audit->handle(AuditLog::ACTION_PAYMENT_ALLOCATION_RECORDED, $allocation, $actor, [
                'family_id' => $family->id,
                'payment_id' => $payment->id,
                'allocation_id' => $allocation->id,
                'amount' => $allocation->amount,
                'student_due_item_id' => $allocation->student_due_item_id,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    private static function toCents(string|int|float $amount): int
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

    private static function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
