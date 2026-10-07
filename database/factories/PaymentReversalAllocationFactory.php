<?php

namespace Database\Factories;

use App\Models\PaymentAllocation;
use App\Models\PaymentReversal;
use App\Models\PaymentReversalAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReversalAllocation>
 */
class PaymentReversalAllocationFactory extends Factory
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
            'payment_allocation_id' => PaymentAllocation::factory(),
            'selected_amount' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
