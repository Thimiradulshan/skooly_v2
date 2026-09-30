<?php

namespace App\Models;

use Database\Factories\DueItemDiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_due_item_id', 'discount_id', 'type', 'value', 'value_type', 'amount_applied'])]
class DueItemDiscount extends Model
{
    /** @use HasFactory<DueItemDiscountFactory> */
    use HasFactory;

    public function studentDueItem(): BelongsTo
    {
        return $this->belongsTo(StudentDueItem::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'amount_applied' => 'decimal:2',
        ];
    }
}
