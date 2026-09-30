<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\StudentDueItem;
use App\Models\User;

class StudentDueItemPolicy
{
    /**
     * @var array<int, string>
     */
    private const FINANCE_ROLES = [Role::ADMIN, Role::ACCOUNTANT];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function view(User $user, StudentDueItem $studentDueItem): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function update(User $user, StudentDueItem $studentDueItem): bool
    {
        return $user->hasAnyRole(self::FINANCE_ROLES);
    }

    public function delete(User $user, StudentDueItem $studentDueItem): bool
    {
        return $user->hasRole(Role::ADMIN);
    }
}
