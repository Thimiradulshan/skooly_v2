<?php

namespace Database\Factories;

use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionBatchItem>
 */
class PromotionBatchItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotion_batch_id' => PromotionBatch::factory(),
            'action' => PromotionBatchItem::ACTION_PROMOTE,
            'status' => PromotionBatchItem::STATUS_PENDING,
            'target_grade_id' => null,
            'target_section_id' => null,
            'applied_enrollment_id' => null,
        ];
    }
}
