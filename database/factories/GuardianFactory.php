<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->optional()->word(),
            'contact_no' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'nic' => fake()->optional()->bothify('#########?'),
        ];
    }
}
