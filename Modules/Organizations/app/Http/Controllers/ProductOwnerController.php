<?php

namespace Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\MilestoneComment;
use Modules\Mentorship\Models\MilestoneStatus;
use Modules\Mentorship\StateMachines\MilestoneStateMachine;
use Modules\Programs\Models\Call;

class ProductOwnerController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────────────────

    private function poCall(Request $request): ?Call
    {
        return Call::where('po_user_id', $request->user()->id)
            ->with([
                'currentStatusHistory.status',
                'callType',
                'program.typeOfProgram',
                'organization:id,name',
                'applications.team.members',
                'applications.status',
                'callCriteria',
                'documents.versions',
            ])
            ->latest('id')
            ->first();
    }

    // ── Dashboard ──────────────────────────────────────────────────────────

    public function dashboard(Request $request): JsonResponse
    {
        $call = $this->poCall($request);

        if (! $call) {
            return response()->json([
                'call' => null,
                'stats' => [
                    'open_milestones'   => 0,
                    'done_milestones'   => 0,
                    'pending_approvals' => 0,
                ],
            ]);
        }

        $openStatusIds = MilestoneStatus::whereIn('name', ['Plánované', 'V riešení'])->pluck('id');
        $doneStatusIds = MilestoneStatus::whereIn('name', ['Dokončené', 'Schválené'])->pluck('id');
        $pendingStatusId = MilestoneStatus::where('name', 'Dokončené')->value('id');

        $openBacklog = Milestone::where('call_id', $call->id)
            ->whereIn('milestone_status_id', $openStatusIds)
            ->count();

        $doneBacklog = Milestone::where('call_id', $call->id)
            ->whereIn('milestone_status_id', $doneStatusIds)
            ->count();

        $pendingApprovals = Milestone::where('call_id', $call->id)
            ->where('milestone_status_id', $pendingStatusId)
            ->count();

        $selectedApp = $call->applications->first(fn ($a) => in_array($a->status?->name, [
            'Onboarding', 'Aktívny projekt', 'Ukončené', 'Schválené',
        ]));

        return response()->json([
            'team' => $selectedApp?->team ? [
                'name'    => $selectedApp->team->name,
                'status'  => $selectedApp->status?->name,
                'members' => $selectedApp->team->members->map(fn ($m) => trim("{$m->name} {$m->surname}")),
            ] : null,
            'call' => [
                'id'                   => $call->id,
                'name'                 => $call->name,
                'description'          => $call->description,
                'tech_spec'            => $call->tech_spec,
                'tech_tags'            => $call->tech_tags ?? [],
                'status'               => $call->currentStatusHistory?->status?->name,
                'application_start'    => $call->application_start?->toDateString(),
                'application_deadline' => $call->application_deadline?->toDateString(),
                'project_start'        => $call->project_start?->toDateString(),
                'project_end'          => $call->project_end?->toDateString(),
                'call_type'            => $call->callType?->name,
                'program'              => $call->program?->typeOfProgram?->name,
                'organization'         => $call->organization?->name,
                'budget'               => $call->budget,
                'max_teams'            => $call->max_teams,
                'budget_type'          => $call->budget_type,
                'requirements'         => $call->callCriteria?->pluck('name') ?? [],
                'documents'            => $call->documents?->map(fn ($d) => [
                    'id'   => $d->id,
                    'name' => $d->versions->last()?->file_name,
                ]) ?? [],
            ],
            'stats' => [
                'open_milestones'   => $openBacklog,
                'done_milestones'   => $doneBacklog,
                'pending_approvals' => $pendingApprovals,
                'documents_count'   => $call->documents?->count() ?? 0,
            ],
        ]);
    }

    // ── Update call (PO only) ──────────────────────────────────────────────

    public function updateCall(Request $request, Call $call): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $validated = $request->validate([
            'name'                 => ['sometimes', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'budget'               => ['nullable', 'numeric'],
            'budget_type'          => ['nullable', 'string'],
            'tech_spec'            => ['nullable', 'string'],
            'tech_tags'            => ['nullable', 'array'],
            'max_teams'            => ['nullable', 'integer'],
            'application_start'    => ['sometimes', 'nullable', 'date'],
            'application_deadline' => ['sometimes', 'nullable', 'date'],
            'project_start'        => ['sometimes', 'nullable', 'date'],
            'project_end'          => ['sometimes', 'nullable', 'date'],
            'document_ids'         => ['nullable', 'array'],
            'document_ids.*'       => ['integer', 'exists:document,id'],
        ]);

        $call->update(collect($validated)->except('document_ids')->toArray());

        if (array_key_exists('document_ids', $validated)) {
            $call->documents()->sync($validated['document_ids'] ?? []);
        }

        return response()->json(['message' => 'Zadanie bolo aktualizované.', 'data' => $call->fresh()]);
    }

    // ── Milestones ────

    public function milestoneApprovals(Request $request, Call $call): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $doneStatusId = MilestoneStatus::where('name', MilestoneStateMachine::STATE_DONE)->value('id');

        $milestones = Milestone::where('call_id', $call->id)
            ->where('milestone_status_id', $doneStatusId)
            ->with('milestoneStatus')
            ->orderBy('deadline')
            ->get()
            ->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->name,
                'due_date' => $m->deadline?->toDateString(),
                'status'   => $m->milestoneStatus?->name,
            ]);

        return response()->json(['milestones' => $milestones]);
    }

    public function approveMilestone(Request $request, Call $call, Milestone $milestone): JsonResponse
    {
        $this->authorizePoCall($request, $call);
        abort_if($milestone->call_id !== $call->id, 403);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            (new MilestoneStateMachine($milestone))->transitionTo(MilestoneStateMachine::STATE_APPROVED);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! empty($validated['comment'])) {
            MilestoneComment::create([
                'milestone_id' => $milestone->id,
                'user_id'      => $request->user()->id,
                'comment_text' => $validated['comment'],
            ]);
        }

        return response()->json(['message' => 'Míľnik bol schválený.', 'status' => MilestoneStateMachine::STATE_APPROVED]);
    }

    public function rejectMilestone(Request $request, Call $call, Milestone $milestone): JsonResponse
    {
        $this->authorizePoCall($request, $call);
        abort_if($milestone->call_id !== $call->id, 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            (new MilestoneStateMachine($milestone))->transitionTo(MilestoneStateMachine::STATE_REJECTED);
            (new MilestoneStateMachine($milestone))->transitionTo(MilestoneStateMachine::STATE_IN_PROGRESS);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! empty($validated['reason'])) {
            MilestoneComment::create([
                'milestone_id' => $milestone->id,
                'user_id'      => $request->user()->id,
                'comment_text' => $validated['reason'],
            ]);
        }

        return response()->json(['message' => 'Míľnik bol zamietnutý.', 'status' => MilestoneStateMachine::STATE_IN_PROGRESS]);
    }

    // ── Auth helper ────────────────────────────────────────────────────────

    private function authorizePoCall(Request $request, Call $call): void
    {
        abort_if(
            (int) $call->po_user_id !== $request->user()->id,
            Response::HTTP_FORBIDDEN,
            'Nie ste product owner tohto zadania.'
        );
    }
}
