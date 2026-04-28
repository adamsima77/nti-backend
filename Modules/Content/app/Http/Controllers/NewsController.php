<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Content\Models\Language;
use Modules\Content\Models\News;
use Illuminate\Http\Response;
class NewsController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $news = News::with(['category', 'user', 'newsTranslations'])->orderByDesc('created_at')->paginate(15);
        return response()->json($news, Response::HTTP_OK);
    }

    public function fetchBySlug($slug, $lang)
    {
        $lang_id = Language::where('name', $lang)->firstOrFail()->id;
        $news = News::with([
            'category',
            'user',
            'newsTranslations' => fn($q) => $q->where('language_id', $lang_id)
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($news, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');
        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $news = News::with([
            'category', 'user', 'newsTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->paginate(15);

        return response()->json($news, Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $this->authorize('create', News::class);
        $validated = $request->validate([
            'slug' => ['required', 'unique:news', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'max:255'],
            'description' => ['required'],
            'language_id' => ['required', 'exists:languages,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096']
        ]);

        try{
            DB::beginTransaction();
            $path = null;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('news', 'public');
            }
            $news = News::create([
                'slug' => $validated['slug'],
                'category_id' => $validated['category_id'],
                'user_id' => $request->user()->id,
                'image' => $path
            ]);

            $news->newsTranslations()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'language_id' => $validated['language_id']
            ]);

            DB::commit();
            return response()->json(['message' => 'News article created'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'News article could not be created'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $news = News::with(['category', 'user', 'newsTranslations'])->findOrFail($id);
        $this->authorize('view', $news);
        return response()->json($news, Response::HTTP_OK);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);
        $this->authorize('update', $news);

        $validated = $request->validate([
            'slug' => ['required', 'max:255', Rule::unique('news', 'slug')->ignore($id)],
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'max:255'],
            'description' => ['required'],
            'language_id' => ['required', 'exists:languages,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096']
        ]);

        $languageId = $validated['language_id'];

        try {
            DB::beginTransaction();
            if($request->hasFile('image')) {
            if($news->image && Storage::disk('public')->exists($news->image)) {
                Storage::delete($news->image);
            }
            $path = $request->file('image')->store('news', 'public');
            }
            $news->update([
                'slug' => $validated['slug'],
                'category_id' => $validated['category_id'],
                'image' => $path ?? $news->image
            ]);

            $translation = $news->newsTranslations()
                ->where('language_id', $languageId)
                ->firstOrFail();

            $translation->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
            ]);

            DB::commit();

            return response()->json(['message' => 'News article updated'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'News article could not be updated'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $news = News::findOrFail($id);
        $this->authorize('delete', $news);

        try {
            DB::beginTransaction();
            if($news->image && Storage::disk('public')->exists($news->image)){
                Storage::delete($news->image);
            }
            $news->newsTranslations()->delete();
            $news->delete();

            DB::commit();

            return response()->json([
                'message' => 'News article deleted'
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete news article'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
