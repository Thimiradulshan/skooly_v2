<?php

namespace App\Policies;

use App\Models\PaymentReversal;
use App\Models\Role;
use App\Models\User;

class PaymentReversalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::ACCOUNTANT]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentReversal $paymentReversal): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::ACCOUNTANT]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::ACCOUNTANT);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function approve(User $user, PaymentReversal $paymentReversal): bool
    {
        return $user->hasRole(Role::ADMIN) && $user->id !== $paymentReversal->requested_by_user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
}
