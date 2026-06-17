<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\Language;
use Modules\Content\Models\Partner;

class PartnerController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $partners = Partner::with(['partnerTranslations'])->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($partners, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $partners = Partner::with([
            'partnerTranslations' => fn($q) =>
            $q->where('language_id', $languageId)
        ])->paginate(15);

        return response()->json($partners, Response::HTTP_OK);
    }

    public function fetchByLangPublic(string $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $partners = Partner::with([
            'partnerTranslations' => fn($q) => $q
                ->where('language_id', $languageId)
        ])
            ->where('status_id', 1)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($partners, Response::HTTP_OK);
    }

    public function showCms(int $id)
    {
        $partner = Partner::with([
            'cmsStatus',
            'partnerTranslations.language',
        ])->find($id);

        if (!$partner) {
            return response()->json([
                'message' => 'Partner not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($partner, Response::HTTP_OK);
    }

    public function fetchImages()
    {
        $images = Partner::get(['image']);
        return response()->json($images, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Partner::class);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2500'],
            'language_id' => ['required', 'exists:languages,id'],
            'status_id'   => ['nullable', 'exists:cms_statuses,id'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        try {
            DB::beginTransaction();

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('partners', 'public');
            }

            $partner = Partner::create([
                'name'      => $validated['name'],
                'image'     => $imagePath,
                'status_id' => $validated['status_id'] ?? null,
            ]);

            $partner->partnerTranslations()->create([
                'description' => $validated['description'],
                'language_id' => $validated['language_id'],
            ]);

            DB::commit();

            return response()->json(['message' => 'Partner successfully created!'], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            // Clean up uploaded file if DB failed
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            return response()->json(['message' => 'Partner could not be created!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $partner = Partner::with(['partnerTranslations'])->findOrFail($id);
        $this->authorize('view', $partner);
        return response()->json($partner, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $partner = Partner::findOrFail($id);
        $this->authorize('update', $partner);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2500'],
            'language_id' => ['required', 'exists:languages,id'],
            'status_id'   => ['nullable', 'exists:cms_statuses,id'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:4096'],
        ]);

        try {
            DB::beginTransaction();

            $partnerData = [
                'name'      => $validated['name'],
                'status_id' => $validated['status_id'] ?? $partner->status_id,
            ];

            if ($request->hasFile('image')) {
                // Delete old image if it exists
                if ($partner->image) {
                    Storage::disk('public')->delete($partner->image);
                }
                $partnerData['image'] = $request->file('image')->store('partners', 'public');
            }

            $partner->update($partnerData);

            $partner->partnerTranslations()
                ->updateOrCreate(
                    ['language_id' => $validated['language_id']],
                    ['description' => $validated['description']],
                );

            DB::commit();

            return response()->json(['message' => 'Partner successfully updated!'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Partner could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $partner = Partner::findOrFail($id);
        $this->authorize('delete', $partner);

        try {
            DB::beginTransaction();

            if ($partner->image) {
                Storage::disk('public')->delete($partner->image);
            }

            $partner->partnerTranslations()->delete();
            $partner->delete();

            DB::commit();

            return response()->json(['message' => 'Partner successfully deleted!'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Partner could not be deleted!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
