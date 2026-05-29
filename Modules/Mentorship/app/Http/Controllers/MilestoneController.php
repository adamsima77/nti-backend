<?php

namespace Modules\Mentorship\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Mentorship\Models\Milestone;

class MilestoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Milestone::class);

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
        $this->authorize('view', $milestone);

        $milestone->load(['application:id,name']);

        return response()->json($milestone);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Milestone::class);

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
        $this->authorize('update', $milestone);

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
            $milestone->load(['application.team.members', 'application.creator', 'application.call']);

            event(new MilestoneStatusChanged(
                $milestone,
                $oldStatus,
                $milestone->status,
                $request->user(),
                LanguageType::SLOVAK->value,
            ));
        }

        return response()->json($milestone);
    }

    public function destroy(Milestone $milestone): JsonResponse
    {
        $this->authorize('delete', $milestone);

        $milestone->delete();

        return response()->json([
            'message' => 'Míľnik bol úspešne odstránený.',
        ]);
    }
}
