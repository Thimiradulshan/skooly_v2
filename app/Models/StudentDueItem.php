<?php

namespace App\Models;

use Database\Factories\StudentDueItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'academic_year_id', 'fee_category_id', 'fee_structure_id', 'description', 'frequency', 'original_amount', 'discount_amount', 'net_amount', 'due_date', 'status', 'generation_key'])]
class StudentDueItem extends Model
{
    /** @use HasFactory<StudentDueItemFactory> */
    use HasFactory;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function dueItemDiscounts(): HasMany
    {
        return $this->hasMany(DueItemDiscount::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }
}
