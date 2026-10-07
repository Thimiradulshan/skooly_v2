<?php

namespace App\Teachers;

use App\Academic\ActiveAcademicYear;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionYearAssignment;
use App\Models\User;
use InvalidArgumentException;

class CreateSectionYearAssignment
{
    public function handle(AcademicYear $academicYear, Section $section, User $teacher): SectionYearAssignment
    {
        (new ActiveAcademicYear)->ensure($academicYear);

        if (! $teacher->hasRole(Role::TEACHER)) {
            throw new InvalidArgumentException('The selected user must have the Teacher role.');
        }

        return SectionYearAssignment::query()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'class_in_charge_id' => $teacher->id,
        ]);
    }
}
