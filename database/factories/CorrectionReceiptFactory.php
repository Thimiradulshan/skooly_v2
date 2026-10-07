<?php

namespace Database\Factories;

use App\Models\CorrectionReceipt;
use App\Models\PaymentReversal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectionReceipt>
 */
class CorrectionReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_reversal_id' => PaymentReversal::factory(),
            'receipt_no' => fake()->unique()->bothify('CRR-#####'),
            'issued_at' => fake()->dateTimeBetween('-1 year'),
            'original_receipt_snapshot' => [],
            'reversal_snapshot' => [],
        ];
    }
}
