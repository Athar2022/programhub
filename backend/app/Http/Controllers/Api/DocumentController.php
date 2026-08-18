<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Application;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display the documents attached to an application.
     */
    public function index(Application $application): JsonResponse
    {
        Gate::authorize('viewAny', [Document::class, $application]);

        return response()->json([
            'documents' => $application->documents()->latest()->get(),
        ]);
    }

    /**
     * Store a newly uploaded document for an application.
     */
    public function store(
        StoreDocumentRequest $request,
        Application $application
    ): JsonResponse {
        Gate::authorize('create', [Document::class, $application]);

        $file = $request->file('file');
        $filePath = $file->store('documents', 'public');

        $document = $application->documents()->create([
            'name' => $request->input('name') ?: $file->getClientOriginalName(),
            'type' => $file->getMimeType(),
            'file_path' => $filePath,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'document' => $document,
        ], 201);
    }

    /**
     * Display a specific document.
     */
    public function show(Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        return response()->json([
            'document' => $document,
        ]);
    }

    /**
     * Remove a document and its stored file.
     */
    public function destroy(Document $document): JsonResponse
    {
        Gate::authorize('delete', $document);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }
}
