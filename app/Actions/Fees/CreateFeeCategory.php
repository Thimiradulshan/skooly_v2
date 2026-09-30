<?php

namespace App\Actions\Fees;

use App\Models\FeeCategory;
use Illuminate\Support\Facades\DB;

class CreateFeeCategory
{
    /**
     * Create a FeeCategory.
     *
     * No audit constant exists for fee categories, so this workflow is currently unaudited.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): FeeCategory
    {
        return DB::transaction(fn (): FeeCategory => FeeCategory::query()->create([
            'name' => $data['name'],
            'is_recurring' => $data['is_recurring'] ?? false,
            'is_opt_in' => $data['is_opt_in'] ?? false,
        ]));
    }
}
