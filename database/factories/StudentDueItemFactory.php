<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentDueItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDueItem>
 */
class StudentDueItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'fee_category_id' => FeeCategory::factory(),
            'fee_structure_id' => null,
            'description' => fake()->sentence(3),
            'frequency' => fake()->optional()->randomElement(['monthly', 'termly', 'yearly']),
            'original_amount' => 100,
            'discount_amount' => 0,
            'net_amount' => 100,
            'due_date' => fake()->optional()->date(),
            'status' => 'unpaid',
            'generation_key' => null,
        ];
    }
}
