<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventDueItem;
use App\Models\StudentDueItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventDueItem>
 */
class EventDueItemFactory extends Factory
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
            'student_due_item_id' => StudentDueItem::factory(),
        ];
    }
}
