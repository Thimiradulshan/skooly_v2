<?php

namespace App\Actions\Fees;

use App\Models\FeeCategory;

class UpdateFeeCategory
{
    /**
     * Update a FeeCategory.
     *
     * Renaming a category never rewrites existing FeeStructures or StudentDueItems,
     * which keep their own snapshots.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(FeeCategory $feeCategory, array $data): FeeCategory
    {
        $feeCategory->fill([
            'name' => $data['name'],
            'is_recurring' => $data['is_recurring'] ?? false,
            'is_opt_in' => $data['is_opt_in'] ?? false,
        ])->save();

        return $feeCategory->refresh();
    }
}
