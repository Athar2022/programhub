<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement([
            'draft',
            'submitted',
            'under_review',
            'accepted',
            'rejected',
            'waitlisted',
        ]);

        return [
            'program_id' => Program::factory(),
            'applicant_id' => User::factory(),
            'status' => $status,
            'submitted_at' => $status === 'draft'
                ? null
                : fake()->dateTimeBetween('-6 months', 'now'),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
