<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
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
            'name' => fake()->unique()->numerify('Academic Year ####'),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $startDate->modify('+1 year -1 day')->format('Y-m-d'),
        ];
    }
}
