<?php

namespace Database\Seeders;

use App\Models\ApplicantProfile;
use App\Models\Application;
use App\Models\Document;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Program;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $plans = collect([
            [
                'name' => 'Starter',
                'price' => 29.99,
                'max_programs' => 5,
                'max_applicants' => 100,
                'features' => [
                    'analytics' => false,
                    'custom_branding' => false,
                    'priority_support' => false,
                ],
                'status' => 'active',
            ],
            [
                'name' => 'Professional',
                'price' => 99.99,
                'max_programs' => 25,
                'max_applicants' => 1000,
                'features' => [
                    'analytics' => true,
                    'custom_branding' => true,
                    'priority_support' => false,
                ],
                'status' => 'active',
            ],
            [
                'name' => 'Enterprise',
                'price' => 249.99,
                'max_programs' => 100,
                'max_applicants' => 10000,
                'features' => [
                    'analytics' => true,
                    'custom_branding' => true,
                    'priority_support' => true,
                ],
                'status' => 'active',
            ],
        ])->map(function (array $attributes): Plan {
            return Plan::query()->firstOrCreate(
                ['name' => $attributes['name']],
                $attributes
            );
        });

        $platformAdmin = User::factory()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@programhub.test',
            'role' => 'platform_admin',
            'status' => 'active',
        ]);

        $organizations = Organization::factory(3)->create();
        $organizationUsers = User::factory(6)->create([
            'role' => 'organization',
            'status' => 'active',
        ]);

        $organizations->each(function (Organization $organization, int $index) use ($organizationUsers): void {
            $owner = $organizationUsers[$index * 2];
            $reviewer = $organizationUsers[($index * 2) + 1];

            $organization->users()->attach([
                $owner->id => ['role' => 'owner'],
                $reviewer->id => ['role' => 'reviewer'],
            ]);
        });

        $applicants = User::factory(5)->create([
            'role' => 'applicant',
            'status' => 'active',
        ]);

        $applicants->each(function (User $applicant): void {
            ApplicantProfile::factory()->create([
                'user_id' => $applicant->id,
            ]);
        });

        $programs = $organizations->flatMap(function (Organization $organization) {
            return Program::factory(2)
                ->for($organization)
                ->create();
        })->values();

        $applications = $applicants->values()->map(function (User $applicant, int $index) use ($programs): Application {
            $program = $programs[$index % $programs->count()];

            return Application::factory()
                ->for($program)
                ->for($applicant, 'applicant')
                ->create([
                    'status' => 'submitted',
                    'submitted_at' => now()->subDays($index + 1),
                ]);
        });

        $applications->each(function (Application $application): void {
            Document::factory(2)
                ->for($application)
                ->create();
        });

        $organizations->each(function (Organization $organization) use ($plans): void {
            Subscription::factory()
                ->for($organization)
                ->for($plans->random())
                ->create([
                    'start_date' => now()->subYear(),
                    'end_date' => now()->subMonths(2),
                    'status' => 'expired',
                ]);

            Subscription::factory()
                ->for($organization)
                ->for($plans->random())
                ->create([
                    'start_date' => now()->subMonth(),
                    'end_date' => null,
                    'status' => 'active',
                ]);
        });

        $applicants->each(function (User $applicant): void {
            Notification::factory(2)
                ->for($applicant, 'user')
                ->create();
        });
    }
}
