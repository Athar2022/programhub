<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Starter',
                'Professional',
                'Enterprise',
            ]),
            'price' => fake()->randomFloat(2, 0, 499.99),
            'max_programs' => fake()->numberBetween(1, 100),
            'max_applicants' => fake()->numberBetween(25, 5000),
            'features' => [
                'analytics' => fake()->boolean(),
                'custom_branding' => fake()->boolean(),
                'priority_support' => fake()->boolean(),
            ],
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
