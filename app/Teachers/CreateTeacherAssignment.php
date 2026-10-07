<?php

namespace App\Teachers;

use App\Academic\ActiveAcademicYear;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use InvalidArgumentException;

class CreateTeacherAssignment
{
    public function handle(AcademicYear $academicYear, Section $section, User $teacher, Subject $subject): TeacherAssignment
    {
        (new ActiveAcademicYear)->ensure($academicYear);

        if (! $teacher->hasRole(Role::TEACHER)) {
            throw new InvalidArgumentException('The selected user must have the Teacher role.');
        }

        if (! $teacher->qualifiedSubjects()->whereKey($subject)->exists()) {
            throw new InvalidArgumentException('The selected teacher is not qualified for this subject.');
        }

        return TeacherAssignment::query()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
        ]);
    }
}
