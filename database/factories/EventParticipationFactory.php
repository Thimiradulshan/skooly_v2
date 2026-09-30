<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventParticipation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventParticipation>
 */
class EventParticipationFactory extends Factory
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
            'student_id' => Student::factory(),
            'status' => EventParticipation::STATUS_OPTED_IN,
        ];
    }
}
