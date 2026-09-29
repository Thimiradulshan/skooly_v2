<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
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
            'section_id' => Section::factory(),
            'grade_id' => function (array $attributes): int {
                return Section::query()->findOrFail($attributes['section_id'])->grade_id;
            },
        ];
    }
}
