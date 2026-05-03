<?php

namespace Modules\Programs\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallResource extends JsonResource
{
    private function getTranslation(Request $request)
    {
        $locale = $request->header('X-Locale')
            ?? $request->query('lang')
            ?? app()->getLocale();

        return $this->callTranslations
            ?->firstWhere('language.code', $locale);
    }

    public function toArray(Request $request): array
    {
        $currentStatus = $this->currentStatusHistory?->status;
        $translation = $this->getTranslation($request);

        return [
            'id' => $this->id,

            // multilingual fields
            'name' => $translation?->name ?? $this->name,
            'description' => $translation?->description ?? $this->description,

            'application_start'    => $this->application_start,
            'application_deadline' => $this->application_deadline,

            'project_start' => $this->project_start,
            'project_end'   => $this->project_end,

            'is_open' => $this->application_deadline
                ? now()->lt($this->application_deadline)
                : false,

            'applicants_count' => $this->applications_count ?? 0,

            'status' => [
                'id'   => $currentStatus?->id,
                'name' => $currentStatus?->name,
            ],

            'program' => [
                'id'   => $this->program?->id,
                'name' => $this->program?->typeOfProgram?->name,
            ],

            'organization' => [
                'id'   => $this->organization?->id,
                'name' => $this->organization?->name,
            ],

            'call_criteria' => collect($this->callCriteria)
                ->map(fn ($criterion) => [
                    'id'   => $criterion->id,
                    'name' => $criterion->name,
                ])
                ->values(),
        ];
    }
}
