<?php

namespace App\Policies;

use App\Models\PromotionBatch;
use App\Models\Role;
use App\Models\User;

class PromotionBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function view(User $user, PromotionBatch $promotionBatch): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function update(User $user, PromotionBatch $promotionBatch): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function delete(User $user, PromotionBatch $promotionBatch): bool
    {
        return $user->hasRole(Role::ADMIN);
    }
}
