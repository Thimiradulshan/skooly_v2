<?php

namespace App\Academic;

use App\Models\AcademicYear;
use App\Models\SchoolSetting;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use InvalidArgumentException;

class ActiveAcademicYear
{
    public function current(): AcademicYear
    {
        $setting = SchoolSetting::query()->firstOrFail();

        $academicYear = AcademicYear::query()->find($setting->active_academic_year_id);

        if ($academicYear === null || $academicYear->is_archived) {
            throw new InvalidArgumentException('The configured active academic year must be available.');
        }

        return $academicYear;
    }

    public function ensure(AcademicYear $academicYear): void
    {
        if (! $academicYear->is($this->current())) {
            throw new InvalidArgumentException('New operations must use the active academic year.');
        }
    }

    public function validationRule(): Exists
    {
        return Rule::exists('school_settings', 'active_academic_year_id');
    }
}
