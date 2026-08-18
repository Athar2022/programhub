<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    /**
     * Display the authenticated user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Notification::class);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($perPage);

        $unreadCount = $user->notifications()
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Display a specific notification owned by the authenticated user.
     */
    public function show(Notification $notification): JsonResponse
    {
        Gate::authorize('view', $notification);

        return response()->json([
            'notification' => $notification,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        Gate::authorize('markAsRead', $notification);

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return response()->json([
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Notification::class);

        $updatedCount = $request->user()->notifications()
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'updated_count' => $updatedCount,
            'unread_count' => 0,
        ]);
    }
}
