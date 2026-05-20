<?php

namespace Modules\Students\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Content\Models\Language;
use Modules\Students\Models\StudyYear;
use Illuminate\Http\Response;
class StudyYearController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $studyYears = StudyYear::with(['studyYearTranslations'])->orderByDesc('created_at')->get();
        return response()->json($studyYears, Response::HTTP_OK);
    }

    public function fetchByLangPublic(string $lang){
        $lang = Language::where('name', $lang)->value('id');

        if(!$lang){
            return response()->json(['message' => 'Language not found'], Response::HTTP_NOT_FOUND);
        }

        $studyYears = StudyYear::with(['studyYearTranslations' => function($q) use ($lang){
            $q->where('language_id', $lang);
        }])->orderByDesc('created_at')->get();

        return response()->json($studyYears, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

        return response()->json([]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        //

        return response()->json([]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //

        return response()->json([]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //

        return response()->json([]);
    }
}
