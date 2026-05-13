<?php

namespace Modules\Programs\Support;

use Modules\Content\Models\Language;
use Modules\Programs\Models\Call;

class CallFormSchema
{
    /**
     * @return array{title: string, description?: string, fields: array<int, array<string, mixed>>}
     */
    public static function build(Call $call, Language $language, string $localeCode): array
    {
        $fields = [];

        foreach ($call->callCriteria as $criterion) {
            $translation = $criterion->criterionTranslations
                ->firstWhere('language_id', $language->id);
            $label = $translation?->name ?? $criterion->name ?? ('Criterion #'.$criterion->id);

            $fields[] = [
                'name' => 'criterion_'.$criterion->id,
                'type' => 'textarea',
                'label' => $label,
                'required' => true,
                'minLength' => 1,
                'placeholder' => $localeCode === 'en'
                    ? 'Describe how your project meets this criterion…'
                    : 'Popíšte, ako váš projekt spĺňa toto kritérium…',
            ];
        }

        $fields[] = [
            'name' => 'document_ids',
            'type' => 'file',
            'label' => $localeCode === 'en' ? 'Attachments (required)' : 'Prílohy (povinné)',
            'description' => $localeCode === 'en'
                ? 'Upload at least one file (e.g. PDF).'
                : 'Nahrajte aspoň jeden súbor (napr. PDF).',
            'required' => true,
            'allowMultiple' => true,
            'accept' => 'application/pdf,image/*,.doc,.docx',
        ];

        $title = $localeCode === 'en' ? 'Application details' : 'Údaje prihlášky';

        return [
            'title' => $title,
            'fields' => $fields,
        ];
    }
}
