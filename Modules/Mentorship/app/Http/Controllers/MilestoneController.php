<?php

namespace Modules\Mentorship\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Mentorship\Models\Milestone;

class MilestoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $milestones = Milestone::query()
            ->with(['application:id,name'])
            ->when(
                $request->filled('project_id'),
                fn ($query) => $query->where('project_id', (int) $request->query('project_id'))
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->query('status'))
            )
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json($milestones);
    }

    

    public function show(Milestone $milestone): JsonResponse
    {
        $milestone->load(['application:id,name']);

        return response()->json($milestone);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'deadline' => ['required', 'date'],
            'status' => ['required', 'string', 'max:120'],
            'comments' => ['nullable', 'string'],
            'project_id' => ['required', 'integer', 'exists:application,id'],
        ]);

        $milestone = DB::transaction(function () use ($validated) {
            $application = Application::query()->findOrFail($validated['project_id']);

            return Milestone::query()->create([
                'name' => $validated['name'],
                'deadline' => $validated['deadline'],
                'status' => $validated['status'],
                'comments' => $validated['comments'] ?? null,
                'project_id' => $application->id,
            ]);
        });

        return response()->json($milestone->load('application:id,name'), 201);
    }

    public function update(Request $request, Milestone $milestone): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'deadline' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'max:120'],
            'comments' => ['nullable', 'string'],
            'project_id' => ['sometimes', 'integer', 'exists:application,id'],
        ]);

        $oldStatus = $milestone->status;

        $milestone = DB::transaction(function () use ($milestone, $validated) {
            $milestone->update($validated);

            return $milestone->fresh()->load('application:id,name');
        });

        if ($oldStatus !== $milestone->status) {
            event(new MilestoneStatusChanged(
                $milestone,
                $oldStatus,
                $milestone->status,
                $request->user()
            ));
        }

        return response()->json($milestone);
    }

    public function destroy(Milestone $milestone): JsonResponse
    {
        $milestone->delete();

        return response()->json([
            'message' => 'Míľnik bol úspešne odstránený.',
        ]);
    }
}
