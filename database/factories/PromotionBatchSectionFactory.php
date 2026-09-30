<?php

namespace Database\Factories;

use App\Models\PromotionBatch;
use App\Models\PromotionBatchSection;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionBatchSection>
 */
class PromotionBatchSectionFactory extends Factory
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
            'source_section_id' => Section::factory(),
        ];
    }
}
