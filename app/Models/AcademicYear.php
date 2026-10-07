<?php

namespace App\Models;

use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** @property bool $is_archived */
#[Fillable(['name', 'start_date', 'end_date', 'is_archived'])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    protected $attributes = [
        'is_archived' => false,
    ];

    /**
     * Get the active school setting that references this academic year.
     */
    public function activeSchoolSetting(): HasOne
    {
        return $this->hasOne(SchoolSetting::class, 'active_academic_year_id');
    }

    /**
     * Get the terms for the academic year.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    /**
     * Get the teaching assignments for the academic year.
     */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /**
     * Get the class teacher assignments for the academic year.
     */
    public function sectionYearAssignments(): HasMany
    {
        return $this->hasMany(SectionYearAssignment::class);
    }

    /**
     * Get the student enrollments for the academic year.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
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
            'start_date' => 'date',
            'end_date' => 'date',
            'is_archived' => 'boolean',
        ];
    }
}
