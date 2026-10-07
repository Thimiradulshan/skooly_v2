<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReversal>
 */
class PaymentReversalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_payment_id' => Payment::factory(),
            'requested_by_user_id' => User::factory(),
            'reason' => fake()->sentence(),
            'status' => PaymentReversal::STATUS_REQUESTED,
            'approved_by_user_id' => null,
            'approved_at' => null,
        ];
    }
}
