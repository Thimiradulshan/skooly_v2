<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeStructure>
 */
class FeeStructureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fee_category_id' => FeeCategory::factory(),
            'grade_id' => Grade::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'amount' => fake()->randomFloat(2, 1, 10000),
            'frequency' => fake()->randomElement(['monthly', 'termly', 'yearly']),
        ];
    }
}
