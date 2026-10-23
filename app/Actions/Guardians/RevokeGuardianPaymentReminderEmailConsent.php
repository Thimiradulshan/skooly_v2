<?php

namespace App\Actions\Guardians;

use App\Actions\Audit\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RevokeGuardianPaymentReminderEmailConsent
{
    /**
     * Revoke a Guardian's payment reminder email consent.
     */
    public function handle(Guardian $guardian, User $administrator): Guardian
    {
        if (! $administrator->hasRole(Role::ADMIN)) {
            throw new AuthorizationException('Only an administrator may revoke Guardian email consent.');
        }

        return DB::transaction(function () use ($guardian, $administrator): Guardian {
            $guardian = Guardian::query()->lockForUpdate()->findOrFail($guardian->id);

            $guardian->forceFill(['payment_reminder_email_opt_out_at' => now()])->save();

            (new RecordAuditLog)->handle(AuditLog::ACTION_GUARDIAN_PAYMENT_REMINDER_EMAIL_CONSENT_REVOKED, $guardian, $administrator, [
                'guardian_id' => $guardian->id,
                'family_id' => $guardian->family_id,
            ]);

            return $guardian->refresh();
        });
    }
}
