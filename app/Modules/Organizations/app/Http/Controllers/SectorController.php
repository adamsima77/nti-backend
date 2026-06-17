<?php

namespace Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Content\Models\Language;
use Modules\Organizations\Models\Sector;

class SectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sectors = Sector::with(['sectorTranslations'])->orderByDesc('created_at')->get();
        return response()->json($sectors, Response::HTTP_OK);
    }

    public function getSectorByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $sectors = Sector::with([
            'sectorTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->get();

        return response()->json($sectors, Response::HTTP_OK);
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
