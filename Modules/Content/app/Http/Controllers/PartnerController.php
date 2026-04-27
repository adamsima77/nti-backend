<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
        $this->authorize('viewAny', Partner::class);
        $partners = Partner::with(['partnerTranslations'])->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($partners, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }
        $this->authorize('fetchByLanguage', Partner::class);

        $partners = Partner::with([
            'partnerTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->paginate(15);

        return response()->json($partners, Response::HTTP_OK);
    }

    public function fetchImages(){
        $images = Partner::get(['image']);
        return response()->json($images, Response::HTTP_OK);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $this->authorize('create', Partner::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2500'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $partner = Partner::create([]);
            $partner->partnerTranslations()->create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response()->json(['message' => 'Partner successfully created!'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Partner could not be created !'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
    public function update(Request $request, $id) {
        $partner = Partner::findOrFail($id);
        $this->authorize('update', $partner);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2500'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try {
            DB::beginTransaction();
            $translation = $partner->partnerTranslations()
                ->where('language_id', $validated['language_id'])
                ->firstOrFail();

            $translation->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);

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
    public function destroy($id) {
        $partner = Partner::findOrFail($id);
        $this->authorize('delete', $partner);
        try{
            DB::beginTransaction();
            $partner->partnerTranslations()->delete();
            $partner->delete();
            DB::commit();
            return response()->json(['message' => 'Partner successfully deleted!'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Partner could not be deleted!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
