<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Pdf\PdfService;
use Modules\Applications\Http\Resources\ApplicationResource;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\Applications;
use Modules\Applications\Models\ApplicationStatusHistory;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Applications\Models\TypeOfApplication;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Modules\Teams\Models\Team;

class ApplicationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Applications::class);

        $applications = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'team:id,name',
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
                'team:id,name',
                'documents:id',
                'documents.versions',
                'statusHistory.status:id,name',
                'milestones',
            ])
            ->findOrFail($id);

        $this->authorize('view', $application);

        return new ApplicationResource($application);
    }

    public function downloadPdf(Request $request, int $id, PdfService $pdfService)
    {
        $application = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'documents:id',
                'statusHistory.status:id,name',
            ])
            ->findOrFail($id);

        $this->authorize('view', $application);

        return $pdfService->download(
            'applications::pdf.application-details',
            ['application' => $application],
            'application-' . $application->id . '.pdf'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => ['required', 'integer', 'exists:call,id'],
            'team_id' => ['required', 'integer', 'exists:team,id'],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document,id'],
            'form_data' => ['nullable', 'array'],
            'form_data.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $application = DB::transaction(function () use ($validated, $request) {
            $call = Call::query()
                ->with('callCriteria')
                ->whereKey($validated['call_id'])
                ->whereHas('currentStatusHistory.status', function ($query) {
                    $query->where('name', 'Publikované');
                })
                ->first();

            if ($call === null) {
                abort(422, 'Vybrana vyzva nie je publikovana.');
            }

            $team = Team::query()
                ->withCount('members')
                ->findOrFail((int) $validated['team_id']);

            if ($team->members_count < 3) {
                throw ValidationException::withMessages([
                    'team_id' => ['Tím musí mať aspoň 3 členov, aby bolo možné podať prihlášku.'],
                ]);
            }

            if (! $team->members()->where('user_id', $request->user()->id)->exists()) {
                throw ValidationException::withMessages([
                    'team_id' => ['Do prihlášky môžete použiť iba tím, ktorého ste členom.'],
                ]);
            }

            $formDataInput = $validated['form_data'] ?? [];
            $storedFormData = [];

            foreach ($call->callCriteria as $criterion) {
                $key = 'criterion_'.$criterion->id;
                $value = isset($formDataInput[$key]) ? trim((string) $formDataInput[$key]) : '';

                if ($value === '') {
                    throw ValidationException::withMessages([
                        'form_data.'.$key => ['Vyplňte pole pre toto kritérium.'],
                    ]);
                }

                $storedFormData[$key] = $value;
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
                'form_data' => $storedFormData === [] ? null : $storedFormData,
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
                'team:id,name',
                'documents:id',
                'documents.versions',
            ]);
        });

        return (new ApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(Request $request, int $id): ApplicationResource
    {
        $application = Application::findOrFail($id);

        $this->authorize('update', $application);

        $validated = $request->validate([
            'status_id' => ['nullable', 'integer', 'exists:status_of_application,id', 'required_without:status_name'],
            'status_name' => ['nullable', 'string', 'max:120', 'required_without:status_id'],
            'note' => ['nullable', 'string'],
        ]);

        $application = DB::transaction(function () use ($validated, $application) {

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

}
