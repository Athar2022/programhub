<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewApplicationRequest;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\SubmitApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * Display applications available to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Application::query()
            ->with(['program.organization', 'applicant'])
            ->latest('created_at');

        if ($user->hasRole('platform_admin')) {
            // Platform administrators can view all applications.
        } elseif ($user->hasRole('applicant')) {
            $query->where('applicant_id', $user->id);
        } else {
            $organizationIds = $user->organizations()
                ->wherePivotIn('role', ['owner', 'admin', 'reviewer'])
                ->pluck('organizations.id');

            $query->whereHas('program', function ($programQuery) use ($organizationIds): void {
                $programQuery->whereIn('organization_id', $organizationIds);
            });
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a new draft application for a published program.
     */
    public function store(
        StoreApplicationRequest $request,
        Program $program,
    ): JsonResponse {
        $applicantId = $request->user()->id;
        $alreadyExists = Application::query()
            ->where('program_id', $program->id)
            ->where('applicant_id', $applicantId)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'message' => 'You already have an application for this program.',
            ], 422);
        }

        $application = $program->applications()->create([
            'applicant_id' => $applicantId,
            'status' => 'draft',
            'notes' => $request->validated()['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Application draft created successfully.',
            'application' => $application->load(['program.organization', 'applicant']),
        ], 201);
    }

    /**
     * Display the specified application.
     */
    public function show(Request $request, Application $application): JsonResponse
    {
        if (! $request->user()->can('view', $application)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        return response()->json([
            'application' => $application->load(['program.organization', 'applicant', 'documents']),
        ]);
    }

    /**
     * Update a draft application owned by the authenticated applicant.
     */
    public function update(
        UpdateApplicationRequest $request,
        Application $application,
    ): JsonResponse {
        $application->update($request->validated());

        return response()->json([
            'message' => 'Application updated successfully.',
            'application' => $application->fresh()->load(['program.organization', 'applicant']),
        ]);
    }

    /**
     * Submit a draft application for review.
     */
    public function submit(
        SubmitApplicationRequest $request,
        Application $application,
    ): JsonResponse {
        $application->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application' => $application->fresh()->load(['program.organization', 'applicant']),
        ]);
    }

    /**
     * Review an application as an organization team member or platform administrator.
     */
    public function review(
        ReviewApplicationRequest $request,
        Application $application,
    ): JsonResponse {
        $application->update($request->validated());

        return response()->json([
            'message' => 'Application reviewed successfully.',
            'application' => $application->fresh()->load(['program.organization', 'applicant']),
        ]);
    }

    /**
     * Remove a draft application owned by the authenticated applicant.
     */
    public function destroy(Request $request, Application $application): JsonResponse
    {
        if (! $request->user()->can('delete', $application)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $application->delete();

        return response()->json([
            'message' => 'Application deleted successfully.',
        ]);
    }
}
