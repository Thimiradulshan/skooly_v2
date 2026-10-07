<?php

namespace App\Actions\Promotion;

use App\Academic\ActiveAcademicYear;
use App\Actions\Audit\RecordAuditLog;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
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
    public function handle(PromotionBatch $batch, ?User $actor = null): PromotionBatch
    {
        (new ActiveAcademicYear)->ensure(AcademicYear::query()->findOrFail($batch->target_academic_year_id));

        return DB::transaction(function () use ($batch, $actor): PromotionBatch {
            $batch = PromotionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($batch->status !== PromotionBatch::STATUS_DRAFT) {
                throw new RuntimeException('Only a draft promotion batch can be confirmed.');
            }

            foreach ($batch->items()->orderBy('id')->get() as $item) {
                $this->applyItem($batch, $item);
            }

            $batch->update([
                'status' => PromotionBatch::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            (new RecordAuditLog)->handle(AuditLog::ACTION_PROMOTION_BATCH_CONFIRMED, $batch, $actor, [
                'promotion_batch_id' => $batch->id,
                'source_academic_year_id' => $batch->source_academic_year_id,
                'target_academic_year_id' => $batch->target_academic_year_id,
                'applied_count' => $batch->items()->where('status', PromotionBatchItem::STATUS_APPLIED)->count(),
                'skipped_count' => $batch->items()->where('status', PromotionBatchItem::STATUS_SKIPPED)->count(),
                'graduated_count' => $batch->items()
                    ->where('action', PromotionBatchItem::ACTION_GRADUATE)
                    ->where('status', PromotionBatchItem::STATUS_APPLIED)
                    ->count(),
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
