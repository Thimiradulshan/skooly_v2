<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\EnrollmentPlacement;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentPlacement>
 */
class EnrollmentPlacementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'section_id' => Section::factory(),
            'grade_id' => function (array $attributes): int {
                return Section::query()->findOrFail($attributes['section_id'])->grade_id;
            },
        ];
    }
}
