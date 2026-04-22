<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Programs\Http\Resources\CallResource;
use Modules\Programs\Models\Call;

class CallController extends Controller
{
    public function index()
    {
        $calls = Call::query()
            ->with([
                'program:id,name',
                'organization:id,name',
            ])
            ->whereHas('status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->latest('id')
            ->get();

        return CallResource::collection($calls);
    }

    public function show(int $id)
    {
        $call = Call::query()
            ->with([
                'program:id,name',
                'organization:id,name',
                'status:id,name',
                'callCriteria:id,name',
            ])
            ->whereHas('status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->findOrFail($id);

        return new CallResource($call);
    }
}
