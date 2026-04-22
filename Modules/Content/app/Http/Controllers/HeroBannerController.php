<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\HeroBanner;
use Illuminate\Http\Response;
use Modules\Content\Models\Language;

class HeroBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = HeroBanner::with(['page', 'heroBannerTranslations'])->orderByDesc('created_at')->get();
        return response()->json($banners, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $banners = HeroBanner::with([
            'page', 'heroBannerTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->get();

        return response()->json($banners, Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $validated = $request->validate([
           'page_id' => ['required', 'exists:pages,id'],
           'title' => ['required', 'string', 'max:255'],
           'description' => ['required', 'string', 'max:2000'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $banner = HeroBanner::create(['page_id' => $validated['page_id']]);
            $banner->heroBannerTranslations()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response()->json(['message' => 'Hero banner created'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Hero banner could not be created'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $banner = HeroBanner::with(['page', 'heroBannerTranslations'])->findOrFail($id);
        return response()->json($banner, Response::HTTP_OK);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {
        $validated = $request->validate([
            'page_id' => ['required', 'exists:pages,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();

            $banner = HeroBanner::findOrFail($id);
            $banner->update([
                'page_id' => $validated['page_id'],
            ]);
            $translation = $banner->heroBannerTranslations()
                ->where('language_id', $validated['language_id'])
                ->firstOrFail();

            $translation->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
            ]);

            DB::commit();

            return response()->json(['message' => 'Hero banner updated'], Response::HTTP_OK);

        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Hero banner could not be updated'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $banner = HeroBanner::findOrFail($id);
        try{
            DB::beginTransaction();
            $banner->heroBannerTranslations()->delete();
            $banner->delete();
            DB::commit();
            return response()->json(['message' => 'Hero banner deleted'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Hero banner could not be deleted'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
