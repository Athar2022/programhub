<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Program $program): bool
    {
        if ($program->status === 'published' || $user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        return $user->organizations()
            ->whereKey($program->organization_id)
            ->exists();
    }

    /**
     * Determine whether the user can create models for the organization.
     */
    public function create(User $user, Organization $organization): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        return $user->hasRole(User::ROLE_ORGANIZATION)
            && $this->isOrganizationManager($user, $organization);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Program $program): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        return $this->isOrganizationManager(
            $user,
            $program->organization,
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Program $program): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        return $this->isOrganizationManager(
            $user,
            $program->organization,
        );
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Program $program): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Program $program): bool
    {
        return false;
    }

    /**
     * Determine whether the user manages the organization.
     */
    private function isOrganizationManager(User $user, Organization $organization): bool
    {
        return $user->organizations()
            ->whereKey($organization->getKey())
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
