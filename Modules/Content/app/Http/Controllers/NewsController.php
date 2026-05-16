<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Content\Models\CmsStatus;
use Modules\Content\Models\Language;
use Modules\Content\Models\News;
use Illuminate\Http\Response;

class NewsController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $news = News::with(['category', 'cmsStatus', 'user', 'newsTranslations'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($news, Response::HTTP_OK);
    }

    public function fetchBySlug($slug, $lang)
    {
        $langId = Language::where('name', $lang)->value('id');

        if (!$langId) {
            abort(404, 'Language not found');
        }

        $news = News::with([
            'cmsStatus:id,name',
            'category.categoryTranslations' => fn($q) => $q
                ->where('language_id', $langId)
                ->select('id', 'name', 'category_id', 'language_id'),
            'user',
            // public-facing: only the requested language
            'newsTranslations' => fn($q) => $q
                ->where('language_id', $langId)
                ->select('id', 'title', 'description', 'news_id', 'language_id'),
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($news, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $news = News::with([
            'cmsStatus:id,name',

            'category.categoryTranslations' => fn($q) => $q
                ->where('language_id', $languageId)
                ->select('id', 'name', 'category_id', 'language_id'),

            'user',

            'newsTranslations' => fn($q) => $q
                ->where('language_id', $languageId),
        ])->orderByDesc('news.created_at')->paginate(15);

        return response()->json($news, Response::HTTP_OK);
    }

    public function fetchByLangPublic(string $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $news = News::with([
            'category.categoryTranslations' => fn($q) => $q
                ->where('language_id', $languageId)
                ->select('id', 'name', 'category_id', 'language_id'),

            'newsTranslations' => fn($q) => $q
                ->where('language_id', $languageId),

            'user:id,name,surname',
        ])
            ->where('status_id', 1)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($news, Response::HTTP_OK);
    }

    public function showCms(int $id)
    {
        $news = News::with([
            'cmsStatus',
            'category.categoryTranslations',
            'user',
            'newsTranslations.language',
        ])->find($id);

        if (!$news) {
            return response()->json([
                'message' => 'News not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($news, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $this->authorize('create', News::class);

        $validated = $request->validate([
            'slug'        => ['required', 'unique:news', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'title'       => ['required', 'max:255'],
            'description' => ['required'],
            'language_id' => ['required', 'exists:languages,id'],
            'status_id'   => ['nullable', 'exists:cms_statuses,id'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        try {
            DB::beginTransaction();

            $path = null;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('news', 'public');
            }

            $statusId = $validated['status_id']
                ?? CmsStatus::where('name', 'Koncept')->value('id');

            $news = News::create([
                'slug'        => $validated['slug'],
                'category_id' => $validated['category_id'],
                'user_id'     => $request->user()->id,
                'status_id'   => $statusId,
                'image'       => $path,
            ]);

            $news->newsTranslations()->create([
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'language_id' => $validated['language_id'],
            ]);

            DB::commit();

            return response()->json(['message' => 'News article created'], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'News article could not be created'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        $news = News::with(['category', 'user', 'cmsStatus', 'newsTranslations'])->findOrFail($id);
        return response()->json($news, Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);
        $this->authorize('update', $news);

        $validated = $request->validate([
            'slug'        => ['required', 'max:255', Rule::unique('news', 'slug')->ignore($id)],
            'category_id' => ['required', 'exists:categories,id'],
            'title'       => ['required', 'max:255'],
            'description' => ['required'],
            'language_id' => ['required', 'exists:languages,id'],
            'status_id'   => ['nullable', 'exists:cms_statuses,id'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        try {
            DB::beginTransaction();

            $path = $news->image;
            if ($request->hasFile('image')) {
                if ($news->image && Storage::disk('public')->exists($news->image)) {
                    Storage::disk('public')->delete($news->image);
                }
                $path = $request->file('image')->store('news', 'public');
            }

            $news->update([
                'slug'        => $validated['slug'],
                'category_id' => $validated['category_id'],
                'status_id'   => $validated['status_id'] ?? $news->status_id,
                'image'       => $path,
            ]);

            $translation = $news->newsTranslations()
                ->firstOrCreate(
                    ['language_id' => $validated['language_id']],
                    ['title' => '', 'description' => '']
                );

            $translation->update([
                'title'       => $validated['title'],
                'description' => $validated['description'],
            ]);

            DB::commit();

            return response()->json(['message' => 'News article updated'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'News article could not be updated'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        $news = News::findOrFail($id);
        $this->authorize('delete', $news);

        try {
            DB::beginTransaction();

            if ($news->image && Storage::disk('public')->exists($news->image)) {
                Storage::disk('public')->delete($news->image);
            }

            $news->newsTranslations()->delete();
            $news->delete();

            DB::commit();

            return response()->json(['message' => 'News article deleted'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete news article'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
