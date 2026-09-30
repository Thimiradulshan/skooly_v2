<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\FeeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'fee_category_id' => FeeCategory::factory(),
            'name' => fake()->unique()->sentence(3),
            'event_date' => fake()->date(),
            'description' => fake()->optional()->sentence(),
            'is_mandatory' => true,
            'confirmed_at' => null,
        ];
    }
}
