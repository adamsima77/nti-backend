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

    public function fetchMilestonesForStudent(Request $request){
        $this->authorize('fetchForStudent', Milestone::class);
        $user = $request->user();

        $calls = Call::whereHas('applications.team.members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            // Check pre stav výzvy: "V realizácii"
            ->whereHas('statusOfCall', function ($query) {
                $query->where('name', 'V realizácii')
                ->orWhere('name', 'Uzavreté');
            })
            // Check pre stav prihlášky: "Aktívny projekt"
            ->whereHas('applications', function ($query) use ($user) {
                $query->whereHas('status', function ($q) {
                    $q->where('name', 'Aktívny projekt')
                    ->orWhere('name', 'Ukončené');
                })
                    ->whereHas('team.members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with('milestones')
            ->get();
        return response()->json($calls, Response::HTTP_OK);
    }


    public function studentAnswer(Request $request, Milestone $milestone){
        $this->authorize('studentAnswer', $milestone);
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,txt', 'max:5120'],
        ]);

        $milestone = Milestone::findOrFail($milestone->id);
        $milestone->comments()->create([
            'comment_text' => $validated['comment'],
            'user_id'      => $request->user()->id,
            'parent_comment_id' => null
        ]);

        if (!empty($validated['files'])) {
            DB::transaction(function () use ($milestone,$validated, $request) {
            foreach ($validated['files'] as $file) {
                    try {

                        $path = $file->store('milestones/' . $milestone->id, 'private');
                        $originalName = $file->getClientOriginalName();


                        $doc = Document::create([
                            'owner_id' => $request->user()->id,
                            'security_classification_id' => 3,
                        ]);


                        $doc->versions()->create([
                            'file_name' => $originalName,
                            'file_path' => $path,
                        ]);

                        $milestone->documents()->attach($doc->id);

                    } catch (\Exception $e) {
                        \Log::error("Chyba pri nahrávaní súboru: " . $e->getMessage());
                        throw $e;
                    }

            }
                $milestone->update(['status' => 3]); //Poslane na hodnotenie
            });
        }

        return response()->json($milestone,Response::HTTP_OK);
    }
}
