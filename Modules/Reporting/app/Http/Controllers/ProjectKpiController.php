<?php

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Modules\Reporting\Models\ProjectKpi;
use Modules\Reporting\Http\Requests\StoreProjectKpiRequest;
use Modules\Reporting\Http\Requests\UpdateProjectKpiRequest;
use Modules\Reporting\Http\Resources\ProjectKpiResource;
use Modules\Applications\Models\Application;

class ProjectKpiController extends Controller
{
    /**
     * Display a listing of KPIs for an application
     */
    public function index(Request $request, int $applicationId): JsonResponse
    {
        $this->authorize('view', Application::findOrFail($applicationId));

        $kpis = ProjectKpi::query()
            ->where('application_id', $applicationId)
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->where('metric_name', 'like', '%' . $request->query('search') . '%')
            )
            ->orderBy('id')
            ->paginate(15);

        return ProjectKpiResource::collection($kpis)->response();
    }

    /**
     * Display a specific KPI
     */
    public function show(int $id): JsonResponse
    {
        $kpi = ProjectKpi::findOrFail($id);

        $this->authorize('view', $kpi);

        return (new ProjectKpiResource($kpi))->response();
    }

    /**
     * Store a newly created KPI
     */
    public function store(StoreProjectKpiRequest $request): JsonResponse
    {
        $this->authorize('createForApplication', [
            Application::class,
            Application::findOrFail($request->application_id),
        ]);

        $kpi = ProjectKpi::create($request->validated());

        return (new ProjectKpiResource($kpi))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified KPI
     */
    public function update(UpdateProjectKpiRequest $request, int $id): JsonResponse
    {
        $kpi = ProjectKpi::findOrFail($id);

        $this->authorize('update', $kpi);

        $kpi->update($request->validated());

        return (new ProjectKpiResource($kpi))->response();
    }

    /**
     * Delete the specified KPI
     */
    public function destroy(int $id): JsonResponse
    {
        $kpi = ProjectKpi::findOrFail($id);

        $this->authorize('delete', $kpi);

        $kpi->delete();

        return response()->json(['message' => 'KPI bolo úspešne odstránené']);
    }

    /**
     * Get KPI statistics for an application
     */
    public function statistics(int $applicationId): JsonResponse
    {
        $this->authorize('view', Application::findOrFail($applicationId));

        $kpis = ProjectKpi::where('application_id', $applicationId)->get();

        $stats = [
            'total_kpis' => $kpis->count(),
            'kpis_with_targets' => $kpis->whereNotNull('target_value')->count(),
            'kpis_with_actuals' => $kpis->whereNotNull('actual_value')->count(),
            'targets_met' => $kpis->filter(fn ($kpi) => $kpi->isTargetMet())->count(),
            'average_achievement' => $kpis
                ->filter(fn ($kpi) => $kpi->achievement_percentage !== null)
                ->average(fn ($kpi) => $kpi->achievement_percentage),
        ];

        return response()->json($stats);
    }
}
