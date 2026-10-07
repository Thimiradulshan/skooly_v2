<?php

namespace App\Models;

use Database\Factories\GradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sequence_order', 'is_archived'])]
class Grade extends Model
{
    /** @use HasFactory<GradeFactory> */
    use HasFactory;

    protected $attributes = [
        'is_archived' => false,
    ];

    /**
     * Get the sections within the grade.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Get the enrollments in the grade.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the historical placements in the grade.
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
            'sequence_order' => 'integer',
            'is_archived' => 'boolean',
        ];
    }
}
