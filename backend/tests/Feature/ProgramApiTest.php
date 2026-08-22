<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_returns_published_programs_only(): void
    {
        $publishedProgram = $this->createProgram(['status' => 'published']);
        $this->createProgram(['status' => 'draft']);

        $response = $this->getJson('/api/programs');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publishedProgram->id)
            ->assertJsonPath('data.0.status', 'published');
    }

    public function test_public_users_cannot_view_a_draft_program(): void
    {
        $program = $this->createProgram(['status' => 'draft']);

        $this->getJson("/api/programs/{$program->id}")
            ->assertNotFound();
    }

    public function test_organization_owner_can_create_a_program(): void
    {
        [$user, $organization] = $this->createOrganizationOwner();

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/organizations/{$organization->id}/programs",
            [
                'title' => 'Leadership Program',
                'type' => 'training',
                'description' => 'A practical leadership training program.',
                'delivery_mode' => 'hybrid',
                'application_start' => '2026-08-20 09:00:00',
                'application_deadline' => '2026-08-30 23:59:59',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'capacity' => 25,
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonPath('program.organization_id', $organization->id)
            ->assertJsonPath('program.title', 'Leadership Program')
            ->assertJsonPath('program.status', 'draft');

        $this->assertDatabaseHas('programs', [
            'organization_id' => $organization->id,
            'title' => 'Leadership Program',
            'status' => 'draft',
        ]);
    }

    public function test_an_applicant_cannot_create_a_program(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
            'status' => 'active',
        ]);
        $organization = Organization::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/organizations/{$organization->id}/programs", [
                'title' => 'Unauthorized Program',
                'type' => 'training',
            ])
            ->assertForbidden();
    }

    public function test_organization_owner_can_update_its_program(): void
    {
        [$user, $organization] = $this->createOrganizationOwner();
        $program = $this->createProgram([
            'organization_id' => $organization->id,
            'title' => 'Original Title',
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/programs/{$program->id}", [
                'title' => 'Updated Title',
            ])
            ->assertOk()
            ->assertJsonPath('program.title', 'Updated Title');

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_a_manager_from_another_organization_cannot_update_the_program(): void
    {
        [$user, $otherOrganization] = $this->createOrganizationOwner();
        $program = $this->createProgram();

        $this->assertNotSame($otherOrganization->id, $program->organization_id);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/programs/{$program->id}", [
                'title' => 'Unauthorized Update',
            ])
            ->assertForbidden();
    }

    public function test_organization_owner_can_delete_its_program(): void
    {
        [$user, $organization] = $this->createOrganizationOwner();
        $program = $this->createProgram([
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/programs/{$program->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Program deleted successfully.',
            ]);

        $this->assertDatabaseMissing('programs', [
            'id' => $program->id,
        ]);
    }


    public function test_organization_owner_can_list_its_programs(): void
    {
        [$user, $organization] = $this->createOrganizationOwner();

        Program::create([
            'organization_id' => $organization->id,
            'title' => 'Draft Program',
            'type' => 'training',
            'status' => 'draft',
        ]);
        Program::create([
            'organization_id' => $organization->id,
            'title' => 'Published Program',
            'type' => 'grant',
            'status' => 'published',
        ]);
        $this->createProgram();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/programs");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.organization_id', $organization->id)
            ->assertJsonPath('data.1.organization_id', $organization->id);
    }

    public function test_an_applicant_cannot_list_organization_programs(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
            'status' => 'active',
        ]);
        $organization = Organization::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/programs")
            ->assertForbidden();
    }

    public function test_a_manager_from_another_organization_cannot_list_programs(): void
    {
        [$user] = $this->createOrganizationOwner();
        $organization = Organization::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/programs")
            ->assertForbidden();
    }


    public function test_organization_owner_can_publish_a_draft_program(): void
    {
        [$user, $organization] = $this->createOrganizationOwner();

        $program = Program::create([
            'organization_id' => $organization->id,
            'title' => 'Program Ready to Publish',
            'type' => 'training',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/programs/{$program->id}", [
                'status' => 'published',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('program.status', 'published');

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'status' => 'published',
        ]);
    }

    public function test_program_status_must_be_draft_or_published(): void
    {
        [$user, $organization] = $this->createOrganizationOwner();

        $program = Program::create([
            'organization_id' => $organization->id,
            'title' => 'Program With Invalid Status',
            'type' => 'training',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/programs/{$program->id}", [
                'status' => 'closed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    private function createProgram(array $attributes = []): Program
    {
        $organization = Organization::factory()->create();

        return Program::create(array_merge([
            'organization_id' => $organization->id,
            'title' => 'Community Program',
            'type' => 'grant',
            'description' => 'A sample program for testing.',
            'status' => 'published',
        ], $attributes));
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function createOrganizationOwner(): array
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ORGANIZATION,
            'status' => 'active',
        ]);
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        return [$user, $organization];
    }
}
