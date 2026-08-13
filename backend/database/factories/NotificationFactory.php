<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isRead = fake()->boolean(40);

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement([
                'application_submitted',
                'application_status_changed',
                'program_deadline_reminder',
                'subscription_updated',
            ]),
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(12),
            'data' => [
                'reference' => fake()->uuid(),
                'action' => fake()->randomElement([
                    'view_application',
                    'view_program',
                    'view_subscription',
                ]),
            ],
            'read_at' => $isRead
                ? fake()->dateTimeBetween('-1 month', 'now')
                : null,
        ];
    }
}
