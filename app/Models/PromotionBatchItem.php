<?php

namespace App\Models;

use Database\Factories\PromotionBatchItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'promotion_batch_id',
    'student_id',
    'source_enrollment_id',
    'source_section_id',
    'target_grade_id',
    'target_section_id',
    'action',
    'status',
    'applied_enrollment_id',
])]
class PromotionBatchItem extends Model
{
    public const ACTION_PROMOTE = 'promote';

    public const ACTION_RETAIN = 'retain';

    public const ACTION_GRADUATE = 'graduate';

    public const ACTION_EXCLUDE = 'exclude';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_SKIPPED = 'skipped';

    /** @use HasFactory<PromotionBatchItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<PromotionBatch, $this>
     */
    public function promotionBatch(): BelongsTo
    {
        return $this->belongsTo(PromotionBatch::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function sourceEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'source_enrollment_id');
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function sourceSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'source_section_id');
    }

    /**
     * @return BelongsTo<Grade, $this>
     */
    public function targetGrade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'target_grade_id');
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function targetSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'target_section_id');
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function appliedEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'applied_enrollment_id');
    }
}
