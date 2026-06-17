<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Students\Models\AcademicFlag;
use Illuminate\Http\Response;
class AcademicFlagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $flags = AcademicFlag::orderByDesc('created_at')->get();
        return response()->json($flags, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', AcademicFlag::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:academic_flags,name'],
        ]);

        $record = AcademicFlag::create($validated);

        return response()->json($record, Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show(AcademicFlag $record)
    {
        $this->authorize('view', $record);

        return response()->json($record, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AcademicFlag $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:academic_flags,name,' . $record->id],
        ]);

        $record->update($validated);

        return response()->json($record, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicFlag $record)
    {
        $this->authorize('delete', $record);

        $record->delete();

        return response()->json([
            'message' => 'Záznam bol úspešne odstránený.',
        ], Response::HTTP_OK);
    }
}
