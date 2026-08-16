<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_applicant_can_create_a_draft_application(): void
    {
        $applicant = $this->createApplicant();
        $program = $this->createPublishedProgram();

        $response = $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/programs/{$program->id}/applications", [
                'notes' => 'Initial application notes.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('application.program_id', $program->id)
            ->assertJsonPath('application.applicant_id', $applicant->id)
            ->assertJsonPath('application.status', 'draft')
            ->assertJsonPath('application.notes', 'Initial application notes.');

        $this->assertDatabaseHas('applications', [
            'program_id' => $program->id,
            'applicant_id' => $applicant->id,
            'status' => 'draft',
        ]);
    }

    public function test_an_applicant_cannot_create_a_duplicate_application(): void
    {
        $applicant = $this->createApplicant();
        $program = $this->createPublishedProgram();
        $this->makeTestApplication($applicant, $program);

        $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/programs/{$program->id}/applications", [
                'notes' => 'Duplicate application.',
            ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'You already have an application for this program.',
            ]);
    }

    public function test_an_applicant_can_update_a_draft_application(): void
    {
        $applicant = $this->createApplicant();
        $program = $this->createPublishedProgram();
        $application = $this->makeTestApplication($applicant, $program);

        $this->actingAs($applicant, 'sanctum')
            ->patchJson("/api/applications/{$application->id}", [
                'notes' => 'Updated application notes.',
            ])
            ->assertOk()
            ->assertJsonPath('application.notes', 'Updated application notes.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'notes' => 'Updated application notes.',
            'status' => 'draft',
        ]);
    }

    public function test_an_applicant_can_submit_a_draft_application(): void
    {
        $applicant = $this->createApplicant();
        $program = $this->createPublishedProgram();
        $application = $this->makeTestApplication($applicant, $program);

        $response = $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/applications/{$application->id}/submit");

        $response
            ->assertOk()
            ->assertJsonPath('application.status', 'submitted');

        $this->assertNotNull($response->json('application.submitted_at'));
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'submitted',
        ]);
    }

    public function test_an_applicant_cannot_review_an_application(): void
    {
        $applicant = $this->createApplicant();
        $program = $this->createPublishedProgram();
        $application = $this->makeTestApplication($applicant, $program, [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($applicant, 'sanctum')
            ->patchJson("/api/applications/{$application->id}/review", [
                'status' => 'under_review',
            ])
            ->assertForbidden();
    }

    public function test_an_organization_reviewer_can_review_a_submitted_application(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $reviewer = $this->createOrganizationMember($organization, 'reviewer');
        $applicant = $this->createApplicant();
        $program = $this->createPublishedProgram([], $organization);
        $application = $this->makeTestApplication(
            $applicant, $program, [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($reviewer, 'sanctum')
            ->patchJson("/api/applications/{$application->id}/review", [
                'status' => 'under_review',
                'notes' => 'Application moved to review.',
            ])
            ->assertOk()
            ->assertJsonPath('application.status', 'under_review')
            ->assertJsonPath('application.notes', 'Application moved to review.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'under_review',
            'notes' => 'Application moved to review.',
        ]);
    }

    public function test_a_reviewer_from_another_organization_cannot_review_the_application(): void
    {
        $programOrganization = Organization::factory()->create(['status' => 'active']);
        $otherOrganization = Organization::factory()->create(['status' => 'active']);
        $reviewer = $this->createOrganizationMember($otherOrganization, 'reviewer');
        $applicant = $this->createApplicant();
        $program = $this->createPublishedProgram([], $programOrganization);
        $application = $this->makeTestApplication(
            $applicant, $program, [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($reviewer, 'sanctum')
            ->patchJson("/api/applications/{$application->id}/review", [
                'status' => 'under_review',
            ])
            ->assertForbidden();
    }

    private function createApplicant(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_APPLICANT,
            'status' => 'active',
        ]);
    }

    private function createOrganizationMember(
        Organization $organization,
        string $membershipRole,
    ): User {
        $user = User::factory()->create([
            'role' => User::ROLE_ORGANIZATION,
            'status' => 'active',
        ]);

        $organization->users()->attach($user->id, [
            'role' => $membershipRole,
        ]);

        return $user;
    }

    private function createPublishedProgram(
        array $attributes = [],
        ?Organization $organization = null,
    ): Program {
        $organization ??= Organization::factory()->create(['status' => 'active']);

        return Program::create(array_merge([
            'organization_id' => $organization->id,
            'title' => 'Test Application Program',
            'type' => 'training',
            'description' => 'A program used for application API tests.',
            'status' => 'published',
        ], $attributes));
    }

    private function makeTestApplication(
        User $applicant,
        Program $program,
        array $attributes = [],
    ): Application {
        return Application::create(array_merge([
            'program_id' => $program->id,
            'applicant_id' => $applicant->id,
            'status' => 'draft',
            'notes' => null,
        ], $attributes));
    }
}
