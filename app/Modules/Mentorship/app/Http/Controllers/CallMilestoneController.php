<?php

namespace Modules\Mentorship\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\MilestoneStatus;
use Modules\Mentorship\StateMachines\MilestoneStateMachine;
use Modules\Programs\Models\Call;

class CallMilestoneController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Call $call): JsonResponse
    {
        $this->authorize('viewAny', [Milestone::class, $call]);

        $milestones = Milestone::where('call_id', $call->id)
            ->with(['milestoneStatus', 'comments'])
            ->orderBy('deadline')
            ->get()
            ->map(fn ($m) => [
                'id'                    => $m->id,
                'name'                  => $m->name,
                'description'           => $m->description,
                'due_date'              => $m->deadline?->toDateString(),
                'status'                => $m->milestoneStatus?->name ?? MilestoneStateMachine::STATE_PLANNED,
                'available_transitions' => (new MilestoneStateMachine($m))->availableTransitions(),
                'comments'              => $m->getRelation('comments')->map(fn ($c) => [
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
        $this->authorize('create', [Milestone::class, $call]);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date'    => ['required', 'date'],
        ]);

        $defaultStatus = MilestoneStatus::where('name', 'Plánované')->firstOrFail();

        $milestone = Milestone::create([
            'call_id'             => $call->id,
            'name'                => $validated['name'],
            'description'         => $validated['description'] ?? null,
            'deadline'            => $validated['due_date'],
            'milestone_status_id' => $defaultStatus->id,
        ]);

        $milestone->load('milestoneStatus');

        return response()->json([
            'id'          => $milestone->id,
            'name'        => $milestone->name,
            'description' => $milestone->description,
            'due_date'    => $milestone->deadline?->toDateString(),
            'status'      => $milestone->milestoneStatus?->name,
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, Call $call, Milestone $milestone): JsonResponse
    {
        $this->authorize('update', $milestone);

        abort_if($milestone->call_id !== $call->id, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'due_date'    => ['sometimes', 'required', 'date'],
            'status'      => ['sometimes', 'required', 'string', 'max:150'],
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

        if (isset($validated['due_date'])) {
            $validated['deadline'] = $validated['due_date'];
            unset($validated['due_date']);
        }

        if (! empty($validated)) {
            $milestone->update($validated);
        }

        $milestone->load('milestoneStatus');

        return response()->json([
            'id'          => $milestone->id,
            'name'        => $milestone->name,
            'description' => $milestone->description,
            'due_date'    => $milestone->deadline?->toDateString(),
            'status'      => $milestone->milestoneStatus?->name,
            'available_transitions' => (new MilestoneStateMachine($milestone))->availableTransitions(),
        ]);
    }

    public function destroy(Request $request, Call $call, Milestone $milestone): JsonResponse
    {
        $this->authorize('delete', $milestone);

        abort_if($milestone->call_id !== $call->id, Response::HTTP_NOT_FOUND);

        $milestone->delete();

        return response()->json(['message' => 'Míľnik bol odstránený.']);
    }
}
