<?php

namespace App\Models;

use Database\Factories\PaymentReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['family_id', 'guardian_id', 'student_due_item_id', 'reminder_type', 'status', 'due_item_ids', 'message_snapshot', 'reminder_key', 'scheduled_for', 'sent_at'])]
class PaymentReminder extends Model
{
    public const TYPE_UPCOMING = 'upcoming';

    public const TYPE_OVERDUE = 'overdue';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_CANCELLED = 'cancelled';

    /** @use HasFactory<PaymentReminderFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Guardian, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * @return BelongsTo<StudentDueItem, $this>
     */
    public function studentDueItem(): BelongsTo
    {
        return $this->belongsTo(StudentDueItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_item_ids' => 'array',
            'message_snapshot' => 'array',
            'scheduled_for' => 'date',
            'sent_at' => 'datetime',
        ];
    }
}
