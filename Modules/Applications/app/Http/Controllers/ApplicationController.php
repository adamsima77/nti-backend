<?php

namespace Modules\Applications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Applications\Models\TypeOfApplication;

class ApplicationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => ['required', 'integer', 'exists:call,id'],
            'team_id' => ['required', 'integer'],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document,id'],
        ]);

        $application = DB::transaction(function () use ($validated, $request) {
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

            return $application;
        });

        return response()->json([
            'id' => $application->id,
            'status' => 'Podané',
        ], 201);
    }
}
