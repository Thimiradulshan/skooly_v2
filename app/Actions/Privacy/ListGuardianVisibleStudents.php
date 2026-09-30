<?php

namespace App\Actions\Privacy;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Support\Collection;

class ListGuardianVisibleStudents
{
    /**
     * List only the Students explicitly linked to the Guardian.
     *
     * @return Collection<int, Student>
     */
    public function handle(Guardian $guardian): Collection
    {
        return $guardian->students()->orderBy('students.id')->get();
    }
}
