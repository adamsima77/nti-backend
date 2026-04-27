<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Content\Models\Category;
use Illuminate\Http\Response;
use Modules\Content\Models\Language;

class CategoryController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Category::class);
        $categories = Category::with('categoryTranslations')->orderByDesc('created_at')
            ->paginate(15);
        return response()->json($categories, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('fetchByLanguage', Category::class);

        $categories = Category::with([
            'categoryTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->paginate(15);

        return response()->json($categories, Response::HTTP_OK);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $this->authorize('create', Category::class);
        $validated = $request->validate([
            'slug' => ['required', 'unique:categories', 'max:255'],
            'name' => ['required', 'max:255'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        DB::beginTransaction();
        try {
            $category = Category::create(['slug' => $validated['slug']]);
            $category->categoryTranslations()->create([
                'name' => $validated['name'],
                'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response()->json(['message' => 'Category created!'], Response::HTTP_CREATED);
        } catch(\Throwable  $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create category'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $category = Category::with('categoryTranslations')->findOrFail($id);
        $this->authorize('view', $category);
        return response()->json($category, Response::HTTP_OK);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {
        $category = Category::findOrFail($id);
        $this->authorize('update', $category);

        $validated = $request->validate([
            'slug' => ['required','max:255',Rule::unique('categories', 'slug')->ignore($id)],
            'name' => ['required', 'max:255'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $category->update(['slug' => $validated['slug']]);
            $translation = $category->categoryTranslations()
                ->where('language_id', $validated['language_id'])
                ->firstOrFail();
            $translation->update([
                'name' => $validated['name'],
            ]);
            DB::commit();
            return response()->json(['message' => 'Category updated!'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update category'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        $category = Category::findOrFail($id);
        $this->authorize('delete', $category);
        try {
            DB::beginTransaction();
            $category->categoryTranslations()->delete();
            $category->delete();

            DB::commit();
            return response()->json(['message' => 'Category deleted!'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete category'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
