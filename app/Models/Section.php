<?php

namespace App\Models;

use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['grade_id', 'name', 'capacity', 'is_archived'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected $attributes = [
        'is_archived' => false,
    ];

    /**
     * Get the grade that contains the section.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the yearly subject teaching assignments for the section.
     */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /**
     * Get the yearly class teacher assignments for the section.
     */
    public function yearAssignments(): HasMany
    {
        return $this->hasMany(SectionYearAssignment::class);
    }

    /**
     * Get the enrollments in the section.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the historical placements in the section.
     */
    public function enrollmentPlacements(): HasMany
    {
        return $this->hasMany(EnrollmentPlacement::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_archived', false);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_archived' => 'boolean',
        ];
    }
}
