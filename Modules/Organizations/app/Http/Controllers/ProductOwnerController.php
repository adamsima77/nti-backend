<?php

namespace Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\DocumentVersion;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\CallMilestone;
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
            ])
            ->latest('id')
            ->first();
    }

    private function assignedApplication(Call $call): ?Application
    {
        return $call->applications
            ->first(fn ($app) => in_array($app->status?->name, [
                'Onboarding', 'Aktívny projekt', 'Ukončené',
            ]));
    }

    // ── Dashboard ──────────────────────────────────────────────────────────

    public function dashboard(Request $request): JsonResponse
    {
        $call = $this->poCall($request);

        if (! $call) {
            return response()->json([
                'call' => null,
                'assigned_application' => null,
                'stats' => [
                    'total_applications' => 0,
                    'open_milestones'    => 0,
                    'done_milestones'    => 0,
                    'pending_approvals'  => 0,
                ],
            ]);
        }

        $assignedApp = $this->assignedApplication($call);

        $openBacklog = CallMilestone::where('call_id', $call->id)
            ->where('status', 'open')
            ->count();

        $doneBacklog = CallMilestone::where('call_id', $call->id)
            ->where('status', 'done')
            ->count();

        $pendingApprovals = $assignedApp
            ? Milestone::where('project_id', $assignedApp->id)
                ->where('status', 'Dokončené')
                ->count()
            : 0;

        return response()->json([
            'call' => [
                'id'                   => $call->id,
                'name'                 => $call->name,
                'description'          => $call->description,
                'status'               => $call->currentStatusHistory?->status?->name,
                'application_deadline' => $call->application_deadline?->toDateString(),
                'project_start'        => $call->project_start?->toDateString(),
                'project_end'          => $call->project_end?->toDateString(),
                'call_type'            => $call->callType?->name,
                'program'              => $call->program?->typeOfProgram?->name,
                'organization'         => $call->organization?->name,
            ],
            'assigned_application' => $assignedApp ? [
                'id'     => $assignedApp->id,
                'status' => $assignedApp->status?->name,
                'team'   => $assignedApp->team ? [
                    'id'      => $assignedApp->team->id,
                    'name'    => $assignedApp->team->name,
                    'members' => $assignedApp->team->members->map(fn ($m) => [
                        'id'      => $m->id,
                        'name'    => trim("{$m->name} {$m->surname}"),
                    ]),
                ] : null,
            ] : null,
            'stats' => [
                'total_applications' => $call->applications->count(),
                'open_milestones'    => $openBacklog,
                'done_milestones'    => $doneBacklog,
                'pending_approvals'  => $pendingApprovals,
            ],
        ]);
    }

    // ── Backlog ───────────────────────

    public function backlog(Request $request, Call $call): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $items = CallMilestone::where('call_id', $call->id)
            ->orderBy('due_date')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'name'        => $m->name,
                'description' => $m->description,
                'due_date'    => $m->due_date?->toDateString(),
                'status'      => $m->status ?? 'open',
            ]);

        return response()->json(['backlog' => $items]);
    }

    public function storeBacklogItem(Request $request, Call $call): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date'    => ['required', 'date'],
        ]);

        $item = CallMilestone::create([
            'call_id'     => $call->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'due_date'    => $validated['due_date'],
        ]);

        return response()->json([
            'id'          => $item->id,
            'name'        => $item->name,
            'description' => $item->description,
            'due_date'    => $item->due_date?->toDateString(),
            'status'      => $item->status ?? 'open',
        ], 201);
    }

    public function updateBacklogItem(Request $request, Call $call, CallMilestone $milestone): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        abort_if($milestone->call_id !== $call->id, 404);

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date'    => ['sometimes', 'date'],
        ]);

        $milestone->update($validated);

        return response()->json([
            'id'          => $milestone->id,
            'name'        => $milestone->name,
            'description' => $milestone->description,
            'due_date'    => $milestone->due_date?->toDateString(),
            'status'      => $milestone->status ?? 'open',
        ]);
    }

    public function deleteBacklogItem(Request $request, Call $call, CallMilestone $milestone): JsonResponse
    {
        $this->authorizePoCall($request, $call);
        abort_if($milestone->call_id !== $call->id, 404);

        $milestone->delete();

        return response()->json(['message' => 'Položka bola odstránená.']);
    }

    // ── Milestones ────

    public function milestoneApprovals(Request $request, Call $call): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $assignedApp = $this->assignedApplication($call->load([
            'applications.status',
            'applications.team',
        ]));

        if (! $assignedApp) {
            return response()->json(['milestones' => []]);
        }

        $milestones = Milestone::where('project_id', $assignedApp->id)
            ->orderBy('deadline')
            ->get()
            ->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->name,
                'deadline' => $m->deadline?->toDateString(),
                'status'   => $m->status,
                'comments' => $m->comments,
            ]);

        return response()->json([
            'application_id' => $assignedApp->id,
            'team_name'      => $assignedApp->team?->name,
            'milestones'     => $milestones,
        ]);
    }

    public function approveMilestone(Request $request, Call $call, Milestone $milestone): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $assignedApp = $this->assignedApplication($call->load(['applications.status']));
        abort_if(! $assignedApp || $milestone->project_id !== $assignedApp->id, 403);

        $milestone->update(['status' => 'Schválené']);

        return response()->json(['message' => 'Míľnik bol schválený.', 'status' => 'Schválené']);
    }

    // ── Documents — PO výstupy a prezentácie ──────────────────────────────
    // Samostatná tabuľka po_document (call_id + document_id).
    // document_has_call = tech. spec. org_admina — nedotýkame sa.
    // document_has_application = súbory študentov — nedotýkame sa.

    public function documents(Request $request, Call $call): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $docs = $call->poDocuments()->with('versions')->get()->map(function ($doc) {
            $latest = $doc->versions->sortByDesc('id')->first();
            return [
                'id'          => $doc->id,
                'name'        => $latest?->file_name ?? "Dokument #{$doc->id}",
                'uploaded_at' => $latest?->created_at,
            ];
        });

        return response()->json(['documents' => $docs]);
    }

    public function uploadDocument(Request $request, Call $call): JsonResponse
    {
        $this->authorizePoCall($request, $call);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store("calls/{$call->id}/po-documents", 'public');

        $document = Document::create([
            'security_classification_id' => 1,
            'owner_id'                   => $request->user()->id,
        ]);

        DocumentVersion::create([
            'document_id' => $document->id,
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
        ]);

        $call->poDocuments()->attach($document->id);

        return response()->json([
            'id'          => $document->id,
            'name'        => $file->getClientOriginalName(),
            'uploaded_at' => now(),
        ], 201);
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
