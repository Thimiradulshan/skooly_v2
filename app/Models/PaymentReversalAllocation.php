<?php

namespace App\Models;

use Database\Factories\PaymentReversalAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_reversal_id', 'payment_allocation_id', 'selected_amount'])]
class PaymentReversalAllocation extends Model
{
    /** @use HasFactory<PaymentReversalAllocationFactory> */
    use HasFactory;

    public function paymentReversal(): BelongsTo
    {
        return $this->belongsTo(PaymentReversal::class);
    }

    public function paymentAllocation(): BelongsTo
    {
        return $this->belongsTo(PaymentAllocation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'selected_amount' => 'decimal:2',
        ];
    }
}
