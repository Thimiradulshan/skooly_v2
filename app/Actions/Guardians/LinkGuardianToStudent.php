<?php

namespace App\Actions\Guardians;

use App\Models\Guardian;
use App\Models\Student;
use InvalidArgumentException;

class LinkGuardianToStudent
{
    /**
     * Link a Guardian to a Student explicitly.
     *
     * Sharing a Family is required, but it never grants access on its own.
     * Running this twice is safe and does not duplicate the link.
     */
    public function handle(Guardian $guardian, Student $student): bool
    {
        if ($guardian->family_id !== $student->family_id) {
            throw new InvalidArgumentException('Guardian and Student must belong to the same Family.');
        }

        if ($guardian->students()->whereKey($student->id)->exists()) {
            return false;
        }

        $guardian->students()->attach($student);

        return true;
    }
}
