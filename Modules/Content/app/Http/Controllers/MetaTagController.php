<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\Language;
use Modules\Content\Models\MetaTag;
use Illuminate\Http\Response;
class MetaTagController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tags = MetaTag::with('metaTagTranslations')->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($tags, Response::HTTP_OK);
    }
    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('fetchByLanguage', MetaTag::class);

        $metaTags = MetaTag::with([
            'metaTagTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->paginate(15);

        return response()->json($metaTags, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $this->authorize('create', MetaTag::class);
        $validated = $request->validate([
            'page_id' => ['required', 'exists:pages,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'og_title' => ['sometimes', 'string', 'max:255'],
            'og_description' => ['sometimes', 'string', 'max:1000'],
            'og_type' => ['sometimes', 'string', 'max:255'],
            'og_url' => ['sometimes', 'string', 'max:255'],
            'twitter_card' => ['sometimes', 'string', 'max:255'],
            'twitter_title' => ['sometimes', 'string', 'max:255'],
            'twitter_description' => ['sometimes', 'string', 'max:1000'],
            'language_id' => ['sometimes', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $metaTag = MetaTag::create([
                'page_id' => $validated['page_id']
            ]);
            $metaTag->metaTagTranslations()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'og_title' => $validated['og_title'],
                'og_description' => $validated['og_description'],
                'og_type' => $validated['og_type'],
                'og_url' => $validated['og_url'],
                'twitter_card' => $validated['twitter_card'],
                'twitter_title' => $validated['twitter_title'],
                'twitter_description' => $validated['twitter_description'],
                'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response()->json(['message' => 'Meta tag created successfully!'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Meta tag could not be created!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $metaTag = MetaTag::with('metaTagTranslations')->findOrFail($id);
        $this->authorize('view', $metaTag);
        return response()->json($metaTag, Response::HTTP_OK);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {
        $metaTag = MetaTag::with('metaTagTranslations')->findOrFail($id);
        $this->authorize('update', $metaTag);
        $validated = $request->validate([
            'page_id' => ['required', 'exists:pages,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'og_title' => ['sometimes', 'string', 'max:255'],
            'og_description' => ['sometimes', 'string', 'max:1000'],
            'og_type' => ['sometimes', 'string', 'max:255'],
            'og_url' => ['sometimes', 'string', 'max:255'],
            'twitter_card' => ['sometimes', 'string', 'max:255'],
            'twitter_title' => ['sometimes', 'string', 'max:255'],
            'twitter_description' => ['sometimes', 'string', 'max:1000'],
            'language_id' => ['sometimes', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $metaTag->update(['page_id' => $validated['page_id']]);
            $translation = $metaTag->metaTagTranslations()
                ->where('language_id', $validated['language_id'])
                ->firstOrFail();

            $translation->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'og_title' => $validated['og_title'],
                'og_description' => $validated['og_description'],
                'og_type' => $validated['og_type'],
                'og_url' => $validated['og_url'],
                'twitter_card' => $validated['twitter_card'],
                'twitter_title' => $validated['twitter_title'],
                'twitter_description' => $validated['twitter_description']
            ]);
            DB::commit();
            return response()->json(['message' => 'Meta tag updated successfully!'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Meta tag could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $metaTag = MetaTag::with('metaTagTranslations')->findOrFail($id);
        $this->authorize('delete', $metaTag);

        try{
           DB::beginTransaction();
           $metaTag->metaTagTranslations()->delete();
           $metaTag->delete();
           DB::commit();
           return response()->json(['message' => 'Meta tag deleted successfully!'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Meta tag could not be deleted!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
