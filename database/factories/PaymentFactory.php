<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'payment_reference' => fake()->unique()->bothify('PAY-#####'),
            'paid_at' => fake()->dateTimeBetween('-1 year'),
            'method' => 'cash',
            'amount' => 100,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
