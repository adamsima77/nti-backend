<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\Reference\Reference;
use Modules\Content\Models\Language;
use Modules\Content\Models\Partner;
use Modules\Content\Models\PartnerReference;
use Illuminate\Http\Response;

class PartnerReferenceController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $references = PartnerReference::with('partnerReferenceTranslations')->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($references, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $references = PartnerReference::with([
            'partnerReferenceTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->paginate(15);

        return response()->json($references, Response::HTTP_OK);
    }

    public function fetchByLangPublic(string $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $references = PartnerReference::with([
            'partnerReferenceTranslations' => fn($q) => $q
                ->where('language_id', $languageId),
        ])
            ->where('status_id', 1)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($references, Response::HTTP_OK);
    }

    public function showCms(int $id)
    {
        $tag = PartnerReference::with([
            'cmsStatus',
            'page',
            'partnerReferenceTranslations.language',
        ])->find($id);

        if (!$tag) {
            return response()->json(['message' => 'Meta tag not found!'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($tag, Response::HTTP_OK);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $this->authorize('create', PartnerReference::class);

        $validated = $request->validate([
            'status_id'    => ['required', 'exists:cms_statuses,id'],
            'name'         => ['required', 'string', 'max:255'],
            'job_position' => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:2000'],
            'language_id'  => ['required', 'exists:languages,id'],
            'image'        => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        $imagePath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = Storage::disk('public')->put('partner-references', $request->file('image'));
                if (!$imagePath) {
                    throw new \RuntimeException('Image upload failed.');
                }
            }

            $reference = PartnerReference::create([
                'status_id'    => $validated['status_id'],
                'name'         => $validated['name'],
                'job_position' => $validated['job_position'],
                'image'        => $imagePath,
            ]);

            $reference->partnerReferenceTranslations()->create([
                'language_id' => $validated['language_id'],
                'description' => $validated['description'],
            ]);

            DB::commit();

            return response()->json(['message' => 'Reference was created!'], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            return response()->json(['message' => 'Reference could not be created!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
       $reference = PartnerReference::with('partnerReferenceTranslations')->findOrFail($id);
       return response()->json($reference, Response::HTTP_OK);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {
        $reference = PartnerReference::findOrFail($id);
        $this->authorize('update', $reference);

        $validated = $request->validate([
            'status_id'    => ['required', 'exists:cms_statuses,id'],
            'name'         => ['required', 'string', 'max:255'],
            'job_position' => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:2000'],
            'language_id'  => ['required', 'exists:languages,id'],
            'image'        => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        $newImagePath = null;
        $oldImagePath = $reference->image;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $newImagePath = Storage::disk('public')->put('partner-references', $request->file('image'));
                if (!$newImagePath) {
                    throw new \RuntimeException('Image upload failed.');
                }
            }

            $reference->update([
                'status_id'    => $validated['status_id'],
                'name'         => $validated['name'],
                'job_position' => $validated['job_position'],
                'image'        => $newImagePath ?? $oldImagePath,
            ]);

            $translation = $reference->partnerReferenceTranslations()
                ->where('language_id', $validated['language_id'])
                ->first();

            if ($translation) {
                $translation->update(['description' => $validated['description']]);
            } else {
                $reference->partnerReferenceTranslations()->create([
                    'language_id' => $validated['language_id'],
                    'description' => $validated['description'],
                ]);
            }

            if ($newImagePath && $oldImagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }

            DB::commit();

            return response()->json(['message' => 'Reference was updated!'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            return response()->json(['message' => 'Reference could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $reference = PartnerReference::findOrFail($id);
        $this->authorize('delete', $reference);

        try {
            DB::beginTransaction();

            $reference->partnerReferenceTranslations()->delete();

            if ($reference->image) {
                Storage::disk('public')->delete($reference->image);
            }

            $reference->delete();

            DB::commit();

            return response()->json(['message' => 'Reference was deleted!'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reference could not be deleted!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
