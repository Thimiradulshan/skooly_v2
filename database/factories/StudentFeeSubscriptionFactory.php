<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentFeeSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentFeeSubscription>
 */
class StudentFeeSubscriptionFactory extends Factory
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
            'fee_category_id' => FeeCategory::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'is_active' => true,
            'starts_on' => fake()->optional()->date(),
            'ends_on' => fake()->optional()->date(),
        ];
    }
}
