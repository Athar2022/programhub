<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a user.
     */
    public function createForUser(
        User $user,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
    ): Notification {
        return $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
