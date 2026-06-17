<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\Language;
use Illuminate\Http\Response;
use Modules\Content\Models\MetaTag;

class MetaTagController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $tags = MetaTag::with('metaTagTranslations')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($tags, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json(['message' => 'Language not found!'], Response::HTTP_NOT_FOUND);
        }

        $metaTags = MetaTag::with([
            'page',
            'metaTagTranslations' => fn($q) =>
            $q->where('language_id', $languageId)
        ])->paginate(15);

        return response()->json($metaTags, Response::HTTP_OK);
    }

    public function showCms(int $id)
    {
        $tag = MetaTag::with([
            'cmsStatus',
            'page',
            'metaTagTranslations.language',
        ])->find($id);

        if (!$tag) {
            return response()->json(['message' => 'Meta tag not found!'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($tag, Response::HTTP_OK);
    }

    public function getByPageAndLang($pageId, $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        $metaTag = MetaTag::with([
            'metaTagTranslations' => fn($q) =>
            $q->where('language_id', $languageId)
        ])
            ->where('page_id', $pageId)
            ->where('status_id', 1)
            ->firstOrFail();

        return response()->json($metaTag, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $this->authorize('create', MetaTag::class);
        $validated = $request->validate([
            'page_id'             => ['required', 'exists:pages,id'],
            'status_id'           => ['required', 'exists:cms_statuses,id'],
            'language_id'         => ['required', 'exists:languages,id'],
            'title'               => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'og_title'            => ['nullable', 'string', 'max:255'],
            'og_description'      => ['nullable', 'string', 'max:1000'],
            'og_type'             => ['nullable', 'string', 'max:255'],
            'og_url'              => ['nullable', 'string', 'max:255'],
            'twitter_card'        => ['nullable', 'string', 'max:255'],
            'twitter_title'       => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:1000'],
            'image'               => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        $imagePath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = Storage::disk('public')->put('meta-tags', $request->file('image'));

                if (!$imagePath) {
                    throw new \RuntimeException('Image upload failed.');
                }
            }

            $metaTag = MetaTag::create([
                'page_id'   => $validated['page_id'],
                'status_id' => $validated['status_id'],
                'image'     => $imagePath,
            ]);

            $metaTag->metaTagTranslations()->create([
                'language_id'         => $validated['language_id'],
                'title'               => $validated['title'] ?? null,
                'description'         => $validated['description'] ?? null,
                'og_title'            => $validated['og_title'] ?? null,
                'og_description'      => $validated['og_description'] ?? null,
                'og_type'             => $validated['og_type'] ?? null,
                'og_url'              => $validated['og_url'] ?? null,
                'twitter_card'        => $validated['twitter_card'] ?? null,
                'twitter_title'       => $validated['twitter_title'] ?? null,
                'twitter_description' => $validated['twitter_description'] ?? null,
            ]);

            DB::commit();

            return response()->json(['message' => 'Meta tag created successfully!'], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json(['message' => 'Meta tag could not be created!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        $metaTag = MetaTag::with('metaTagTranslations')->findOrFail($id);
        $this->authorize('view', $metaTag);

        return response()->json($metaTag, Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        $metaTag = MetaTag::with('metaTagTranslations')->findOrFail($id);
        $this->authorize('update', $metaTag);
        $validated = $request->validate([
            'page_id'             => ['required', 'exists:pages,id'],
            'status_id'           => ['required', 'exists:cms_statuses,id'],
            'language_id'         => ['required', 'exists:languages,id'],
            'title'               => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'og_title'            => ['nullable', 'string', 'max:255'],
            'og_description'      => ['nullable', 'string', 'max:1000'],
            'og_type'             => ['nullable', 'string', 'max:255'],
            'og_url'              => ['nullable', 'string', 'max:255'],
            'twitter_card'        => ['nullable', 'string', 'max:255'],
            'twitter_title'       => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:1000'],
            'image'               => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        $newImagePath = null;
        $oldImagePath = $metaTag->image;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $newImagePath = Storage::disk('public')->put('meta-tags', $request->file('image'));

                if (!$newImagePath) {
                    throw new \RuntimeException('Image upload failed.');
                }
            }

            $metaTag->update([
                'page_id'   => $validated['page_id'],
                'status_id' => $validated['status_id'],
                'image'     => $newImagePath ?? $oldImagePath, // keep old if no new image sent
            ]);

            $translation = $metaTag->metaTagTranslations()
                ->where('language_id', $validated['language_id'])
                ->first();

            $translationData = [
                'title'               => $validated['title'] ?? null,
                'description'         => $validated['description'] ?? null,
                'og_title'            => $validated['og_title'] ?? null,
                'og_description'      => $validated['og_description'] ?? null,
                'og_type'             => $validated['og_type'] ?? null,
                'og_url'              => $validated['og_url'] ?? null,
                'twitter_card'        => $validated['twitter_card'] ?? null,
                'twitter_title'       => $validated['twitter_title'] ?? null,
                'twitter_description' => $validated['twitter_description'] ?? null,
            ];

            if ($translation) {
                $translation->update($translationData);
            } else {
                $metaTag->metaTagTranslations()->create([
                    'language_id' => $validated['language_id'],
                    ...$translationData,
                ]);
            }

            // Only delete old image after everything succeeded
            if ($newImagePath && $oldImagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }

            DB::commit();

            return response()->json(['message' => 'Meta tag updated successfully!'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();

            // New image was uploaded but DB failed — clean up the orphaned file
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            return response()->json(['message' => 'Meta tag could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        $metaTag = MetaTag::with('metaTagTranslations')->findOrFail($id);
        $this->authorize('delete', $metaTag);

        try {
            DB::beginTransaction();

            $metaTag->metaTagTranslations()->delete();

            if ($metaTag->image) {
                Storage::disk('public')->delete($metaTag->image);
            }

            $metaTag->delete();

            DB::commit();

            return response()->json(['message' => 'Meta tag deleted successfully!'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Meta tag could not be deleted!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
