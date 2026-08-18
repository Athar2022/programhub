<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_unread_notification_for_the_given_user(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);

        $notification = $service->createForUser(
            $user,
            'application_submitted',
            'Application submitted',
            'Your application was submitted successfully.',
            [
                'application_id' => 123,
                'action' => 'view_application',
            ],
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame($user->id, $notification->user_id);
        $this->assertSame('application_submitted', $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertSame([
            'application_id' => 123,
            'action' => 'view_application',
        ], $notification->data);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => 'application_submitted',
            'title' => 'Application submitted',
            'read_at' => null,
        ]);
    }
}
