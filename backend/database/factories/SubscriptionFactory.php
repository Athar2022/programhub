<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement([
            'active',
            'cancelled',
            'expired',
            'past_due',
        ]);
        $startDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'organization_id' => Organization::factory(),
            'plan_id' => Plan::factory(),
            'start_date' => $startDate,
            'end_date' => $status === 'active'
                ? null
                : fake()->dateTimeBetween($startDate, 'now'),
            'status' => $status,
        ];
    }
}
