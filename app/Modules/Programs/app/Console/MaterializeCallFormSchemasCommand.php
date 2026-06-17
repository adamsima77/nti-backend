<?php

namespace Modules\Programs\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\Language;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\FormField;
use Modules\Programs\Models\FormSchema;
use Modules\Programs\Support\CallFormSchema;

class MaterializeCallFormSchemasCommand extends Command
{
    protected $signature = 'programs:materialize-call-form-schemas {--call= : Only this call ID}';

    protected $description = 'Create published form_schema + form_field rows from legacy call configuration (criteria + JSON overlay).';

    public function handle(): int
    {
        $onlyCall = $this->option('call');

        $langSk = Language::query()->where('name', 'sk')->firstOrFail();
        $langEn = Language::query()->where('name', 'en')->first();

        $query = Call::query()
            ->with([
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
            ]);

        if ($onlyCall !== null && $onlyCall !== '') {
            $query->whereKey((int) $onlyCall);
        }

        $calls = $query->get();

        foreach ($calls as $call) {
            if (FormSchema::query()->where('call_id', $call->id)->where('status', 'published')->exists()) {
                $this->line("Skip call {$call->id}: published form_schema already exists.");

                continue;
            }

            $builtSk = CallFormSchema::buildLegacy($call, $langSk, 'sk');
            $builtEn = $langEn !== null
                ? CallFormSchema::buildLegacy($call, $langEn, 'en')
                : $builtSk;

            $nextVersion = (int) (FormSchema::query()->where('call_id', $call->id)->max('version') ?? 0) + 1;

            DB::transaction(function () use ($call, $builtSk, $builtEn, $nextVersion): void {
                $meta = [
                    'titles' => array_filter([
                        'sk' => $builtSk['title'] ?? '',
                        'en' => $builtEn['title'] ?? '',
                    ], static fn ($v) => is_string($v) && $v !== ''),
                    'descriptions' => array_filter([
                        'sk' => $builtSk['description'] ?? '',
                        'en' => $builtEn['description'] ?? '',
                    ], static fn ($v) => is_string($v) && $v !== ''),
                ];

                $schema = FormSchema::query()->create([
                    'call_id' => $call->id,
                    'version' => $nextVersion,
                    'status' => 'published',
                    'title' => is_string($builtSk['title'] ?? null) ? $builtSk['title'] : null,
                    'description' => is_string($builtSk['description'] ?? null) ? $builtSk['description'] : null,
                    'sections' => isset($builtSk['sections']) && is_array($builtSk['sections']) ? $builtSk['sections'] : null,
                    'meta' => $meta,
                    'published_at' => now(),
                ]);

                foreach ($builtSk['fields'] as $i => $field) {
                    if (! is_array($field) || ! isset($field['name'], $field['type'])) {
                        continue;
                    }

                    FormField::query()->create([
                        'form_schema_id' => $schema->id,
                        'sort_order' => $i,
                        'name' => (string) $field['name'],
                        'type' => (string) $field['type'],
                        'config' => $field,
                    ]);
                }
            });

            $this->info("Materialized form_schema for call {$call->id} (version {$nextVersion}).");
        }

        return self::SUCCESS;
    }
}
