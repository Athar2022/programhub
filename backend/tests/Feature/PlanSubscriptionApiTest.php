<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanSubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_returns_active_plans_only(): void
    {
        $activePlan = Plan::factory()->create([
            'name' => 'Active Plan',
            'status' => 'active',
        ]);
        Plan::factory()->create([
            'name' => 'Inactive Plan',
            'status' => 'inactive',
        ]);

        $response = $this->getJson('/api/plans');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activePlan->id)
            ->assertJsonPath('data.0.status', 'active');
    }

    public function test_public_users_cannot_view_an_inactive_plan(): void
    {
        $plan = Plan::factory()->create([
            'status' => 'inactive',
        ]);

        $response = $this->getJson("/api/plans/{$plan->id}");

        $response->assertNotFound();
    }

    public function test_platform_admin_can_create_an_active_plan(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/plans', [
            'name' => 'Professional Plan',
            'price' => 299.99,
            'max_programs' => 20,
            'max_applicants' => 500,
            'features' => ['analytics', 'custom-reports'],
            'status' => 'inactive',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('plan.name', 'Professional Plan')
            ->assertJsonPath('plan.status', 'active');

        $this->assertDatabaseHas('plans', [
            'name' => 'Professional Plan',
            'status' => 'active',
        ]);
    }

    public function test_an_organization_user_cannot_create_a_plan(): void
    {
        $organizationUser = User::factory()->create([
            'role' => User::ROLE_ORGANIZATION,
        ]);

        $response = $this->actingAs($organizationUser, 'sanctum')->postJson('/api/plans', [
            'name' => 'Unauthorized Plan',
            'price' => 100,
        ]);

        $response->assertForbidden();
    }

    public function test_platform_admin_can_deactivate_a_plan(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);
        $plan = Plan::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson(
            "/api/plans/{$plan->id}",
            [
                'status' => 'inactive',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('plan.status', 'inactive');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => 'inactive',
        ]);
    }

    public function test_organization_owner_can_create_a_subscription_for_their_organization(): void
    {
        [$organization, $owner] = $this->makeOrganizationMember('owner');
        $plan = Plan::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner, 'sanctum')->postJson(
            "/api/organizations/{$organization->id}/subscriptions",
            [
                'plan_id' => $plan->id,
                'start_date' => '2026-09-01 00:00:00',
                'end_date' => '2027-09-01 00:00:00',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('subscription.organization_id', $organization->id)
            ->assertJsonPath('subscription.plan_id', $plan->id)
            ->assertJsonPath('subscription.status', 'active');

        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_organization_admin_cannot_create_a_subscription_for_another_organization(): void
    {
        [$managedOrganization, $admin] = $this->makeOrganizationMember('admin');
        $otherOrganization = Organization::factory()->create();
        $plan = Plan::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/organizations/{$otherOrganization->id}/subscriptions",
            [
                'plan_id' => $plan->id,
                'start_date' => '2026-09-01 00:00:00',
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('subscriptions', [
            'organization_id' => $otherOrganization->id,
        ]);
        $this->assertDatabaseMissing('subscriptions', [
            'organization_id' => $managedOrganization->id,
        ]);
    }

    public function test_organization_owner_can_view_their_subscriptions(): void
    {
        [$organization, $owner] = $this->makeOrganizationMember('owner');
        $plan = Plan::factory()->create([
            'status' => 'active',
        ]);
        $subscription = Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
        ]);

        $response = $this->actingAs($owner, 'sanctum')->getJson(
            "/api/organizations/{$organization->id}/subscriptions"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'subscriptions')
            ->assertJsonPath('subscriptions.0.id', $subscription->id)
            ->assertJsonPath('subscriptions.0.plan.id', $plan->id);
    }

    public function test_an_organization_user_cannot_update_a_subscription(): void
    {
        [$organization, $owner] = $this->makeOrganizationMember('owner');
        $plan = Plan::factory()->create([
            'status' => 'active',
        ]);
        $subscription = Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $response = $this->actingAs($owner, 'sanctum')->patchJson(
            "/api/subscriptions/{$subscription->id}",
            [
                'status' => 'cancelled',
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);
    }

    public function test_platform_admin_can_update_subscription_status_and_dates(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);
        $organization = Organization::factory()->create();
        $originalPlan = Plan::factory()->create([
            'status' => 'active',
        ]);
        $differentPlan = Plan::factory()->create([
            'status' => 'active',
        ]);
        $subscription = Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $originalPlan->id,
            'status' => 'active',
        ]);
        $response = $this->actingAs($admin, 'sanctum')->patchJson(
            "/api/subscriptions/{$subscription->id}",
            [
                'status' => 'expired',
                'end_date' => '2027-09-01 00:00:00',
                'plan_id' => $differentPlan->id,
                'organization_id' => Organization::factory()->create()->id,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('subscription.status', 'expired')
            ->assertJsonPath('subscription.plan_id', $originalPlan->id)
            ->assertJsonPath('subscription.organization_id', $organization->id);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'expired',
            'plan_id' => $originalPlan->id,
            'organization_id' => $organization->id,
        ]);
    }

    private function makeOrganizationMember(string $membershipRole): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_ORGANIZATION,
        ]);

        $organization->users()->attach($user->id, [
            'role' => $membershipRole,
        ]);

        return [$organization, $user];
    }
}
