<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\Language;
use Modules\Programs\Models\Criterion;
class CriterionController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all criteria with their translations.
     * Returns criterion names resolved in the requested language (defaults to SK).
     */
    public function index(Request $request)
    {
        $lang     = $request->query('lang', 'SK');
        $language = Language::where('name', $lang)->first();

        $criteria = Criterion::query()
            ->with('criterionTranslations.language:id,name')
            ->latest('id')
            ->get()
            ->map(fn ($criterion) => $this->formatCriterion($criterion, $language));

        return response()->json($criteria);
    }

    /**
     * Create a criterion with at least one translation.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Criterion::class);

        $validated = $request->validate([
            'code'                           => ['nullable', 'string', 'max:64', 'unique:criterion,code'],
            'translations'                   => ['required', 'array', 'min:1'],
            'translations.*.language_id'     => ['required', 'integer', 'exists:languages,id'],
            'translations.*.name'            => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:1000']
        ]);

        return DB::transaction(function () use ($validated) {
            $criterion = Criterion::create([
                'code' => $validated['code'] ?? null,
            ]);

            foreach ($validated['translations'] as $tr) {
                $criterion->criterionTranslations()->create([
                    'language_id' => $tr['language_id'],
                    'name'        => $tr['name'],
                    'description' => $tr['description'] ?? null,
                ]);
            }

            return response()->json(
                $criterion->load('criterionTranslations.language:id,name'),
                201
            );
        });
    }

    /**
     * Show a single criterion with all translations.
     */
    public function show(int $id)
    {
        $criterion = Criterion::with('criterionTranslations.language:id,name')
            ->findOrFail($id);

        return response()->json($criterion);
    }

    /**
     * Update criterion translations (upsert per language).
     */
    public function update(Request $request, int $id)
    {
        $criterion = Criterion::findOrFail($id);
        $this->authorize('update', $criterion);

        $validated = $request->validate([
            'code'                       => ['nullable', 'string', 'max:64', 'unique:criterion,code,' . $id],
            'translations'               => ['sometimes', 'array'],
            'translations.*.language_id' => ['required_with:translations', 'integer', 'exists:languages,id'],
            'translations.*.name'        => ['required_with:translations', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($validated, $criterion) {
            if (array_key_exists('code', $validated)) {
                $criterion->update(['code' => $validated['code']]);
            }

            foreach ($validated['translations'] ?? [] as $tr) {
                $criterion->criterionTranslations()->updateOrCreate(
                    ['language_id' => $tr['language_id']],
                    ['name'        => $tr['name']]
                );
            }

            return response()->json(
                $criterion->load('criterionTranslations.language:id,name')
            );
        });
    }

    /**
     * Delete a criterion (will fail if attached to calls via FK or you can cascade).
     */
    public function destroy(int $id)
    {
        $criterion = Criterion::findOrFail($id);
        $this->authorize('delete', $criterion);

        return DB::transaction(function () use ($criterion) {
            $criterion->criterionTranslations()->delete();
            $criterion->delete();

            return response()->json(['message' => 'Kritérium bolo zmazané.']);
        });
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function formatCriterion(Criterion $criterion, ?Language $language): array
    {
        $tr = $language
            ? $criterion->criterionTranslations->firstWhere('language_id', $language->id)
            : $criterion->criterionTranslations->first();

        return [
            'id'           => $criterion->id,
            'code'         => $criterion->code ?? null,
            'name'         => $tr?->name ?? "[No translation — criterion #{$criterion->id}]",
            'translations' => $criterion->criterionTranslations->map(fn ($t) => [
                'language_id'   => $t->language_id,
                'language_name' => $t->language?->name,
                'name'          => $t->name,
            ]),
        ];
    }
}
