<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Guardian;
use App\Models\PaymentReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentReminder>
 */
class PaymentReminderFactory extends Factory
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
            'guardian_id' => Guardian::factory(),
            'student_due_item_id' => null,
            'reminder_type' => PaymentReminder::TYPE_UPCOMING,
            'status' => PaymentReminder::STATUS_PENDING,
            'due_item_ids' => [],
            'message_snapshot' => [],
            'reminder_key' => fake()->unique()->bothify('REM-########'),
            'scheduled_for' => null,
            'sent_at' => null,
        ];
    }
}
