<?php

namespace App\Models;

use Database\Factories\FeeCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_recurring'])]
class FeeCategory extends Model
{
    /** @use HasFactory<FeeCategoryFactory> */
    use HasFactory;

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'applies_to_fee_category_id');
    }

    public function studentDueItems(): HasMany
    {
        return $this->hasMany(StudentDueItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_recurring' => 'boolean',
        ];
    }
}
