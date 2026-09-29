<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolSetting>
 */
class SchoolSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 1,
            'active_academic_year_id' => AcademicYear::factory(),
        ];
    }
}
