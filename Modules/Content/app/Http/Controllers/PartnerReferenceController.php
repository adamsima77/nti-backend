<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Reference\Reference;
use Modules\Content\Models\Language;
use Modules\Content\Models\PartnerReference;
use Illuminate\Http\Response;

class PartnerReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $references = PartnerReference::with('partnerReferenceTranslations')->orderByDesc('created_at')->get();
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
        ])->get();

        return response()->json($references, Response::HTTP_OK);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_position' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $reference = PartnerReference::create([]);
            $reference->partnerReferenceTranslations()->create([
                'name' => $validated['name'],
                'job_position' => $validated['job_position'],
                'description' => $validated['description'],
                'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response()->json(['message' => 'Reference was created !'], Response::HTTP_CREATED);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'Reference could not be created !'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_position' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $translation = $reference->partnerReferenceTranslations()
                ->where('language_id', $validated['language_id'])
                ->firstOrFail();
            $translation->update([
                'name' => $validated['name'],
                'job_position' => $validated['job_position'],
                'description' => $validated['description'],
            ]);
            DB::commit();
            return response()->json(['message' => 'Reference was updated !'], Response::HTTP_OK);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'Reference could not be updated !'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
          $reference = PartnerReference::findOrFail($id);
          try{
              DB::beginTransaction();
              $reference->partnerReferenceTranslations()->delete();
              $reference->delete();
              DB::commit();
              return response()->json(['message' => 'Reference was deleted !'], Response::HTTP_OK);
          } catch(\Throwable $e){
              DB::rollBack();
              return response()->json(['message' => 'Reference could not be deleted !'], Response::HTTP_INTERNAL_SERVER_ERROR);
          }
    }
}
