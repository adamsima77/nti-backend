<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Students\Models\University;
use Illuminate\Http\Response;
class UniversityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $university = University::orderByDesc('created_at')->get();
        return response()->json($university, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', University::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:university,name'],
        ]);

        $record = University::create($validated);

        return response()->json($record, Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show(University $record)
    {
        $this->authorize('view', $record);

        return response()->json($record, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, University $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:university,name,' . $record->id],
        ]);

        $record->update($validated);

        return response()->json($record, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(University $record)
    {
        $this->authorize('delete', $record);

        $record->delete();

        return response()->json([
            'message' => 'Záznam bol úspešne odstránený.',
        ], Response::HTTP_OK);
    }
}
