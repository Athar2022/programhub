<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::query()
            ->where('status', 'active')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $organizations,
        ]);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $this->authorize('create', Organization::class);

        $organization = Organization::create($request->validated());

        return response()->json([
            'message' => 'Organization created successfully.',
            'data' => $organization,
        ], 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return response()->json([
            'data' => $organization,
        ]);
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('update', $organization);

        $organization->update($request->validated());

        return response()->json([
            'message' => 'Organization updated successfully.',
            'data' => $organization->fresh(),
        ]);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->json([
            'message' => 'Organization deleted successfully.',
        ]);
    }
}
