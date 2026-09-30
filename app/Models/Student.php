<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['family_id', 'name', 'dob', 'gender', 'admission_no', 'photo_path', 'status'])]
class Student extends Model
{
    public const STATUS_PENDING_REGISTRATION = 'pending_registration';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_GRADUATED = 'graduated';

    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * Get the family that registers the student.
     */
    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the guardians explicitly linked to the student.
     */
    /**
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class);
    }

    /**
     * Get the student's year-specific enrollments.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function studentDueItems(): HasMany
    {
        return $this->hasMany(StudentDueItem::class);
    }

    public function studentFeeSubscriptions(): HasMany
    {
        return $this->hasMany(StudentFeeSubscription::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }
}
