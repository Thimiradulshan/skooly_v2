<?php

namespace App\Actions\Guardians;

use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class LinkGuardianPortalAccount
{
    public function handle(Guardian $guardian, User $user, User $administrator): User
    {
        if (! $administrator->hasRole(Role::ADMIN)) {
            throw new AuthorizationException('Only an administrator may link Guardian portal accounts.');
        }

        if ($user->email_verified_at === null || $guardian->payment_reminder_email_verified_at === null || $guardian->email === null || mb_strtolower($user->email) !== mb_strtolower($guardian->email)) {
            throw new InvalidArgumentException('Both verified email records must match before linking a Guardian portal account.');
        }

        if ($user->guardian_id !== null && $user->guardian_id !== $guardian->id) {
            throw new InvalidArgumentException('This account is already linked to another Guardian.');
        }

        $user->forceFill(['guardian_id' => $guardian->id])->save();

        return $user->refresh();
    }
}
