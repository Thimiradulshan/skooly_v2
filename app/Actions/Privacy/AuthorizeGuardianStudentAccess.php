<?php

namespace App\Actions\Privacy;

use App\Models\Guardian;
use App\Models\Student;

class AuthorizeGuardianStudentAccess
{
    /**
     * A Guardian may access a Student only through an explicit guardian_student link.
     *
     * Sharing a Family, or combined billing, never grants access on its own.
     */
    public function handle(Guardian $guardian, Student $student): bool
    {
        return $guardian->students()->whereKey($student->id)->exists();
    }
}
