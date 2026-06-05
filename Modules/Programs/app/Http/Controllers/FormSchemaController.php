<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\FormSchema;
use Modules\Programs\Models\FormField;

class FormSchemaController extends Controller
{
    use AuthorizesRequests;

    // ────────────────────────────────────────────────────────────────
    //  Schema CRUD
    // ────────────────────────────────────────────────────────────────

    /**
     * Return the active (published) schema, falling back to the latest draft.
     */
    public function show(int $callId)
    {
        $call = Call::findOrFail($callId);

        $schema = FormSchema::where('call_id', $callId)
            ->with('formFields')
            ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
            ->orderByDesc('version')
            ->firstOrFail();

        return response()->json($this->formatSchema($schema));
    }

    /**
     * Create a new draft schema for the call (auto-increments version).
     */
    public function store(Request $request, int $callId)
    {
        $call = Call::findOrFail($callId);
        $this->authorize('update', $call);

        $validated = $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sections'    => ['nullable', 'array'],
            'meta'        => ['nullable', 'array'],
        ]);

        $nextVersion = (FormSchema::where('call_id', $callId)->max('version') ?? 0) + 1;

        $schema = FormSchema::create([
            'call_id'     => $callId,
            'version'     => $nextVersion,
            'status'      => 'draft',
            'title'       => $validated['title']       ?? null,
            'description' => $validated['description'] ?? null,
            'sections'    => isset($validated['sections'])
                ? json_encode($validated['sections'])
                : null,
            'meta'        => isset($validated['meta'])
                ? json_encode($validated['meta'])
                : null,
        ]);

        return response()->json($this->formatSchema($schema), 201);
    }

    /**
     * Update a draft schema (cannot update a published schema — create new version).
     */
    public function update(Request $request, int $callId, int $id)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($id);

        abort_if(
            $schema->status === 'published',
            422,
            'Publikovanú schému nemožno upravovať. Vytvorte novú verziu.'
        );

        $validated = $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sections'    => ['nullable', 'array'],
            'meta'        => ['nullable', 'array'],
        ]);

        $schema->update([
            'title'       => $validated['title']       ?? $schema->title,
            'description' => $validated['description'] ?? $schema->description,
            'sections'    => isset($validated['sections'])
                ? json_encode($validated['sections'])
                : $schema->sections,
            'meta'        => isset($validated['meta'])
                ? json_encode($validated['meta'])
                : $schema->meta,
        ]);

        return response()->json($this->formatSchema($schema->fresh(['formFields'])));
    }

    /**
     * Publish a draft: sets status = published, stamps published_at,
     * and demotes the previous published schema to 'archived'.
     */
    public function publish(int $callId, int $id)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($id);

        abort_if(
            $schema->status === 'published',
            422,
            'Schéma je už publikovaná.'
        );

        return DB::transaction(function () use ($schema, $callId) {
            // Archive the current published version
            FormSchema::where('call_id', $callId)
                ->where('status', 'published')
                ->update(['status' => 'archived']);

            $schema->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);

            return response()->json($this->formatSchema($schema->fresh()));
        });
    }

    /**
     * Delete a draft schema and its fields.
     */
    public function destroy(int $callId, int $id)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($id);

        abort_if(
            $schema->status === 'published',
            422,
            'Publikovanú schému nemožno zmazať. Archivujte ju publikovaním novej verzie.'
        );

        return DB::transaction(function () use ($schema) {
            $schema->formFields()->delete();
            $schema->delete();

            return response()->json(['message' => 'Schéma bola zmazaná.']);
        });
    }

    // ────────────────────────────────────────────────────────────────
    //  Field CRUD
    // ────────────────────────────────────────────────────────────────

    public function listFields(int $callId, int $schemaId)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($schemaId);

        return response()->json(
            $schema->formFields()->orderBy('sort_order')->get()
        );
    }

    /**
     * Supported field types:
     *   text | textarea | number | date | select | multiselect
     *   checkbox | radio | file | heading | divider
     */
    public function storeField(Request $request, int $callId, int $schemaId)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($schemaId);

        abort_if($schema->status === 'published', 422, 'Pridajte polia do draft verzie.');

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:191'],
            'type'       => ['required', 'string', 'in:text,textarea,number,date,select,multiselect,checkbox,radio,file,heading,divider'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'config'     => ['nullable', 'array'],

            // config sub-rules (all optional / flexible)
            'config.label'       => ['nullable', 'string', 'max:255'],
            'config.placeholder' => ['nullable', 'string', 'max:255'],
            'config.required'    => ['nullable', 'boolean'],
            'config.options'     => ['nullable', 'array'],        // for select / radio
            'config.accept'      => ['nullable', 'string'],       // for file
            'config.max_size_mb' => ['nullable', 'integer'],      // for file
            'config.min'         => ['nullable', 'numeric'],      // for number
            'config.max'         => ['nullable', 'numeric'],      // for number
            'config.help_text'   => ['nullable', 'string'],
        ]);

        // Auto-assign next sort_order if not provided
        if (! isset($validated['sort_order'])) {
            $validated['sort_order'] =
                ($schema->formFields()->max('sort_order') ?? -1) + 1;
        }

        $field = $schema->formFields()->create([
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'sort_order' => $validated['sort_order'],
            'config'     => isset($validated['config'])
                ? json_encode($validated['config'])
                : null,
        ]);

        return response()->json($field, 201);
    }

    public function updateField(Request $request, int $callId, int $schemaId, int $fieldId)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($schemaId);
        abort_if($schema->status === 'published', 422, 'Upravujte polia v draft verzii.');

        $field = FormField::where('form_schema_id', $schemaId)->findOrFail($fieldId);

        $validated = $request->validate([
            'name'       => ['sometimes', 'string', 'max:191'],
            'type'       => ['sometimes', 'string', 'in:text,textarea,number,date,select,multiselect,checkbox,radio,file,heading,divider'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'config'     => ['nullable', 'array'],
        ]);

        $field->update([
            'name'       => $validated['name']       ?? $field->name,
            'type'       => $validated['type']       ?? $field->type,
            'sort_order' => $validated['sort_order'] ?? $field->sort_order,
            'config'     => isset($validated['config'])
                ? json_encode($validated['config'])
                : $field->config,
        ]);

        return response()->json($field->fresh());
    }

    public function destroyField(int $callId, int $schemaId, int $fieldId)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($schemaId);
        abort_if($schema->status === 'published', 422, 'Mažte polia v draft verzii.');

        $field = FormField::where('form_schema_id', $schemaId)->findOrFail($fieldId);
        $field->delete();

        return response()->json(['message' => 'Pole bolo zmazané.']);
    }

    /**
     * Bulk reorder fields by passing an ordered array of field IDs.
     *
     * POST /admin/calls/{callId}/form-schema/{schemaId}/fields/reorder
     * Body: { "ids": [3, 1, 5, 2, 4] }
     */
    public function reorderFields(Request $request, int $callId, int $schemaId)
    {
        $schema = FormSchema::where('call_id', $callId)->findOrFail($schemaId);
        abort_if($schema->status === 'published', 422, 'Usporiadajte polia v draft verzii.');

        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:form_field,id'],
        ]);

        DB::transaction(function () use ($validated, $schemaId) {
            foreach ($validated['ids'] as $order => $fieldId) {
                FormField::where('id', $fieldId)
                    ->where('form_schema_id', $schemaId)
                    ->update(['sort_order' => $order]);
            }
        });

        return response()->json(['message' => 'Poradie polí bolo aktualizované.']);
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function formatSchema(FormSchema $schema): array
    {
        return [
            'id'           => $schema->id,
            'call_id'      => $schema->call_id,
            'version'      => $schema->version,
            'status'       => $schema->status,
            'title'        => $schema->title,
            'description'  => $schema->description,
            'sections'     => $schema->sections ? json_decode($schema->sections, true) : null,
            'meta'         => $schema->meta     ? json_decode($schema->meta, true)     : null,
            'published_at' => $schema->published_at,
            'fields'       => $schema->formFields
                ? $schema->formFields
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn ($f) => [
                        'id'         => $f->id,
                        'name'       => $f->name,
                        'type'       => $f->type,
                        'sort_order' => $f->sort_order,
                        'config'     => $f->config ? json_decode($f->config, true) : null,
                    ])
                : [],
        ];
    }
}
