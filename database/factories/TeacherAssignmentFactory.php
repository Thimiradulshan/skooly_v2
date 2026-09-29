<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherAssignment>
 */
class TeacherAssignmentFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterMaking(function (TeacherAssignment $assignment): void {
            $assignment->teacher->qualifiedSubjects()->syncWithoutDetaching([$assignment->subject_id]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'section_id' => Section::factory(),
            'teacher_id' => User::factory()->teacher(),
            'subject_id' => Subject::factory(),
        ];
    }
}
