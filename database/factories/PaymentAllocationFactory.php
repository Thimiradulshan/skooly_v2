<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\StudentDueItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAllocation>
 */
class PaymentAllocationFactory extends Factory
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
            'student_due_item_id' => StudentDueItem::factory(),
            'amount' => 100,
        ];
    }
}
