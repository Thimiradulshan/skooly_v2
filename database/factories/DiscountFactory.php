<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\FeeCategory;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'applies_to_fee_category_id' => FeeCategory::factory(),
            'type' => fake()->word(),
            'value' => fake()->randomFloat(2, 1, 100),
            'value_type' => fake()->optional()->randomElement(['amount', 'percentage']),
            'is_active' => true,
            'starts_on' => fake()->optional()->date(),
            'ends_on' => fake()->optional()->date(),
        ];
    }
}
