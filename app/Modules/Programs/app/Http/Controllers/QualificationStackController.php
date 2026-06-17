<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Modules\Content\Models\Language;
use Modules\Programs\Models\QualificationStack;

class QualificationStackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    public function fetchStacksByLang(Request $request, string $language)
    {

        $validator = Validator::make(['language' => $language], [
            'language' => ['required', 'string', 'exists:languages,name']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        $language_id = Language::where('name', $language)->value('id');

        $stacks = QualificationStack::query()
            ->whereHas('translations', function ($query) use ($language_id) {
                $query->where('language_id', $language_id);
            })
            ->with(['translations' => function ($query) use ($language_id) {
                $query->where('language_id', $language_id);
            }])
            ->get();

        return response()->json($stacks, Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {

    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
