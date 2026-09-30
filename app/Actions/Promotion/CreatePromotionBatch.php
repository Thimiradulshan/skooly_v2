<?php

namespace App\Actions\Promotion;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePromotionBatch
{
    /**
     * Create a draft promotion batch and list its default items.
     *
     * Nothing is applied here. No Enrollment or Student record is modified.
     */
    public function handle(
        AcademicYear $sourceAcademicYear,
        AcademicYear $targetAcademicYear,
        array $sourceSectionIds,
        ?User $createdBy = null,
    ): PromotionBatch {
        return DB::transaction(function () use ($sourceAcademicYear, $targetAcademicYear, $sourceSectionIds, $createdBy): PromotionBatch {
            $batch = PromotionBatch::query()->create([
                'source_academic_year_id' => $sourceAcademicYear->id,
                'target_academic_year_id' => $targetAcademicYear->id,
                'created_by' => $createdBy?->id,
                'status' => PromotionBatch::STATUS_DRAFT,
            ]);

            $sections = Section::query()->whereIn('id', $sourceSectionIds)->get();

            foreach ($sections as $section) {
                $batch->sections()->create(['source_section_id' => $section->id]);
            }

            $enrollments = Enrollment::query()
                ->where('academic_year_id', $sourceAcademicYear->id)
                ->whereIn('section_id', $sections->pluck('id'))
                ->whereHas('student', fn ($student) => $student->where('status', Student::STATUS_ACTIVE))
                ->orderBy('id')
                ->get();

            foreach ($enrollments as $enrollment) {
                $nextGrade = $this->nextGrade($enrollment->grade_id);
                $targetSection = $nextGrade
                    ? $this->sectionWithSameName($nextGrade->id, $enrollment->section->name)
                    : null;

                $batch->items()->create([
                    'student_id' => $enrollment->student_id,
                    'source_enrollment_id' => $enrollment->id,
                    'source_section_id' => $enrollment->section_id,
                    'target_grade_id' => $nextGrade?->id,
                    'target_section_id' => $targetSection?->id,
                    'action' => $nextGrade
                        ? PromotionBatchItem::ACTION_PROMOTE
                        : PromotionBatchItem::ACTION_GRADUATE,
                    'status' => PromotionBatchItem::STATUS_PENDING,
                ]);
            }

            return $batch->load(['sections', 'items']);
        });
    }

    private function nextGrade(int $gradeId): ?Grade
    {
        $current = Grade::query()->findOrFail($gradeId);

        return Grade::query()
            ->where('sequence_order', '>', $current->sequence_order)
            ->orderBy('sequence_order')
            ->first();
    }

    private function sectionWithSameName(int $gradeId, string $name): ?Section
    {
        return Section::query()
            ->where('grade_id', $gradeId)
            ->where('name', $name)
            ->first();
    }
}
