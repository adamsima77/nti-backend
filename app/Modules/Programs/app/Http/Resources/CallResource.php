<?php

namespace Modules\Programs\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Content\Models\Language;
use Modules\Programs\Support\CallFormSchema;

class CallResource extends JsonResource
{
    private function resolveLocaleCode(Request $request): string
    {
        $locale = $request->header('X-Locale')
            ?? $request->query('lang')
            ?? app()->getLocale();

        return in_array($locale, ['sk', 'en'], true) ? $locale : 'sk';
    }

    private function getTranslation(Request $request)
    {
        $locale = $this->resolveLocaleCode($request);

        return $this->callTranslations
            ?->firstWhere('language.name', $locale);
    }

    public function toArray(Request $request): array
    {
        $currentStatus = $this->currentStatusHistory?->status;
        $translation   = $this->getTranslation($request);
        $localeCode    = $this->resolveLocaleCode($request);
        $language      = Language::query()->where('name', $localeCode)->first();

        $criteria = collect($this->callCriteria)->map(function ($criterion) use ($language) {
            $name = $criterion->name;
            if ($language) {
                $tr   = $criterion->criterionTranslations
                    ?->firstWhere('language_id', $language->id);
                $name = $tr?->name ?? $criterion->name;
            }

            return [
                'id'   => $criterion->id,
                'name' => $name,
                'pivot' => [
                    'weight'             => $criterion->pivot?->weight ?? 1,
                    'is_academic_signal' => (bool) ($criterion->pivot?->is_academic_signal ?? false),
                ],
            ];
        })->values();

        $formSchema = $language
            ? CallFormSchema::build($this->resource, $language, $localeCode)
            : ['title' => '', 'fields' => []];

        return [
            'id' => $this->id,

            'name'        => $translation?->name        ?? $this->name,
            'description' => $translation?->description ?? $this->description,

            'budget'      => $this->budget ? (float) $this->budget : null,
            'budget_type' => $this->budget_type,
            'tech_spec'   => $this->tech_spec,
            'tech_tags'   => $this->tech_tags ?? [],
            'po_user_id'  => $this->po_user_id,
            'product_owner' => [
                'id'    => $this->productOwner?->id,
                'name'  => $this->productOwner?->name,
                'email' => $this->productOwner?->email,
            ],

            'application_start'    => $this->application_start,
            'application_deadline' => $this->application_deadline,

            'project_start' => $this->project_start,
            'project_end'   => $this->project_end,

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            // is_open respects both the deadline AND the manual override.
            // Admin can force-close even before the deadline, or re-open by
            // toggling force_closed back to false (if deadline still in future).
            'force_closed' => (bool) $this->force_closed,
            'is_open'      => !$this->force_closed && (
                $this->application_deadline
                    ? now()->lt($this->application_deadline)
                    : false
                ),

            'applicants_count' => $this->applications_count ?? 0,

            'assigned_team' => $this->whenLoaded('applications', function () {
                $activeStatuses = ['Onboarding', 'Aktívny projekt', 'Ukončené', 'Schválené'];
                $app = $this->applications->first(fn ($a) => in_array($a->status?->name, $activeStatuses))
                    ?? $this->applications->first();
                if (!$app?->team) return null;
                return [
                    'id'           => $app->team->id,
                    'name'         => $app->team->name,
                    'members_count' => $app->team->members->count(),
                ];
            }),

            'status' => [
                'id'   => $currentStatus?->id,
                'name' => $currentStatus?->name,
            ],

            'program' => [
                'id'   => $this->program?->id,
                'name' => $this->program?->typeOfProgram?->name,
            ],

            'call_type' => [
                'id'   => $this->callType?->id,
                'name' => $this->callType?->name,
            ],

            'organization' => [
                'id'   => $this->organization?->id,
                'name' => $this->organization?->name,
            ],

            'call_criteria' => $criteria,

            'form_schema' => $formSchema,

            'applications' => $this->whenLoaded('applications', function () {
                return $this->applications->map(function ($application) {
                    return [
                        'id'          => $application->id,
                        'teamName'    => $application->team?->name,
                        'submittedAt' => $application->submitted_at,
                        'status'      => $application->status?->name,
                        'summary'     => null,
                    ];
                })->values();
            }),
        ];
    }
}
