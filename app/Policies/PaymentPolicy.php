<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\Role;
use App\Models\User;

class PaymentPolicy
{
    /**
     * @var array<int, string>
     */
    private const FINANCE_ROLES = [Role::ADMIN, Role::ACCOUNTANT];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasRole(Role::ADMIN);
    }
}
