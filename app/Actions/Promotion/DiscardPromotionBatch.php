<?php

namespace App\Actions\Promotion;

use App\Models\PromotionBatch;
use RuntimeException;

class DiscardPromotionBatch
{
    public function handle(PromotionBatch $batch): PromotionBatch
    {
        if ($batch->status !== PromotionBatch::STATUS_DRAFT) {
            throw new RuntimeException('Only a draft promotion batch can be discarded.');
        }

        $batch->update([
            'status' => PromotionBatch::STATUS_DISCARDED,
            'discarded_at' => now(),
        ]);

        return $batch->refresh();
    }
}
