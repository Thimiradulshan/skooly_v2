<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['actor_user_id', 'action', 'auditable_type', 'auditable_id', 'metadata', 'occurred_at'])]
class AuditLog extends Model
{
    public const ACTION_STUDENT_REGISTERED = 'student_registered';

    public const ACTION_FAMILY_CREATED = 'family_created';

    public const ACTION_FAMILY_UPDATED = 'family_updated';

    public const ACTION_DISCOUNT_APPLIED = 'discount_applied';

    public const ACTION_PAYMENT_RECORDED = 'payment_recorded';

    public const ACTION_PAYMENT_ALLOCATION_RECORDED = 'payment_allocation_recorded';

    public const ACTION_PAYMENT_REVERSAL_REQUESTED = 'payment_reversal_requested';

    public const ACTION_PAYMENT_REVERSAL_APPROVED = 'payment_reversal_approved';

    public const ACTION_PROMOTION_BATCH_CREATED = 'promotion_batch_created';

    public const ACTION_PROMOTION_BATCH_CONFIRMED = 'promotion_batch_confirmed';

    public const ACTION_PROMOTION_BATCH_ITEM_UPDATED = 'promotion_batch_item_updated';

    public const ACTION_RECURRING_DUES_GENERATED = 'recurring_dues_generated';

    public const ACTION_EVENT_DUES_GENERATED = 'event_dues_generated';

    public const ACTION_PAYMENT_REMINDERS_GENERATED = 'payment_reminders_generated';

    public const ACTION_GUARDIAN_STUDENT_UNLINKED = 'guardian_student_unlinked';

    public const ACTION_ACADEMIC_YEAR_ARCHIVED = 'academic_year_archived';

    public const ACTION_ACADEMIC_YEAR_RESTORED = 'academic_year_restored';

    public const ACTION_TERM_ARCHIVED = 'term_archived';

    public const ACTION_TERM_RESTORED = 'term_restored';

    public const ACTION_GRADE_ARCHIVED = 'grade_archived';

    public const ACTION_GRADE_RESTORED = 'grade_restored';

    public const ACTION_SECTION_ARCHIVED = 'section_archived';

    public const ACTION_SECTION_RESTORED = 'section_restored';

    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array{metadata: 'array', occurred_at: 'datetime'}
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
