<?php

namespace App\Models;

use Database\Factories\CorrectionReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_reversal_id', 'receipt_no', 'issued_at', 'original_receipt_snapshot', 'reversal_snapshot'])]
class CorrectionReceipt extends Model
{
    /** @use HasFactory<CorrectionReceiptFactory> */
    use HasFactory;

    public function paymentReversal(): BelongsTo
    {
        return $this->belongsTo(PaymentReversal::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'original_receipt_snapshot' => 'array',
            'reversal_snapshot' => 'array',
        ];
    }
}
