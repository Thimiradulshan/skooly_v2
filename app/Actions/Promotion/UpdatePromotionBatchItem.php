<?php

namespace App\Actions\Promotion;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class UpdatePromotionBatchItem
{
    /**
     * Update one draft promotion item without applying any enrollment changes.
     */
    public function handle(
        PromotionBatch $batch,
        PromotionBatchItem $item,
        string $action,
        ?int $targetGradeId,
        ?int $targetSectionId,
        ?User $actor = null,
    ): PromotionBatchItem {
        return DB::transaction(function () use ($batch, $item, $action, $targetGradeId, $targetSectionId, $actor): PromotionBatchItem {
            $batch = PromotionBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($batch->status !== PromotionBatch::STATUS_DRAFT) {
                throw new RuntimeException('Only items in a draft promotion batch can be edited.');
            }

            $item = $batch->items()->lockForUpdate()->findOrFail($item->id);
            $before = $item->only(['action', 'target_grade_id', 'target_section_id']);

            if (! in_array($action, [
                PromotionBatchItem::ACTION_PROMOTE,
                PromotionBatchItem::ACTION_RETAIN,
                PromotionBatchItem::ACTION_EXCLUDE,
                PromotionBatchItem::ACTION_GRADUATE,
            ], true)) {
                throw new InvalidArgumentException('The promotion action is invalid.');
            }

            if (in_array($action, [PromotionBatchItem::ACTION_PROMOTE, PromotionBatchItem::ACTION_RETAIN], true)) {
                if ($targetGradeId === null || $targetSectionId === null) {
                    throw new InvalidArgumentException('Promote and retain actions require a target grade and target section.');
                }

                $targetSection = Section::query()->findOrFail($targetSectionId);

                if ($targetSection->grade_id !== $targetGradeId) {
                    throw new InvalidArgumentException('The target section must belong to the selected target grade.');
                }
            } else {
                $targetGradeId = null;
                $targetSectionId = null;
            }

            $item->update([
                'action' => $action,
                'target_grade_id' => $targetGradeId,
                'target_section_id' => $targetSectionId,
            ]);

            $item = $item->refresh();

            (new RecordAuditLog)->handle(AuditLog::ACTION_PROMOTION_BATCH_ITEM_UPDATED, $item, $actor, [
                'promotion_batch_id' => $batch->id,
                'promotion_batch_item_id' => $item->id,
                'before' => $before,
                'after' => $item->only(['action', 'target_grade_id', 'target_section_id']),
            ]);

            return $item;
        });
    }
}
