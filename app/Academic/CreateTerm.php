<?php

namespace App\Academic;

use App\Models\AcademicYear;
use App\Models\Term;

class CreateTerm
{
    /**
     * @param  array{name: string, start_date: string, end_date: string}  $data
     */
    public function handle(AcademicYear $academicYear, array $data): Term
    {
        (new ActiveAcademicYear)->ensure($academicYear);

        return Term::query()->create([
            'academic_year_id' => $academicYear->id,
            ...$data,
        ]);
    }
}
