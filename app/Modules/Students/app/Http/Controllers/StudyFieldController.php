<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Content\Models\Language;
use Modules\Students\Models\StudyField;

class StudyFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fields = StudyField::with(['studyFieldTranslations'])->orderByDesc('created_at')->get();
        return response()->json($fields, Response::HTTP_OK);
    }

    public function fetchByLangPublic(string $lang){
        $lang = Language::where('name', $lang)->value('id');
        if(!$lang){
            return response()->json(['message' => 'Language not found !'], Response::HTTP_NOT_FOUND);
        }

        $studyFields = StudyField::with([
            'studyFieldTranslations' => function ($q) use ($lang) {
                $q->where('language_id', $lang);
            }
        ])->get();

        return response()->json($studyFields, Response::HTTP_OK);
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
