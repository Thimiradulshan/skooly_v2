<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\GuardianEmailVerificationToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuardianEmailVerificationToken>
 */
class GuardianEmailVerificationTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guardian_id' => Guardian::factory(),
            'email' => fake()->safeEmail(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'expires_at' => now()->addMinutes(30),
            'consumed_at' => null,
        ];
    }
}
