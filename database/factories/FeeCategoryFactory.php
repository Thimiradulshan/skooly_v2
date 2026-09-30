<?php

namespace Database\Factories;

use App\Models\FeeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeCategory>
 */
class FeeCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'is_recurring' => fake()->boolean(),
        ];
    }
}
