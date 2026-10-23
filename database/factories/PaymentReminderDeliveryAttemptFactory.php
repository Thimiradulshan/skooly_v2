<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\PaymentReminder;
use App\Models\PaymentReminderDeliveryAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReminderDeliveryAttempt>
 */
class PaymentReminderDeliveryAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_reminder_id' => PaymentReminder::factory(),
            'attempt_number' => 1,
            'guardian_id' => Guardian::factory(),
            'recipient_email' => fake()->safeEmail(),
            'status' => PaymentReminderDeliveryAttempt::STATUS_QUEUED,
            'transport_accepted_at' => null,
            'failed_at' => null,
            'failure_classification' => null,
            'failure_message' => null,
        ];
    }
}
