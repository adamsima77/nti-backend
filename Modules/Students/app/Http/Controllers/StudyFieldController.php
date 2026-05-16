<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Students\Models\StudyField;

class StudyFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fields = StudyField::orderByDesc('created_at')->get();
        return response()->json($fields, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', StudyField::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:study_field,name'],
        ]);

        $record = StudyField::create($validated);

        return response()->json($record, Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show(StudyField $record)
    {
        $this->authorize('view', $record);

        return response()->json($record, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StudyField $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:study_field,name,' . $record->id],
        ]);

        $record->update($validated);

        return response()->json($record, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudyField $record)
    {
        $this->authorize('delete', $record);

        $record->delete();

        return response()->json([
            'message' => 'Záznam bol úspešne odstránený.',
        ], Response::HTTP_OK);
    }
}
