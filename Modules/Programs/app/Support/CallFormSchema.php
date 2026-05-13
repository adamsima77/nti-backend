<?php

namespace Modules\Programs\Support;

use Illuminate\Validation\ValidationException;
use Modules\Content\Models\Language;
use Modules\Programs\Models\Call;

class CallFormSchema
{
    /**
     * @return array{title: string, description?: string|null, fields: array<int, array<string, mixed>>, sections?: array<int, array<string, mixed>>|null}
     */
    public static function build(Call $call, Language $language, string $localeCode): array
    {
        $overlay = $call->application_form_schema;
        $hasOverlay = is_array($overlay) && $overlay !== [];

        $criteriaFields = self::criteriaFields($call, $language, $localeCode);
        $documentField = self::documentField($localeCode);

        if (! $hasOverlay) {
            return self::wrap(
                $localeCode === 'en' ? 'Application details' : 'Údaje prihlášky',
                null,
                array_merge($criteriaFields, [$documentField]),
                null
            );
        }

        $appendToCriteria = filter_var($overlay['appendToCriteria'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $customFields = isset($overlay['fields']) && is_array($overlay['fields'])
            ? self::normalizeCustomFields($overlay['fields'])
            : [];

        if ($appendToCriteria && $customFields !== []) {
            $fields = array_merge($criteriaFields, $customFields);
            $fields = self::ensureDocumentAttachmentField($fields, $documentField);
            $title = self::pickTitle($overlay, $localeCode);
            $description = is_string($overlay['description'] ?? null) ? $overlay['description'] : null;

            return self::wrap($title, $description, $fields, null);
        }

        if ($customFields !== []) {
            $fields = self::ensureDocumentAttachmentField($customFields, $documentField);
            $title = self::pickTitle($overlay, $localeCode);
            $description = is_string($overlay['description'] ?? null) ? $overlay['description'] : null;
            $sections = isset($overlay['sections']) && is_array($overlay['sections']) ? $overlay['sections'] : null;

            return self::wrap($title, $description, $fields, $sections);
        }

        return self::wrap(
            $localeCode === 'en' ? 'Application details' : 'Údaje prihlášky',
            null,
            array_merge($criteriaFields, [$documentField]),
            null
        );
    }

    /**
     * Validates incoming form_data keys against the published schema and returns string values for persistence.
     *
     * @param  array<string, mixed>  $formDataInput
     * @return array<string, string>
     */
    public static function normalizeStoredFormAnswers(Call $call, Language $language, string $localeCode, array $formDataInput): array
    {
        $schema = self::build($call, $language, $localeCode);
        $stored = [];

        foreach ($schema['fields'] as $field) {
            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);
            $raw = $formDataInput[$name] ?? null;

            if ($type === 'file') {
                $ids = self::parseDocumentIdsValue($raw);
                if ($required && $ids === []) {
                    throw ValidationException::withMessages([
                        'form_data.'.$name => [
                            $localeCode === 'en' ? 'Upload is required.' : 'Nahratie súboru je povinné.',
                        ],
                    ]);
                }
                $stored[$name] = $ids === [] ? '' : json_encode(array_values($ids), JSON_THROW_ON_ERROR);

                continue;
            }

            if ($type === 'checkbox') {
                $truth = in_array($raw, [true, 1, '1', 'true', 'yes', 'on'], true);
                if ($required && ! $truth) {
                    throw ValidationException::withMessages([
                        'form_data.'.$name => [
                            $localeCode === 'en' ? 'Confirmation is required.' : 'Potvrdenie je povinné.',
                        ],
                    ]);
                }
                $stored[$name] = $truth ? '1' : '0';

                continue;
            }

            if ($type === 'repeater') {
                $rows = self::decodeRepeaterRows($raw);
                if ($rows === null) {
                    throw ValidationException::withMessages([
                        'form_data.'.$name => [
                            $localeCode === 'en' ? 'Invalid repeater data.' : 'Neplatné dáta v opakovateľnej sekcii.',
                        ],
                    ]);
                }
                if ($required && $rows === []) {
                    throw ValidationException::withMessages([
                        'form_data.'.$name => [
                            $localeCode === 'en' ? 'Add at least one row.' : 'Pridajte aspoň jeden riadok.',
                        ],
                    ]);
                }
                $stored[$name] = json_encode($rows, JSON_THROW_ON_ERROR);

                continue;
            }

            $value = is_string($raw) ? trim($raw) : (is_null($raw) ? '' : (string) $raw);

            if ($required && $value === '') {
                throw ValidationException::withMessages([
                    'form_data.'.$name => [
                        $localeCode === 'en' ? 'This field is required.' : 'Toto pole je povinné.',
                    ],
                ]);
            }

            $maxLength = (int) ($field['maxLength'] ?? 20000);
            if ($maxLength > 0 && strlen($value) > $maxLength) {
                throw ValidationException::withMessages([
                    'form_data.'.$name => [
                        $localeCode === 'en'
                            ? "Maximum length is {$maxLength} characters."
                            : "Maximálna dĺžka je {$maxLength} znakov.",
                    ],
                ]);
            }

            $minLength = (int) ($field['minLength'] ?? 0);
            if ($minLength > 0 && strlen($value) < $minLength) {
                throw ValidationException::withMessages([
                    'form_data.'.$name => [
                        $localeCode === 'en'
                            ? "Minimum length is {$minLength} characters."
                            : "Minimálna dĺžka je {$minLength} znakov.",
                    ],
                ]);
            }

            $stored[$name] = $value;
        }

        return $stored;
    }

    /**
     * Union of all document IDs referenced by file-type fields in stored answers.
     *
     * @param  array<string, string>  $storedFormData
     * @return array<int, int>
     */
    public static function collectDocumentIdsFromStoredAnswers(array $storedFormData, Call $call, Language $language, string $localeCode): array
    {
        $schema = self::build($call, $language, $localeCode);
        $all = [];

        foreach ($schema['fields'] as $field) {
            if (($field['type'] ?? '') !== 'file') {
                continue;
            }
            $name = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $raw = $storedFormData[$name] ?? '';
            $all = array_merge($all, self::parseDocumentIdsValue($raw));
        }

        $all = array_values(array_unique(array_filter(array_map('intval', $all))));

        sort($all);

        return $all;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>|null  $sections
     * @return array{title: string, description?: string|null, fields: array<int, array<string, mixed>>, sections?: array<int, array<string, mixed>>|null}
     */
    private static function wrap(string $title, ?string $description, array $fields, ?array $sections): array
    {
        $out = [
            'title' => $title,
            'fields' => $fields,
        ];

        if ($description !== null && $description !== '') {
            $out['description'] = $description;
        }

        if ($sections !== null && $sections !== []) {
            $out['sections'] = $sections;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $overlay
     */
    private static function pickTitle(array $overlay, string $localeCode): string
    {
        $t = $overlay['title'] ?? null;

        return is_string($t) && $t !== ''
            ? $t
            : ($localeCode === 'en' ? 'Application details' : 'Údaje prihlášky');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function criteriaFields(Call $call, Language $language, string $localeCode): array
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

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    private static function documentField(string $localeCode): array
    {
        return [
            'name' => 'document_ids',
            'type' => 'file',
            'label' => $localeCode === 'en' ? 'Attachments (required)' : 'Prílohy (povinné)',
            'description' => $localeCode === 'en'
                ? 'Upload at least one file (e.g. PDF).'
                : 'Nahrajte aspoň jeden súbor (napr. PDF).',
            'required' => true,
            'allowMultiple' => true,
            'accept' => 'application/pdf,image/*,.doc,.docx',
            'documentUpload' => true,
        ];
    }

    /**
     * @param  array<int, mixed>  $fields
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeCustomFields(array $fields): array
    {
        $out = [];

        foreach ($fields as $f) {
            if (! is_array($f) || ! isset($f['name'], $f['type'])) {
                continue;
            }

            $row = $f;

            if (($row['type'] ?? '') === 'file') {
                $row['documentUpload'] = true;
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $documentField
     * @return array<int, array<string, mixed>>
     */
    private static function ensureDocumentAttachmentField(array $fields, array $documentField): array
    {
        foreach ($fields as $f) {
            if (($f['type'] ?? '') === 'file') {
                return $fields;
            }
        }

        $fields[] = $documentField;

        return $fields;
    }

    /**
     * @return array<int, int>
     */
    private static function parseDocumentIdsValue(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;

            return $id > 0 ? [$id] : [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return self::normalizeIdList($decoded);
            }

            if (ctype_digit($raw)) {
                $id = (int) $raw;

                return $id > 0 ? [$id] : [];
            }
        }

        if (is_array($raw)) {
            return self::normalizeIdList($raw);
        }

        return [];
    }

    /**
     * @param  array<int, mixed>  $list
     * @return array<int, int>
     */
    private static function normalizeIdList(array $list): array
    {
        $ids = [];

        foreach ($list as $x) {
            $n = (int) $x;

            if ($n > 0) {
                $ids[] = $n;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function decodeRepeaterRows(mixed $raw): ?array
    {
        if ($raw === null) {
            return [];
        }

        if (is_array($raw)) {
            return self::sanitizeRepeaterRows($raw);
        }

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        return self::sanitizeRepeaterRows($decoded);
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function sanitizeRepeaterRows(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $clean = [];

            foreach ($row as $k => $v) {
                if (! is_string($k) || $k === '') {
                    continue;
                }

                if (is_scalar($v) || $v === null) {
                    $clean[$k] = $v;
                }
            }

            $out[] = $clean;
        }

        return $out;
    }
}
