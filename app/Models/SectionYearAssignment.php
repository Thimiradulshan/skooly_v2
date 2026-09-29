<?php

namespace App\Models;

use Database\Factories\SectionYearAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_year_id', 'section_id', 'class_in_charge_id'])]
class SectionYearAssignment extends Model
{
    /** @use HasFactory<SectionYearAssignmentFactory> */
    use HasFactory;

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function classInCharge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'class_in_charge_id');
    }
}
