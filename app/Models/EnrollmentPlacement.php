<?php

namespace App\Models;

use Database\Factories\EnrollmentPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_id', 'grade_id', 'section_id'])]
class EnrollmentPlacement extends Model
{
    /** @use HasFactory<EnrollmentPlacementFactory> */
    use HasFactory;

    /**
     * Get the enrollment this placement belongs to.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the grade recorded for this placement.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the section recorded for this placement.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
