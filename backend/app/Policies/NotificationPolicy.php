<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    /**
     * Determine whether the user can view any notifications.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the notification.
     */
    public function view(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    /**
     * Determine whether the user can mark the notification as read.
     */
    public function markAsRead(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
