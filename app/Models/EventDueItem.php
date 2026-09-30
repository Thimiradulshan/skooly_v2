<?php

namespace App\Models;

use Database\Factories\EventDueItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'student_due_item_id'])]
class EventDueItem extends Model
{
    /** @use HasFactory<EventDueItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<StudentDueItem, $this>
     */
    public function studentDueItem(): BelongsTo
    {
        return $this->belongsTo(StudentDueItem::class);
    }
}
