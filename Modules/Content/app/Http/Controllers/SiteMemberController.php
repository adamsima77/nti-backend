<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\Language;
use Modules\Content\Models\SiteMember;
use Illuminate\Http\Response;
class SiteMemberController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', SiteMember::class);
        $siteMembers = SiteMember::with(['siteMemberTranslations'])->orderByDesc('created_at')
            ->get();
        return response()->json($siteMembers, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $siteMembers = SiteMember::with([
            'siteMemberTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->get();

        return response()->json($siteMembers, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', SiteMember::class);
        $validated = $request->validate([
            'name' =>['required', 'string', 'max:255'],
            'job_position' =>['required', 'string', 'max:255'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $siteMember = SiteMember::create(['name' => $validated['name']]);
            $siteMember->siteMemberTranslations()->create([
                'job_position' => $validated['job_position'],
                'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response(['message' => 'Site member created'], Response::HTTP_CREATED);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'Site member could not be created'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $siteMember = SiteMember::with(['siteMemberTranslations'])->findOrFail($id);
        $this->authorize('view', $siteMember);
        return response()->json($siteMember, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $siteMember = SiteMember::findOrFail($id);
        $this->authorize('update', $siteMember);
        $validated = $request->validate([
            'name' =>['required', 'string', 'max:255'],
            'job_position' =>['required', 'string', 'max:255'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $siteMember->update(['name' => $validated['name']]);
            $translation = $siteMember->siteMemberTranslations()
                ->where('language_id', $validated['language_id'])
                ->firstOrFail();

            $translation->update([
                'job_position' => $validated['job_position'],
                'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response(['message' => 'Site member updated'], Response::HTTP_OK);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'Site member could not be updated'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $siteMember = SiteMember::findOrFail($id);
        $this->authorize('delete', $siteMember);
        try{
            DB::beginTransaction();
            $siteMember->siteMemberTranslations()->delete();
            $siteMember->delete();
            DB::commit();
            return response(['message' => 'Site member deleted'], Response::HTTP_OK);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'Site member could not be deleted'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
