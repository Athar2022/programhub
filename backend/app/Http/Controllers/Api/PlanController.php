<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PlanController extends Controller
{
    /**
     * Display active plans for public users.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Plan::query()
                ->where('status', 'active')
                ->latest()
                ->paginate(15)
        );
    }

    /**
     * Store a newly created plan.
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        Gate::authorize('create', Plan::class);

        $plan = Plan::create([
            ...$request->validated(),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Plan created successfully.',
            'plan' => $plan,
        ], 201);
    }

    /**
     * Display an active plan for public users.
     */
    public function show(Plan $plan): JsonResponse
    {
        abort_if($plan->status !== 'active', 404);

        return response()->json([
            'plan' => $plan,
        ]);
    }

    /**
     * Update a plan.
     */
    public function update(
        UpdatePlanRequest $request,
        Plan $plan
    ): JsonResponse {
        Gate::authorize('update', $plan);

        $plan->update($request->validated());

        return response()->json([
            'message' => 'Plan updated successfully.',
            'plan' => $plan->fresh(),
        ]);
    }
}
