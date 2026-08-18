<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends Controller
{
    /**
     * Display subscriptions for an organization.
     */
    public function index(Organization $organization): JsonResponse
    {
        Gate::authorize('viewAny', [Subscription::class, $organization]);

        return response()->json([
            'subscriptions' => $organization
                ->subscriptions()
                ->with('plan')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Store a new subscription for an organization.
     */
    public function store(
        StoreSubscriptionRequest $request,
        Organization $organization
    ): JsonResponse {
        Gate::authorize('create', [Subscription::class, $organization]);

        $validated = $request->validated();

        $subscription = $organization->subscriptions()->create([
            'plan_id' => $validated['plan_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Subscription created successfully.',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }

    /**
     * Display a specific subscription.
     */
    public function show(Subscription $subscription): JsonResponse
    {
        Gate::authorize('view', $subscription);

        return response()->json([
            'subscription' => $subscription->load(['plan', 'organization']),
        ]);
    }

    /**
     * Update subscription status or dates.
     */
    public function update(
        UpdateSubscriptionRequest $request,
        Subscription $subscription
    ): JsonResponse {
        Gate::authorize('update', $subscription);

        $subscription->update($request->validated());

        return response()->json([
            'message' => 'Subscription updated successfully.',
            'subscription' => $subscription->fresh()->load(['plan', 'organization']),
        ]);
    }
}
