<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'receipt_no' => fake()->unique()->bothify('RCT-#####'),
            'issued_at' => fake()->dateTimeBetween('-1 year'),
            'family_snapshot' => [],
            'payment_snapshot' => [],
            'allocation_snapshot' => [],
            'total_amount' => 100,
        ];
    }
}
