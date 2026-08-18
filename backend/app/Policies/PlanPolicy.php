<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    /**
     * Determine whether the user can view any plans through protected endpoints.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_PLATFORM_ADMIN);
    }

    /**
     * Determine whether the user can view a plan through a protected endpoint.
     */
    public function view(User $user, Plan $plan): bool
    {
        return $user->hasRole(User::ROLE_PLATFORM_ADMIN)
            || $plan->status === 'active';
    }

    /**
     * Determine whether the user can create a plan.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_PLATFORM_ADMIN);
    }

    /**
     * Determine whether the user can update a plan.
     */
    public function update(User $user, Plan $plan): bool
    {
        return $user->hasRole(User::ROLE_PLATFORM_ADMIN);
    }

    /**
     * Plans are deactivated instead of being deleted.
     */
    public function delete(User $user, Plan $plan): bool
    {
        return false;
    }

    /**
     * Plans are not restored through the API in the MVP.
     */
    public function restore(User $user, Plan $plan): bool
    {
        return false;
    }

    /**
     * Plans cannot be permanently deleted.
     */
    public function forceDelete(User $user, Plan $plan): bool
    {
        return false;
    }
}
