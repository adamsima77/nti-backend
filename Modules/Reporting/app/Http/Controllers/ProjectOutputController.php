<?php

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Modules\Reporting\Models\ProjectOutput;
use Modules\Reporting\Http\Requests\StoreProjectOutputRequest;
use Modules\Reporting\Http\Requests\UpdateProjectOutputRequest;
use Modules\Reporting\Http\Resources\ProjectOutputResource;
use Modules\Applications\Models\Application;

class ProjectOutputController extends Controller
{
    /**
     * Display a listing of outputs for an application
     */
    public function index(Request $request, int $applicationId): JsonResponse
    {
        $this->authorize('view', Application::findOrFail($applicationId));

        $outputs = ProjectOutput::query()
            ->where('application_id', $applicationId)
            ->with('documents')
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->where('output_name', 'like', '%' . $request->query('search') . '%')
            )
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where('status', $request->query('status'))
            )
            ->orderBy('id')
            ->paginate(15);

        return ProjectOutputResource::collection($outputs)->response();
    }

    /**
     * Display a specific output
     */
    public function show(int $id): JsonResponse
    {
        $output = ProjectOutput::with('documents')->findOrFail($id);

        $this->authorize('view', $output);

        return (new ProjectOutputResource($output))->response();
    }

    /**
     * Store a newly created output
     */
    public function store(StoreProjectOutputRequest $request): JsonResponse
    {
        $this->authorize('createForApplication', [
            Application::class,
            Application::findOrFail($request->application_id),
        ]);

        $validated = $request->validated();
        $documentIds = $validated['document_ids'] ?? [];
        unset($validated['document_ids']);

        $output = ProjectOutput::create($validated);

        if (!empty($documentIds)) {
            $output->documents()->sync($documentIds);
        }

        $output->load('documents');

        return (new ProjectOutputResource($output))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified output
     */
    public function update(UpdateProjectOutputRequest $request, int $id): JsonResponse
    {
        $output = ProjectOutput::findOrFail($id);

        $this->authorize('update', $output);

        $validated = $request->validated();
        $documentIds = $validated['document_ids'] ?? null;
        unset($validated['document_ids']);

        $output->update($validated);

        if ($documentIds !== null) {
            $output->documents()->sync($documentIds);
        }

        $output->load('documents');

        return (new ProjectOutputResource($output))->response();
    }

    /**
     * Delete the specified output
     */
    public function destroy(int $id): JsonResponse
    {
        $output = ProjectOutput::findOrFail($id);

        $this->authorize('delete', $output);

        $output->delete();

        return response()->json(['message' => 'Výstup projektu bol úspešne odstránený']);
    }

    /**
     * Mark output as delivered
     */
    public function markAsDelivered(Request $request, int $id): JsonResponse
    {
        $output = ProjectOutput::findOrFail($id);

        $this->authorize('markAsDelivered', $output);

        $output->markAsDelivered();

        return (new ProjectOutputResource($output))->response();
    }

    /**
     * Get output statistics for an application
     */
    public function statistics(int $applicationId): JsonResponse
    {
        $this->authorize('view', Application::findOrFail($applicationId));

        $outputs = ProjectOutput::where('application_id', $applicationId)->get();

        $stats = [
            'total_outputs' => $outputs->count(),
            'pending' => $outputs->where('status', 'pending')->count(),
            'completed' => $outputs->where('status', 'completed')->count(),
            'delivered' => $outputs->where('status', 'delivered')->count(),
            'on_time' => $outputs->filter(fn ($o) => $o->isOnTime())->count(),
            'overdue' => $outputs->filter(fn ($o) => $o->isOverdue())->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Attach documents to an output
     */
    public function attachDocuments(Request $request, int $id): JsonResponse
    {
        $output = ProjectOutput::findOrFail($id);

        $this->authorize('update', $output);

        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer|exists:document,id',
        ]);

        $output->documents()->sync($validated['document_ids']);

        $output->load('documents');

        return (new ProjectOutputResource($output))->response();
    }

    /**
     * Detach documents from an output
     */
    public function detachDocuments(Request $request, int $id): JsonResponse
    {
        $output = ProjectOutput::findOrFail($id);

        $this->authorize('update', $output);

        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'integer|exists:document,id',
        ]);

        $output->documents()->detach($validated['document_ids']);

        $output->load('documents');

        return (new ProjectOutputResource($output))->response();
    }
}
