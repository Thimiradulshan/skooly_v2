<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_code' => fake()->unique()->bothify('FAM-#####'),
            'address' => fake()->optional()->address(),
            'home_contact_no' => fake()->optional()->phoneNumber(),
        ];
    }
}
