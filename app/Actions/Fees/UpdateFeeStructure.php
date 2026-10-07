<?php

namespace App\Actions\Fees;

use App\Models\FeeStructure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateFeeStructure
{
    /**
     * Update a fee structure before it has produced any due items.
     */
    public function handle(FeeStructure $feeStructure, string $amount, string $frequency): FeeStructure
    {
        return DB::transaction(function () use ($feeStructure, $amount, $frequency): FeeStructure {
            $feeStructure = FeeStructure::query()->lockForUpdate()->findOrFail($feeStructure->id);

            if ($feeStructure->studentDueItems()->exists()) {
                throw new RuntimeException('This fee structure is locked because it has generated due items.');
            }

            $feeStructure->update([
                'amount' => $amount,
                'frequency' => $frequency,
            ]);

            return $feeStructure->refresh();
        });
    }
}
