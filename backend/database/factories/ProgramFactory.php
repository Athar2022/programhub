<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $applicationStart = fake()->dateTimeBetween('-1 month', '+1 month');
        $applicationDeadline = fake()->dateTimeBetween($applicationStart, '+3 months');
        $startDate = fake()->dateTimeBetween($applicationDeadline, '+6 months');
        $endDate = fake()->dateTimeBetween($startDate, '+12 months');

        return [
            'organization_id' => Organization::factory(),
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(['grant', 'training', 'scholarship']),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'delivery_mode' => fake()->randomElement(['online', 'onsite', 'hybrid']),
            'application_start' => $applicationStart,
            'application_deadline' => $applicationDeadline,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'capacity' => fake()->numberBetween(10, 200),
            'status' => fake()->randomElement(['draft', 'published', 'closed']),
        ];
    }
}
