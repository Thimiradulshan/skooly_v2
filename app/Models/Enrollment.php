<?php

namespace App\Models;

use App\Academic\ActiveAcademicYear;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[Fillable(['student_id', 'academic_year_id', 'grade_id', 'section_id'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    /**
     * Get the student registered for the academic year.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the academic year of the enrollment.
     */
    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the enrollment's current grade.
     */
    /**
     * @return BelongsTo<Grade, $this>
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the enrollment's current section.
     */
    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the promotion items that use this enrollment as their source.
     *
     * @return HasMany<PromotionBatchItem, $this>
     */
    public function sourcePromotionItems(): HasMany
    {
        return $this->hasMany(PromotionBatchItem::class, 'source_enrollment_id');
    }

    /**
     * Get the promotion item that created this enrollment.
     *
     * @return HasOne<PromotionBatchItem, $this>
     */
    public function appliedPromotionItem(): HasOne
    {
        return $this->hasOne(PromotionBatchItem::class, 'applied_enrollment_id');
    }

    /**
     * @return HasMany<EnrollmentPlacement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(EnrollmentPlacement::class);
    }

    /**
     * Record a new placement while preserving the previous placement history.
     */
    public function placeIn(Grade $grade, Section $section): EnrollmentPlacement
    {
        (new ActiveAcademicYear)->ensure($this->academicYear);

        return DB::transaction(function () use ($grade, $section): EnrollmentPlacement {
            $hasPlacementHistory = $this->placements()->exists();

            if (! $hasPlacementHistory && ((int) $this->grade_id !== $grade->id || (int) $this->section_id !== $section->id)) {
                $this->placements()->create([
                    'grade_id' => $this->grade_id,
                    'section_id' => $this->section_id,
                ]);
            }

            $this->update([
                'grade_id' => $grade->id,
                'section_id' => $section->id,
            ]);

            return $this->placements()->create([
                'grade_id' => $grade->id,
                'section_id' => $section->id,
            ]);
        });
    }
}
