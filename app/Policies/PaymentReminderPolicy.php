<?php

namespace App\Policies;

use App\Models\PaymentReminder;
use App\Models\Role;
use App\Models\User;

class PaymentReminderPolicy
{
    /**
     * @var array<int, string>
     */
    private const FINANCE_ROLES = [Role::ADMIN, Role::ACCOUNTANT];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function view(User $user, PaymentReminder $paymentReminder): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function update(User $user, PaymentReminder $paymentReminder): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }
}
