<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Teacher section access cannot be proven from the current models, so it is denied.
     * Accountant student visibility is also an unresolved decision.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function view(User $user, Student $student): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasRole(Role::ADMIN);
    }
}
