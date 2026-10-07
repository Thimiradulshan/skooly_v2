<?php

namespace App\Models;

use Database\Factories\PaymentReversalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['original_payment_id', 'requested_by_user_id', 'reason', 'status', 'approved_by_user_id', 'approved_at'])]
class PaymentReversal extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    /** @use HasFactory<PaymentReversalFactory> */
    use HasFactory;

    public function originalPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'original_payment_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /** @return HasMany<PaymentReversalAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentReversalAllocation::class);
    }

    /** @return HasOne<CorrectionReceipt, $this> */
    public function correctionReceipt(): HasOne
    {
        return $this->hasOne(CorrectionReceipt::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }
}
