<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Programs\Http\Resources\CallResource;
use Modules\Programs\Models\Call;

class CallController extends Controller
{
    public function index(Request $request)
    {
        $calls = Call::query()
            ->with([
                'program:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) use ($request) {
                if ($request->filled('status')) {
                    $query->where('name', $request->query('status'));
                    return;
                }

                $query->where('name', 'Publikované');
            })
            ->when(
                $request->filled('deadline_from'),
                fn (Builder $query) => $query->whereDate('application_deadline', '>=', $request->query('deadline_from'))
            )
            ->when(
                $request->filled('deadline_to'),
                fn (Builder $query) => $query->whereDate('application_deadline', '<=', $request->query('deadline_to'))
            )
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return CallResource::collection($calls);
    }

    public function show(int $id)
    {
        $call = Call::query()
            ->with([
                'program:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria:id,name',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->findOrFail($id);

        return new CallResource($call);
    }
}
