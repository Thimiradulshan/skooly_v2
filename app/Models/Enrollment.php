<?php

namespace App\Models;

use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the enrollment's current grade.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the enrollment's current section.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the placement history for the enrollment.
     *
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
