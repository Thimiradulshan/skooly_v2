<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
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
            'name' => fake()->name(),
            'dob' => fake()->dateTimeBetween('-18 years', '-3 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['female', 'male']),
            'admission_no' => fake()->unique()->bothify('ADM-#####'),
            'photo_path' => fake()->optional()->bothify('students/#####?.jpg'),
            'status' => Student::STATUS_PENDING_REGISTRATION,
        ];
    }
}
