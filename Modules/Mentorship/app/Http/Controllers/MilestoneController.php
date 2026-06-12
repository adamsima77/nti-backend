<?php

namespace Modules\Mentorship\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\DocumentVersion;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Mentorship\Models\Milestone;
use Modules\Programs\Models\Call;

class MilestoneController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Milestone::class);

        $milestones = Milestone::query()
            ->with(['application:id,name'])
            ->when(
                $request->filled('project_id'),
                fn($query) => $query->where('project_id', (int)$request->query('project_id'))
            )
            ->when(
                $request->filled('status'),
                fn($query) => $query->where('status', $request->query('status'))
            )
            ->latest('id')
            ->paginate((int)$request->query('per_page', 15));

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

    public function fetchMilestonesForStudent(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->isStudent()) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $activeStatuses = ['Aktívny projekt', 'Onboarding', 'Ukončené'];

        $calls = Call::query()
            ->whereHas('applications.team.members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('applications', function ($query) use ($user, $activeStatuses) {
                $query->whereHas('status', function ($q) use ($activeStatuses) {
                    $q->whereIn('name', $activeStatuses);
                })
                    ->whereHas('team.members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with([
                'currentStatusHistory.status',

                // Load milestones with their documents + each document's latest version
                // so the frontend can display file names and trigger downloads.
                'milestones' => function ($query) {
                    $query->with([
                        'documents' => function ($q) {
                            // latestVersion is a hasOne relationship on Document that
                            // resolves to the versions() hasMany ordered by id desc.
                            $q->with('latestVersion');


                        },
                        'comments' => function ($q) {
                            $q->with('user:id,name,surname');
                        },
                    ])->orderBy('id', 'asc');
                },

                // Load the student's application(s) for this call, with mentorships
                'applications' => function ($query) use ($user, $activeStatuses) {
                    $query
                        ->whereHas('status', fn($q) => $q->whereIn('name', $activeStatuses))
                        ->whereHas('team.members', fn($q) => $q->where('user_id', $user->id))
                        ->select(['id', 'call_id', 'team_id', 'active_status']) // ← removed academic_flag
                        ->with([
                            'mentorships' => function ($q) {
                                $q->select(['id', 'application_id', 'mentor_user_id', 'created_at'])
                                    ->with([
                                        'mentor' => function ($q) {
                                            $q->select(['id', 'name', 'surname', 'email']);
                                        },
                                    ]);
                            },
                        ]);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedCalls = $calls->map(function (Call $call) {
            $call->status_of_call = $call->currentStatusHistory?->status;
            return $call;
        });

        return response()->json($formattedCalls, Response::HTTP_OK);
    }

    public function studentAnswer(Request $request, Milestone $milestone)
    {
        $user = $request->user();

        // 1. Role check
        if (!$user || !$user->isStudent()) {
            abort(403, 'Only students can answer milestones.');
        }

        // 2. Status check — only "V riešení" (2) and "Vrátené na doplnenie" (6)
        if (!in_array((int) $milestone->getAttribute('milestone_status_id'), [2, 6])) {
            abort(403, 'This milestone is not open for answers at this time.');
        }

        // 3. Team membership check
        $hasAccess = Application::where('call_id', $milestone->call_id)
            ->whereHas('team.members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You are not a member of the team assigned to this milestone.');
        }

        // 4. Date-range guards
        if ($milestone->start_date !== null) {
            $startDate = $milestone->start_date instanceof \Carbon\Carbon
                ? $milestone->start_date
                : \Carbon\Carbon::parse($milestone->start_date);

            if ($startDate->isFuture()) {
                abort(403, 'This milestone cannot be answered before the start date.');
            }
        }

        if ($milestone->deadline !== null) {
            $deadline = $milestone->deadline instanceof \Carbon\Carbon
                ? $milestone->deadline
                : \Carbon\Carbon::parse($milestone->deadline);

            if ($deadline->endOfDay()->isPast()) {
                abort(403, 'This milestone cannot be answered after the deadline.');
            }
        }

        // 5. Validation
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,txt', 'max:5120'],
        ]);

        // 6. Persist comment, files and status transition atomically
        DB::transaction(function () use ($milestone, $validated, $user) {

            // Create the student comment
            $milestone->comments()->create([
                'comment_text' => $validated['comment'],
                'user_id' => $user->id,
                'parent_comment_id' => null,
            ]);

            // Process uploaded files with Smart Versioning
            if (!empty($validated['files'])) {

                // Načítame existujúce dokumenty tohto míľnika aj s ich najnovšou verziou (kvôli overeniu názvu)
                $existingDocuments = $milestone->documents()->with('latestVersion')->get();

                foreach ($validated['files'] as $file) {
                    try {
                        $originalName = $file->getClientOriginalName();
                        $path = $file->store('milestones/' . $milestone->id, 'local');

                        // Skontrolujeme, či už existuje dokument s rovnakým názvom súboru v najnovšej verzii
                        $existingDoc = $existingDocuments->first(function ($doc) use ($originalName) {
                            return $doc->latestVersion && $doc->latestVersion->file_name === $originalName;
                        });

                        if ($existingDoc) {
                            // PREPOJENIE NA EXISTUJÚCI: Vytvoríme novú verziu pod starý Document ID
                            $existingDoc->versions()->create([
                                'file_name' => $originalName,
                                'file_path' => $path,
                            ]);

                            // Netreba robiť attach(), väzba v pivot tabuľke medzi míľnikom a document_id už existuje
                        } else {
                            // NOVÝ DOKUMENT: Ak súbor s takým názvom neexistuje, vytvoríme nový záznam
                            $doc = Document::create([
                                'owner_id' => $user->id,
                                'security_classification_id' => 3,
                            ]);

                            $doc->versions()->create([
                                'file_name' => $originalName,
                                'file_path' => $path,
                            ]);

                            // Keďže je to nový dokument, musíme ho naviazať na míľnik
                            $milestone->documents()->attach($doc->id);
                        }

                    } catch (\Exception $e) {
                        Log::error('Chyba pri nahrávaní súboru míľnika: ' . $e->getMessage());
                        throw $e;
                    }
                }
            }

            // Transition status to "Dokončené" (3)
            $milestone->update(['milestone_status_id' => 3]);
        });

        // 7. Return the refreshed milestone with corrected relationships
        $milestone->refresh()->load([
            'documents' => fn($q) => $q->with('latestVersion'),
            'milestoneStatus',
            'comments'
        ]);

        return response()->json($milestone, Response::HTTP_OK);
    }
}

