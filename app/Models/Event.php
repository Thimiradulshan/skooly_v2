<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'fee_category_id', 'name', 'event_date', 'description', 'is_mandatory', 'confirmed_at'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Get the academic year the event belongs to.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the fee category the event charges fall under.
     *
     * @return BelongsTo<FeeCategory, $this>
     */
    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    /**
     * @return HasMany<EventCharge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(EventCharge::class);
    }

    /**
     * @return HasMany<EventParticipation, $this>
     */
    public function participations(): HasMany
    {
        return $this->hasMany(EventParticipation::class);
    }

    /**
     * @return HasMany<EventDueItem, $this>
     */
    public function eventDueItems(): HasMany
    {
        return $this->hasMany(EventDueItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_mandatory' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }
}
