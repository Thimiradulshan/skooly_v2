<?php

namespace App\Models;

use Database\Factories\GuardianEmailVerificationTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 */
#[Fillable(['guardian_id', 'email', 'token_hash', 'expires_at', 'consumed_at'])]
class GuardianEmailVerificationToken extends Model
{
    /** @use HasFactory<GuardianEmailVerificationTokenFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $hidden = ['email', 'token_hash'];

    /** @return BelongsTo<Guardian, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
