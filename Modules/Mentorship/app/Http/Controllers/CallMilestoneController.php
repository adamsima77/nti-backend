<?php

namespace Modules\Mentorship\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mentorship\Models\CallMilestone;
use Modules\Mentorship\Models\MilestoneStatus;
use Modules\Mentorship\StateMachines\MilestoneStateMachine;
use Modules\Programs\Models\Call;

class CallMilestoneController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Call $call): JsonResponse
    {
        $this->authorize('viewAny', [CallMilestone::class, $call]);

        $milestones = CallMilestone::where('call_id', $call->id)
            ->with(['milestoneStatus', 'comments'])
            ->orderBy('due_date')
            ->get()
            ->map(fn ($m) => [
                'id'                    => $m->id,
                'name'                  => $m->name,
                'description'           => $m->description,
                'due_date'              => $m->due_date?->toDateString(),
                'status'                => $m->milestoneStatus?->name ?? MilestoneStateMachine::STATE_PLANNED,
                'available_transitions' => (new MilestoneStateMachine($m))->availableTransitions(),
                'comments'              => $m->comments->map(fn ($c) => [
                    'id'           => $c->id,
                    'text'         => $c->comment_text,
                    'author'       => trim("{$c->user?->name} {$c->user?->surname}"),
                    'created_at'   => $c->created_at?->toDateTimeString(),
                ]),
            ]);

        return response()->json(['milestones' => $milestones]);
    }

    public function store(Request $request, Call $call): JsonResponse
    {
        $this->authorize('create', [CallMilestone::class, $call]);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date'    => ['required', 'date'],
        ]);

        $defaultStatus = MilestoneStatus::where('name', 'Plánované')->firstOrFail();

        $milestone = CallMilestone::create([
            'call_id'             => $call->id,
            'name'                => $validated['name'],
            'description'         => $validated['description'] ?? null,
            'due_date'            => $validated['due_date'],
            'milestone_status_id' => $defaultStatus->id,
        ]);

        $milestone->load('milestoneStatus');

        return response()->json([
            'id'          => $milestone->id,
            'name'        => $milestone->name,
            'description' => $milestone->description,
            'due_date'    => $milestone->due_date?->toDateString(),
            'status'      => $milestone->milestoneStatus?->name,
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, Call $call, CallMilestone $milestone): JsonResponse
    {
        $this->authorize('update', $milestone);

        abort_if($milestone->call_id !== $call->id, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date'    => ['sometimes', 'date'],
            'status'      => ['sometimes', 'string', 'max:150'],
        ]);

        if (isset($validated['status'])) {
            $sm = new MilestoneStateMachine($milestone);
            try {
                $sm->transitionTo($validated['status']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            unset($validated['status']);
        }

        if (! empty($validated)) {
            $milestone->update($validated);
        }

        $milestone->load('milestoneStatus');

        return response()->json([
            'id'          => $milestone->id,
            'name'        => $milestone->name,
            'description' => $milestone->description,
            'due_date'    => $milestone->due_date?->toDateString(),
            'status'      => $milestone->milestoneStatus?->name,
            'available_transitions' => (new MilestoneStateMachine($milestone))->availableTransitions(),
        ]);
    }

    public function destroy(Request $request, Call $call, CallMilestone $milestone): JsonResponse
    {
        $this->authorize('delete', $milestone);

        abort_if($milestone->call_id !== $call->id, Response::HTTP_NOT_FOUND);

        $milestone->delete();

        return response()->json(['message' => 'Míľnik bol odstránený.']);
    }
}
