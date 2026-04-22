<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Http\Resources\ApplicationResource;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\ApplicationStatusHistory;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Applications\Models\TypeOfApplication;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'documents:id',
            ])
            ->where('created_by', $request->user()->id)
            ->when(
                $request->filled('call_id'),
                fn (Builder $query) => $query->where('call_id', (int) $request->query('call_id'))
            )
            ->when(
                $request->filled('status_id'),
                fn (Builder $query) => $query->where('active_status', (int) $request->query('status_id'))
            )
            ->latest('id')
            ->paginate(15);

        return ApplicationResource::collection($applications);
    }

    public function show(Request $request, int $id): ApplicationResource
    {
        $application = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'documents:id',
                'statusHistory.status:id,name',
            ])
            ->where('created_by', $request->user()->id)
            ->findOrFail($id);

        return new ApplicationResource($application);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => ['required', 'integer', 'exists:call,id'],
            'team_id' => ['required', 'integer'],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document,id'],
        ]);

        $application = DB::transaction(function () use ($validated, $request) {
            $call = Call::query()
                ->whereKey($validated['call_id'])
                ->whereHas('status', function ($query) {
                    $query->where('name', 'Publikované');
                })
                ->first();

            if ($call === null) {
                abort(422, 'Vybrana vyzva nie je publikovana.');
            }

            $status = StatusOfApplication::query()->firstOrCreate([
                'name' => 'Podané',
            ]);

            $defaultType = TypeOfApplication::query()->firstOrCreate([
                'name' => 'Príloha prihlášky',
            ]);

            $application = Application::query()->create([
                'submitted_at' => now(),
                'last_update' => now(),
                'call_id' => $validated['call_id'],
                'team_id' => $validated['team_id'],
                'created_by' => $request->user()->id,
                'active_status' => $status->id,
            ]);

            $application->documents()->syncWithPivotValues(
                $validated['document_ids'],
                ['type_of_application_id' => $defaultType->id]
            );

            ApplicationStatusHistory::query()->create([
                'status_of_application_id' => $status->id,
                'application_id' => $application->id,
                'note' => 'Automaticky nastavene pri odoslani prihlasky.',
            ]);

            return $application->load([
                'call:id,name',
                'status:id,name',
                'documents:id',
            ]);
        });

        return (new ApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(Request $request, int $id): ApplicationResource
    {
        $user = $request->user();

        if (! $this->canManageStatuses($user)) {
            abort(403, 'Nemate opravnenie menit stav prihlasky.');
        }

        $validated = $request->validate([
            'status_id' => ['nullable', 'integer', 'exists:status_of_application,id', 'required_without:status_name'],
            'status_name' => ['nullable', 'string', 'max:120', 'required_without:status_id'],
            'note' => ['nullable', 'string'],
        ]);

        $application = DB::transaction(function () use ($validated, $id) {
            $application = Application::query()->findOrFail($id);

            $status = null;
            if (! empty($validated['status_id'])) {
                $status = StatusOfApplication::query()->findOrFail((int) $validated['status_id']);
            }

            if ($status === null && ! empty($validated['status_name'])) {
                $status = StatusOfApplication::query()->firstOrCreate([
                    'name' => $validated['status_name'],
                ]);
            }

            $application->update([
                'active_status' => $status->id,
                'last_update' => now(),
            ]);

            ApplicationStatusHistory::query()->create([
                'status_of_application_id' => $status->id,
                'application_id' => $application->id,
                'note' => $validated['note'] ?? null,
            ]);

            return $application->load([
                'call:id,name',
                'status:id,name',
                'documents:id',
                'statusHistory.status:id,name',
            ]);
        });

        return new ApplicationResource($application);
    }

    private function canManageStatuses(User $user): bool
    {
        return $user->isEvaluator() || $user->isAdmin() || $user->isSuperAdmin();
    }
}
