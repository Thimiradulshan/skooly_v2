<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\Role;
use App\Models\User;

class ReceiptPolicy
{
    /**
     * @var array<int, string>
     */
    private const FINANCE_ROLES = [Role::ADMIN, Role::ACCOUNTANT];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function update(User $user, Receipt $receipt): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }
}
