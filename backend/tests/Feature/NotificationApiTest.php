<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_their_notifications_with_unread_count(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstUnread = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        $secondUnread = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        $readNotification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => now()->subHour(),
        ]);
        $otherNotification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications');

        $response
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonCount(3, 'notifications.data')
            ->assertJsonFragment(['id' => $firstUnread->id])
            ->assertJsonFragment(['id' => $secondUnread->id])
            ->assertJsonFragment(['id' => $readNotification->id])
            ->assertJsonMissing(['id' => $otherNotification->id]);
    }

    public function test_unauthenticated_user_cannot_list_notifications(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    public function test_user_can_view_their_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}");

        $response
            ->assertOk()
            ->assertJsonPath('notification.id', $notification->id)
            ->assertJsonPath('notification.user_id', $user->id);
    }

    public function test_user_cannot_view_another_users_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertForbidden();
    }

    public function test_user_can_mark_their_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response
            ->assertOk()
            ->assertJsonPath('notification.id', $notification->id)
            ->assertJsonPath('notification.read_at', fn ($readAt) => $readAt !== null);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertForbidden();
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    public function test_user_can_mark_all_their_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstUnread = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        $secondUnread = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        $alreadyRead = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => now()->subHour(),
        ]);
        $otherUnread = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/notifications/read-all');

        $response
            ->assertOk()
            ->assertJsonPath('updated_count', 2)
            ->assertJsonPath('unread_count', 0);

        $this->assertDatabaseMissing('notifications', [
            'id' => $firstUnread->id,
            'read_at' => null,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'id' => $secondUnread->id,
            'read_at' => null,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $alreadyRead->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $otherUnread->id,
            'read_at' => null,
        ]);
    }
}
