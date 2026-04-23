<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Content\Models\Language;
use Illuminate\Http\Response;
class LanguageController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Language::class);
        $languages = Language::orderByDesc('created_at')->get();
        return response($languages, Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $this->authorize('create', Language::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255']
        ]);

        Language::create([
            'name' => $validated['name']
        ]);
        return response(['message' => 'Language created !'], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $language = Language::findOrFail($id);
        $this->authorize('view', $language);
        return response($language, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {
        $language = Language::findOrFail($id);
        $this->authorize('update', $language);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255']
        ]);

        $language->update([
            'name' => $validated['name']
        ]);

        return response(['message' => 'Language updated !'], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $language = Language::findOrFail($id);
        $this->authorize('delete', $language);
        $language->delete();
        return response(['message' => 'Language deleted !'], Response::HTTP_OK);
    }
}
