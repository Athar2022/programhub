<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Organization;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Display published programs for public users.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $programs = Program::query()
            ->with('organization')
            ->where('status', 'published')
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($programs);
    }

    /**
     * Store a newly created program for an organization.
     */
    public function store(
        StoreProgramRequest $request,
        Organization $organization,
    ): JsonResponse {
        $program = $organization->programs()->create([
            ...$request->validated(),
            'status' => 'draft',
        ]);

        return response()->json([
            'message' => 'Program created successfully.',
            'program' => $program->load('organization'),
        ], 201);
    }

    /**
     * Display the specified program.
     */
    public function show(Request $request, Program $program): JsonResponse
    {
        if ($program->status !== 'published') {
            $user = $request->user();

            if (!$user || !$user->can('view', $program)) {
                return response()->json([
                    'message' => 'Program not found.',
                ], 404);
            }
        }

        return response()->json([
            'program' => $program->load('organization'),
        ]);
    }

    /**
     * Update the specified program.
     */
    public function update(
        UpdateProgramRequest $request,
        Program $program,
    ): JsonResponse {
        $program->update($request->validated());

        return response()->json([
            'message' => 'Program updated successfully.',
            'program' => $program->fresh()->load('organization'),
        ]);
    }

    /**
     * Remove the specified program.
     */
    public function destroy(Program $program): JsonResponse
    {
        $program->delete();

        return response()->json([
            'message' => 'Program deleted successfully.',
        ]);
    }
}
