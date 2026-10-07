<?php

namespace App\Actions\Guardians;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UnlinkGuardianFromStudent
{
    /**
     * Remove an explicit Guardian-to-Student link and record the privacy change.
     */
    public function handle(Guardian $guardian, Student $student, ?User $actor = null): bool
    {
        if ($guardian->family_id !== $student->family_id) {
            throw new InvalidArgumentException('Guardian and Student must belong to the same Family.');
        }

        return DB::transaction(function () use ($guardian, $student, $actor): bool {
            if (! $guardian->students()->whereKey($student->id)->exists()) {
                return false;
            }

            $guardian->students()->detach($student);

            (new RecordAuditLog)->handle(AuditLog::ACTION_GUARDIAN_STUDENT_UNLINKED, $guardian, $actor, [
                'guardian_id' => $guardian->id,
                'student_id' => $student->id,
                'family_id' => $guardian->family_id,
            ]);

            return true;
        });
    }
}
