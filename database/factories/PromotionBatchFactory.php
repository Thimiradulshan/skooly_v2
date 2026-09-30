<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionBatch>
 */
class PromotionBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_academic_year_id' => AcademicYear::factory(),
            'target_academic_year_id' => AcademicYear::factory(),
            'created_by' => null,
            'status' => PromotionBatch::STATUS_DRAFT,
            'confirmed_at' => null,
            'discarded_at' => null,
        ];
    }
}
