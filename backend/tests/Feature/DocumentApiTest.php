<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_applicant_can_upload_a_document_to_a_draft_application(): void
    {
        Storage::fake('public');

        $applicant = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $application = $this->makeTestApplication($applicant);
        $file = UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf');

        $response = $this->actingAs($applicant, 'sanctum')->post(
            "/api/applications/{$application->id}/documents",
            [
                'file' => $file,
                'name' => 'Passport Copy',
            ],
            [
                'Accept' => 'application/json',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('document.name', 'Passport Copy')
            ->assertJsonPath('document.type', 'application/pdf');

        $filePath = $response->json('document.file_path');

        $this->assertDatabaseHas('documents', [
            'application_id' => $application->id,
            'name' => 'Passport Copy',
            'type' => 'application/pdf',
            'file_path' => $filePath,
        ]);

        Storage::disk('public')->assertExists($filePath);
    }

    public function test_an_applicant_can_list_documents_for_their_application(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $application = $this->makeTestApplication($applicant);

        $application->documents()->create([
            'name' => 'Identity Document',
            'type' => 'application/pdf',
            'file_path' => 'documents/identity.pdf',
        ]);

        $response = $this->actingAs($applicant, 'sanctum')->getJson(
            "/api/applications/{$application->id}/documents"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'documents')
            ->assertJsonPath('documents.0.name', 'Identity Document');
    }

    public function test_an_organization_reviewer_can_view_application_documents(): void
    {
        $organization = Organization::factory()->create();
        $reviewer = User::factory()->create([
            'role' => User::ROLE_ORGANIZATION,
        ]);
        $organization->users()->attach($reviewer->id, [
            'role' => 'reviewer',
        ]);

        $program = Program::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'published',
        ]);
        $applicant = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $application = $this->makeTestApplication($applicant, $program);

        $application->documents()->create([
            'name' => 'Resume',
            'type' => 'application/pdf',
            'file_path' => 'documents/resume.pdf',
        ]);

        $response = $this->actingAs($reviewer, 'sanctum')->getJson(
            "/api/applications/{$application->id}/documents"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'documents')
            ->assertJsonPath('documents.0.name', 'Resume');
    }

    public function test_an_unrelated_applicant_cannot_view_application_documents(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $otherApplicant = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $application = $this->makeTestApplication($owner);

        $response = $this->actingAs($otherApplicant, 'sanctum')->getJson(
            "/api/applications/{$application->id}/documents"
        );

        $response->assertForbidden();
    }

    public function test_an_applicant_can_view_a_specific_document(): void
    {
        $applicant = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $application = $this->makeTestApplication($applicant);
        $document = $application->documents()->create([
            'name' => 'Certificate',
            'type' => 'application/pdf',
            'file_path' => 'documents/certificate.pdf',
        ]);

        $response = $this->actingAs($applicant, 'sanctum')->getJson(
            "/api/documents/{$document->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('document.id', $document->id)
            ->assertJsonPath('document.name', 'Certificate');
    }

    public function test_an_applicant_can_delete_their_document(): void
    {
        Storage::fake('public');

        $applicant = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $application = $this->makeTestApplication($applicant);
        $filePath = 'documents/delete-me.pdf';
        Storage::disk('public')->put($filePath, 'test document');

        $document = $application->documents()->create([
            'name' => 'Document to Delete',
            'type' => 'application/pdf',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($applicant, 'sanctum')->deleteJson(
            "/api/documents/{$document->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Document deleted successfully.');

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_an_applicant_cannot_delete_a_document_after_application_acceptance(): void
    {
        Storage::fake('public');

        $applicant = User::factory()->create([
            'role' => User::ROLE_APPLICANT,
        ]);
        $application = $this->makeTestApplication($applicant, null, 'accepted');
        $document = $application->documents()->create([
            'name' => 'Accepted Application Document',
            'type' => 'application/pdf',
            'file_path' => 'documents/accepted.pdf',
        ]);

        $response = $this->actingAs($applicant, 'sanctum')->deleteJson(
            "/api/documents/{$document->id}"
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
        ]);
    }

    private function makeTestApplication(
        User $applicant,
        ?Program $program = null,
        string $status = 'draft'
    ): Application {
        $program ??= Program::factory()->create([
            'status' => 'published',
        ]);

        return Application::factory()->create([
            'program_id' => $program->id,
            'applicant_id' => $applicant->id,
            'status' => $status,
        ]);
    }
}
