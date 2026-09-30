<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventCharge;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventCharge>
 */
class EventChargeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'grade_id' => Grade::factory(),
            'amount' => 25,
        ];
    }
}
