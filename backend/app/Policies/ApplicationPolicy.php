<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
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
    public function view(User $user, Application $application): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        if ($application->applicant_id === $user->id) {
            return true;
        }

        return $this->belongsToApplicationTeam($user, $application);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_APPLICANT);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Application $application): bool
    {
        return $application->applicant_id === $user->id
            && $application->status === 'draft'
            && $user->hasRole(User::ROLE_APPLICANT);
    }

    /**
     * Determine whether the user can submit the model.
     */
    public function submit(User $user, Application $application): bool
    {
        return $application->applicant_id === $user->id
            && $application->status === 'draft'
            && $user->hasRole(User::ROLE_APPLICANT);
    }

    /**
     * Determine whether the user can review the model.
     */
    public function review(User $user, Application $application): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        if (! in_array($application->status, ['submitted', 'under_review'], true)) {
            return false;
        }

        return $this->belongsToApplicationTeam($user, $application);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Application $application): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        return $application->applicant_id === $user->id
            && $application->status === 'draft'
            && $user->hasRole(User::ROLE_APPLICANT);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Application $application): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Application $application): bool
    {
        return false;
    }

    /**
     * Determine whether the user belongs to the application's organization team.
     */
    private function belongsToApplicationTeam(User $user, Application $application): bool
    {
        $organization = $application->program?->organization;

        if ($organization === null) {
            return false;
        }

        return $user->organizations()
            ->whereKey($organization->getKey())
            ->wherePivotIn('role', ['owner', 'admin', 'reviewer'])
            ->exists();
    }
}
