<?php

namespace App\Models;

use Database\Factories\SchoolSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['active_academic_year_id'])]
class SchoolSetting extends Model
{
    /** @use HasFactory<SchoolSettingFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, int>
     */
    protected $attributes = [
        'id' => 1,
    ];

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the active academic year.
     */
    public function activeAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'active_academic_year_id');
    }
}
