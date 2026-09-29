<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', '+1 year');

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => fake()->unique()->numerify('Term ##'),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $startDate->modify('+3 months')->format('Y-m-d'),
        ];
    }
}
