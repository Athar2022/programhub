<?php

namespace Database\Factories;

use App\Models\ApplicantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicantProfile>
 */
class ApplicantProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'education' => fake()->randomElement([
                'Bachelor\'s Degree',
                'Master\'s Degree',
                'Doctorate',
                'Professional Diploma',
            ]),
            'major' => fake()->randomElement([
                'Computer Science',
                'Business Administration',
                'Engineering',
                'Public Administration',
                'Information Systems',
            ]),
            'graduation_year' => fake()->numberBetween(now()->year - 10, now()->year + 2),
            'experience_years' => fake()->numberBetween(0, 10),
            'skills' => fake()->randomElements([
                'Communication',
                'Project Management',
                'Data Analysis',
                'Leadership',
                'Technical Writing',
                'Problem Solving',
                'Research',
                'Presentation',
            ], fake()->numberBetween(2, 5)),
            'languages' => fake()->randomElements([
                'Arabic',
                'English',
                'French',
                'Spanish',
            ], fake()->numberBetween(1, 3)),
        ];
    }
}
