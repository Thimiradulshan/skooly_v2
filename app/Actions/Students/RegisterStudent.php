<?php

namespace App\Actions\Students;

use App\Academic\ActiveAcademicYear;
use App\Actions\Audit\RecordAuditLog;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegisterStudent
{
    /**
     * Register a Student under an existing Family.
     *
     * No StudentDueItem is generated and the Student is not activated automatically.
     *
     * @param  array<string, mixed>  $studentData
     * @param  array<int, int>  $guardianIds
     * @param  array{academic_year_id: int, grade_id: int, section_id: int}|null  $enrollment
     */
    public function handle(
        Family $family,
        array $studentData,
        array $guardianIds = [],
        ?array $enrollment = null,
        ?User $actor = null,
    ): Student {
        return DB::transaction(function () use ($family, $studentData, $guardianIds, $enrollment, $actor): Student {
            $status = $studentData['status'] ?? Student::STATUS_PENDING_REGISTRATION;

            if (! in_array($status, self::validStatuses(), true)) {
                throw new InvalidArgumentException('Unknown Student status.');
            }

            if (empty($studentData['admission_no'])) {
                throw new InvalidArgumentException('admission_no is required.');
            }

            if ($enrollment !== null) {
                $academicYear = AcademicYear::query()->active()->findOrFail($enrollment['academic_year_id']);
                (new ActiveAcademicYear)->ensure($academicYear);
                Grade::query()->active()->findOrFail($enrollment['grade_id']);
                $section = Section::query()->active()->findOrFail($enrollment['section_id']);

                if ($section->grade_id !== $enrollment['grade_id']) {
                    throw new InvalidArgumentException('Section does not belong to the given Grade.');
                }
            }

            $student = Student::query()->create([
                'family_id' => $family->id,
                'name' => $studentData['name'],
                'dob' => $studentData['dob'],
                'gender' => $studentData['gender'],
                'admission_no' => $studentData['admission_no'],
                'photo_path' => $studentData['photo_path'] ?? null,
                'status' => $status,
            ]);

            foreach ($guardianIds as $guardianId) {
                $guardian = $family->guardians()->findOrFail($guardianId);
                $guardian->students()->syncWithoutDetaching([$student->id]);
            }

            if ($enrollment !== null) {
                Enrollment::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $enrollment['academic_year_id'],
                    'grade_id' => $enrollment['grade_id'],
                    'section_id' => $enrollment['section_id'],
                ]);
            }

            (new RecordAuditLog)->handle(AuditLog::ACTION_STUDENT_REGISTERED, $student, $actor, [
                'student_id' => $student->id,
                'family_id' => $family->id,
                'admission_no' => $student->admission_no,
                'status' => $student->status,
                'linked_guardian_ids' => $guardianIds,
                'enrollment_created' => $enrollment !== null,
            ]);

            return $student;
        });
    }

    /**
     * @return array<int, string>
     */
    private static function validStatuses(): array
    {
        return [
            Student::STATUS_PENDING_REGISTRATION,
            Student::STATUS_ACTIVE,
            Student::STATUS_INACTIVE,
            Student::STATUS_WITHDRAWN,
            Student::STATUS_GRADUATED,
        ];
    }
}
