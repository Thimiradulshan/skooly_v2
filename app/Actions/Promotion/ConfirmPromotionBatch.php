<?php

namespace App\Actions\Promotion;

use App\Models\Enrollment;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ConfirmPromotionBatch
{
    /**
     * Apply a draft promotion batch atomically.
     *
     * Either every target Enrollment is created and the batch is confirmed, or nothing changes.
     */
    public function handle(PromotionBatch $batch): PromotionBatch
    {
        if ($batch->status !== PromotionBatch::STATUS_DRAFT) {
            throw new RuntimeException('Only a draft promotion batch can be confirmed.');
        }

        return DB::transaction(function () use ($batch): PromotionBatch {
            foreach ($batch->items()->orderBy('id')->get() as $item) {
                $this->applyItem($batch, $item);
            }

            $batch->update([
                'status' => PromotionBatch::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            return $batch->refresh();
        });
    }

    private function applyItem(PromotionBatch $batch, PromotionBatchItem $item): void
    {
        if ($item->action === PromotionBatchItem::ACTION_EXCLUDE) {
            $item->update(['status' => PromotionBatchItem::STATUS_SKIPPED]);

            return;
        }

        if ($item->action === PromotionBatchItem::ACTION_GRADUATE) {
            $item->student->update(['status' => Student::STATUS_GRADUATED]);
            $item->update(['status' => PromotionBatchItem::STATUS_APPLIED]);

            return;
        }

        $enrollment = $this->createTargetEnrollment($batch, $item);

        $item->update([
            'status' => PromotionBatchItem::STATUS_APPLIED,
            'applied_enrollment_id' => $enrollment->id,
        ]);
    }

    private function createTargetEnrollment(PromotionBatch $batch, PromotionBatchItem $item): Enrollment
    {
        if ($item->target_grade_id === null || $item->target_section_id === null) {
            throw new InvalidArgumentException(
                "Promotion item {$item->id} requires a target grade and target section."
            );
        }

        $targetSection = Section::query()->findOrFail($item->target_section_id);

        if ($targetSection->grade_id !== $item->target_grade_id) {
            throw new InvalidArgumentException(
                "Promotion item {$item->id} target section does not belong to the target grade."
            );
        }

        $sourceEnrollment = Enrollment::query()
            ->where('id', $item->source_enrollment_id)
            ->where('student_id', $item->student_id)
            ->where('academic_year_id', $batch->source_academic_year_id)
            ->first();

        if ($sourceEnrollment === null) {
            throw new InvalidArgumentException(
                "Promotion item {$item->id} has no valid source enrollment in the source academic year."
            );
        }

        return Enrollment::query()->create([
            'student_id' => $item->student_id,
            'academic_year_id' => $batch->target_academic_year_id,
            'grade_id' => $item->target_grade_id,
            'section_id' => $item->target_section_id,
        ]);
    }
}
