<?php

namespace App\Actions\Fees;

use App\Academic\ActiveAcademicYear;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentFeeSubscription;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateStudentFeeSubscription
{
    /**
     * Subscribe a Student to a fee category for an academic year.
     *
     * Only opt-in categories may be subscribed. No StudentDueItem is generated here.
     */
    public function handle(
        Student $student,
        FeeCategory $feeCategory,
        AcademicYear $academicYear,
        array $data = [],
    ): StudentFeeSubscription {
        if (! $feeCategory->is_opt_in) {
            throw new InvalidArgumentException('Only opt-in fee categories can be subscribed.');
        }

        (new ActiveAcademicYear)->ensure($academicYear);

        return DB::transaction(fn (): StudentFeeSubscription => StudentFeeSubscription::query()->create([
            'student_id' => $student->id,
            'fee_category_id' => $feeCategory->id,
            'academic_year_id' => $academicYear->id,
            'is_active' => $data['is_active'] ?? true,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
        ]));
    }
}
