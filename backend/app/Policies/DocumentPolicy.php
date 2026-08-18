<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view documents for an application.
     */
    public function viewAny(User $user, Application $application): bool
    {
        return $this->canAccessApplication($user, $application);
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $this->canAccessApplication($user, $document->application);
    }

    /**
     * Determine whether the user can upload a document to an application.
     */
    public function create(User $user, Application $application): bool
    {
        return $user->hasRole(User::ROLE_APPLICANT)
            && $application->applicant_id === $user->id
            && in_array($application->status, ['draft', 'submitted'], true);
    }
    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        $application = $document->application;

        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        return $user->hasRole(User::ROLE_APPLICANT)
            && $application->applicant_id === $user->id
            && $application->status !== 'accepted';
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the document.
     */
    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the document.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can access an application and its documents.
     */
    private function canAccessApplication(User $user, Application $application): bool
    {
        if ($user->hasRole(User::ROLE_PLATFORM_ADMIN)) {
            return true;
        }

        if (
            $user->hasRole(User::ROLE_APPLICANT)
            && $application->applicant_id === $user->id
        ) {
            return true;
        }

        $organization = $application->program?->organization;

        if ($organization === null) {
            return false;
        }

        return $organization->users()
            ->whereKey($user->id)
            ->wherePivotIn('role', ['owner', 'admin', 'reviewer'])
            ->exists();
    }
}
