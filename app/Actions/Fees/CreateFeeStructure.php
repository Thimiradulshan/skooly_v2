<?php

namespace App\Actions\Fees;

use App\Academic\ActiveAcademicYear;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use Illuminate\Support\Facades\DB;

class CreateFeeStructure
{
    /**
     * Create an academic-year scoped FeeStructure.
     *
     * This is configuration only. No StudentDueItem is generated or rewritten.
     */
    public function handle(
        FeeCategory $feeCategory,
        Grade $grade,
        AcademicYear $academicYear,
        string $amount,
        string $frequency,
    ): FeeStructure {
        (new ActiveAcademicYear)->ensure($academicYear);

        return DB::transaction(fn (): FeeStructure => FeeStructure::query()->create([
            'fee_category_id' => $feeCategory->id,
            'grade_id' => $grade->id,
            'academic_year_id' => $academicYear->id,
            'amount' => $amount,
            'frequency' => $frequency,
        ]));
    }
}
