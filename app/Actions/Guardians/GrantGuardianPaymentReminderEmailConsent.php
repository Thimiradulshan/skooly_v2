<?php

namespace App\Actions\Guardians;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GrantGuardianPaymentReminderEmailConsent
{
    /**
     * Record a Guardian's explicit authorization for payment reminder email.
     */
    public function handle(Guardian $guardian, User $administrator, string $authorizationRecord): Guardian
    {
        if (! $administrator->hasRole(Role::ADMIN)) {
            throw new AuthorizationException('Only an administrator may record Guardian email consent.');
        }

        $authorizationRecord = trim($authorizationRecord);

        if ($authorizationRecord === '') {
            throw new InvalidArgumentException('A Guardian authorization record is required to grant email consent.');
        }

        return DB::transaction(function () use ($guardian, $administrator, $authorizationRecord): Guardian {
            $guardian = Guardian::query()->lockForUpdate()->findOrFail($guardian->id);

            $guardian->forceFill([
                'payment_reminder_email_opt_in_at' => now(),
                'payment_reminder_email_opt_out_at' => null,
            ])->save();

            (new RecordAuditLog)->handle(AuditLog::ACTION_GUARDIAN_PAYMENT_REMINDER_EMAIL_CONSENT_GRANTED, $guardian, $administrator, [
                'guardian_id' => $guardian->id,
                'family_id' => $guardian->family_id,
                'guardian_authorization_record' => $authorizationRecord,
            ]);

            return $guardian->refresh();
        });
    }
}
