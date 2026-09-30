<?php

namespace App\Models;

use Database\Factories\PromotionBatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['source_academic_year_id', 'target_academic_year_id', 'created_by', 'status', 'confirmed_at', 'discarded_at'])]
class PromotionBatch extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DISCARDED = 'discarded';

    /** @use HasFactory<PromotionBatchFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function sourceAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'source_academic_year_id');
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function targetAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'target_academic_year_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PromotionBatchSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PromotionBatchSection::class);
    }

    /**
     * @return HasMany<PromotionBatchItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PromotionBatchItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'discarded_at' => 'datetime',
        ];
    }
}
