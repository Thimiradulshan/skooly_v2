<?php

namespace App\Models;

use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['family_id', 'name', 'relationship', 'contact_no', 'email', 'nic'])]
class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_reminder_email_opt_in_at' => 'datetime',
            'payment_reminder_email_verified_at' => 'datetime',
            'payment_reminder_email_opt_out_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Guardian $guardian): void {
            if ($guardian->isDirty('email')) {
                $guardian->forceFill([
                    'payment_reminder_email_opt_in_at' => null,
                    'payment_reminder_email_verified_at' => null,
                    'payment_reminder_email_opt_out_at' => null,
                ]);
                GuardianEmailVerificationToken::query()
                    ->where('guardian_id', $guardian->id)
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now()]);
            }
        });
    }

    /**
     * Get the family that registers the guardian.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the students this guardian may access.
     */
    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    public function paymentReminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function paymentReminderDeliveryAttempts(): HasMany
    {
        return $this->hasMany(PaymentReminderDeliveryAttempt::class);
    }

    public function emailVerificationTokens(): HasMany
    {
        return $this->hasMany(GuardianEmailVerificationToken::class);
    }
}
