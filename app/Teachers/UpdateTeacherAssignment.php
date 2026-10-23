<?php

namespace App\Teachers;

use App\Academic\ActiveAcademicYear;
use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateTeacherAssignment
{
    public function handle(TeacherAssignment $assignment, User $teacher, Subject $subject, User $actor): TeacherAssignment
    {
        return DB::transaction(function () use ($assignment, $teacher, $subject, $actor): TeacherAssignment {
            $assignment = TeacherAssignment::query()->with(['academicYear', 'section'])->lockForUpdate()->findOrFail($assignment->id);

            (new ActiveAcademicYear)->ensure($assignment->academicYear);

            if ($assignment->section->is_archived) {
                throw new InvalidArgumentException('Archived sections cannot have their teaching assignments corrected.');
            }

            if (! $teacher->is_active || ! $teacher->hasRole(Role::TEACHER)) {
                throw new InvalidArgumentException('The selected user must be an active Teacher.');
            }

            if (! $teacher->qualifiedSubjects()->whereKey($subject)->exists()) {
                throw new InvalidArgumentException('The selected teacher is not qualified for this subject.');
            }

            $before = $assignment->only(['teacher_id', 'subject_id']);
            $assignment->update(['teacher_id' => $teacher->id, 'subject_id' => $subject->id]);

            (new RecordAuditLog)->handle(AuditLog::ACTION_TEACHER_ASSIGNMENT_UPDATED, $assignment, $actor, [
                'before' => $before,
                'after' => $assignment->only(['teacher_id', 'subject_id']),
            ]);

            return $assignment->fresh(['academicYear', 'section.grade', 'teacher', 'subject']);
        });
    }
}
