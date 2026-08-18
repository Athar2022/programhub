<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Determine whether the user can view subscriptions for an organization.
     */
    public function viewAny(User $user, Organization $organization): bool
    {
        return $this->canManageOrganization($user, $organization);
    }

    /**
     * Determine whether the user can view the subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        return $this->canManageOrganization($user, $subscription->organization);
    }

    /**
     * Determine whether the user can create a subscription for an organization.
     */
    public function create(User $user, Organization $organization): bool
    {
        return $this->canManageOrganization($user, $organization);
    }

    /**
     * Determine whether the user can update the subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        return $user->hasRole(User::ROLE_PLATFORM_ADMIN);
    }

    /**
     * Subscriptions are historical records and cannot be deleted.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        return false;
    }

    /**
     * Subscriptions are not restored through the API.
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        return false;
    }

    /**
     * Subscriptions cannot be permanently deleted.
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return false;
    }

    /**
     * Determine whether the user can manage an organization subscription.
     */
    private function canManageOrganization(User $user, Organization $organization): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        return $user->hasRole(User::ROLE_ORGANIZATION)
            && $organization->users()
                ->whereKey($user->id)
                ->wherePivotIn('role', ['owner', 'admin'])
                ->exists();
    }
}
