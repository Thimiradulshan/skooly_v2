<?php

namespace App\Actions\Guardians;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\GuardianEmailVerificationToken;
use Illuminate\Support\Facades\DB;

class VerifyGuardianEmail
{
    public function handle(string $plainToken): bool
    {
        return DB::transaction(function () use ($plainToken): bool {
            $token = GuardianEmailVerificationToken::query()->where('token_hash', hash('sha256', $plainToken))->lockForUpdate()->first();

            if ($token === null || $token->consumed_at !== null || $token->expires_at->isPast()) {
                return false;
            }

            $guardian = Guardian::query()->lockForUpdate()->find($token->guardian_id);

            if ($guardian === null || $guardian->email !== $token->email || $guardian->payment_reminder_email_verified_at !== null) {
                $token->forceFill(['consumed_at' => now()])->save();

                return false;
            }

            $guardian->forceFill(['payment_reminder_email_verified_at' => now()])->save();
            $token->forceFill(['consumed_at' => now()])->save();
            (new RecordAuditLog)->handle(AuditLog::ACTION_GUARDIAN_EMAIL_VERIFIED, $guardian, null, ['guardian_id' => $guardian->id]);

            return true;
        });
    }
}
