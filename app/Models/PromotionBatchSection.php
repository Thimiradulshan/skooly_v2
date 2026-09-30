<?php

namespace App\Models;

use Database\Factories\PromotionBatchSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['promotion_batch_id', 'source_section_id'])]
class PromotionBatchSection extends Model
{
    /** @use HasFactory<PromotionBatchSectionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<PromotionBatch, $this>
     */
    public function promotionBatch(): BelongsTo
    {
        return $this->belongsTo(PromotionBatch::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function sourceSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'source_section_id');
    }
}
