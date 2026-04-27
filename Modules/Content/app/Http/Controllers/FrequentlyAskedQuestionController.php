<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\FrequentlyAskedQuestion;
use Illuminate\Http\Response;
use Modules\Content\Models\Language;

class FrequentlyAskedQuestionController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', FrequentlyAskedQuestion::class);
        $faq = FrequentlyAskedQuestion::with(['frequentlyAskedQuestionTranslations'])->orderByDesc('created_at')->get();
        return response()->json($faq, Response::HTTP_OK);
    }

    public function getByPageAndLang($pageId, $lang)
    {
        $languageId = Language::where('name', $lang)->value('id');

        $banner = FrequentlyAskedQuestion::with(['frequentlyAskedQuestionTranslations' => function ($q) use ($languageId) {
            $q->where('language_id', $languageId);
        }])
            ->where('page_id', $pageId)
            ->get();

        return response()->json($banner, Response::HTTP_OK);
    }

    public function fetchByLang(string $lang){
        $languageId = Language::where('name', $lang)->value('id');

        if (!$languageId) {
            return response()->json([
                'message' => 'Language not found!'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('fetchByLanguage', FrequentlyAskedQuestion::class);

        $faq = FrequentlyAskedQuestion::with([
            'frequentlyAskedQuestionTranslations' => fn ($q) =>
            $q->where('language_id', $languageId)
        ])->get();

        return response()->json($faq, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', FrequentlyAskedQuestion::class);
        $validated = $request->validate([
            'page_id' => ['required', 'exists:pages,id'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3500'],
            'language_id' => ['required', 'exists:languages,id']
        ]);

        try{
            DB::beginTransaction();
            $faq = FrequentlyAskedQuestion::create(['page_id' => $validated['page_id']]);
            $faq->frequentlyAskedQuestionTranslations()->create([
               'question' => $validated['question'],
               'answer' => $validated['answer'],
               'language_id' => $validated['language_id']
            ]);
            DB::commit();
            return response()->json(['message' => 'FAQ created successfully!'], Response::HTTP_CREATED);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'FAQ could not be created!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $faq = FrequentlyAskedQuestion::with(['frequentlyAskedQuestionTranslations'])->findOrFail($id);
        $this->authorize('view', $faq);
        return response()->json($faq, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $faq = FrequentlyAskedQuestion::with(['frequentlyAskedQuestionTranslations'])->findOrFail($id);
        $this->authorize('update', $faq);
        $validated = $request->validate([
            'page_id' => ['required', 'exists:pages,id'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3500'],
            'language_id' => ['required', 'exists:languages,id']
        ]);
        try{
            DB::beginTransaction();
            $faq->update(['page_id' => $validated['page_id']]);
            $translation = $faq->frequentlyAskedQuestionTranslations()
                ->where('language_id', $validated['language_id'])
                ->firstOrFail();

            $translation->update([
                'question' => $validated['question'],
                'answer' => $validated['answer']
            ]);
            DB::commit();
            return response()->json(['message' => 'FAQ updated successfully!'], Response::HTTP_OK);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'FAQ could not be updated!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $faq = FrequentlyAskedQuestion::with(['frequentlyAskedQuestionTranslations'])->findOrFail($id);
        $this->authorize('delete', $faq);

        try{
            DB::beginTransaction();
            $faq->frequentlyAskedQuestionTranslations()->delete();
            $faq->delete();
            DB::commit();
            return response()->json(['message' => 'FAQ deleted successfully!'], Response::HTTP_OK);
        } catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['message' => 'FAQ could not be deleted!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
