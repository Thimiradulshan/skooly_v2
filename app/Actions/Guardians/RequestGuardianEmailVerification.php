<?php

namespace App\Actions\Guardians;

use App\Actions\Audit\RecordAuditLog;
use App\Mail\GuardianEmailVerificationMail;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\GuardianEmailVerificationToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class RequestGuardianEmailVerification
{
    public function handle(Guardian $guardian, User $administrator): void
    {
        if (! $administrator->hasRole(Role::ADMIN)) {
            throw new AuthorizationException('Only an administrator may request Guardian email verification.');
        }

        $key = 'guardian-email-verification:'.$administrator->id.':'.$guardian->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw new RuntimeException('Too many verification requests. Please try again later.');
        }

        $plainToken = bin2hex(random_bytes(32));
        $email = DB::transaction(function () use ($guardian, $administrator, $plainToken): string {
            $guardian = Guardian::query()->lockForUpdate()->findOrFail($guardian->id);

            if ($guardian->email === null || trim($guardian->email) === '') {
                throw new RuntimeException('The Guardian does not have an email address.');
            }

            GuardianEmailVerificationToken::query()->where('guardian_id', $guardian->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            GuardianEmailVerificationToken::query()->create([
                'guardian_id' => $guardian->id,
                'email' => $guardian->email,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes(30),
            ]);
            (new RecordAuditLog)->handle(AuditLog::ACTION_GUARDIAN_EMAIL_VERIFICATION_REQUESTED, $guardian, $administrator, ['guardian_id' => $guardian->id]);

            return $guardian->email;
        });

        RateLimiter::hit($key, 1800);
        Mail::to($email)->send(new GuardianEmailVerificationMail($plainToken));
    }
}
