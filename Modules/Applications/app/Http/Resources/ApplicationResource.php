<?php

namespace Modules\Applications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Content\Enums\LanguageType;
use Modules\Mentorship\Models\Milestone;

class ApplicationResource extends JsonResource
{
    /**
     * Map backend milestone / status strings to frontend-friendly slugs.
     */
    protected static function milestoneUiStatus(?string $status): string
    {
        $s = mb_strtolower((string) $status);

        if (str_contains($s, 'complete') || str_contains($s, 'dokon')) {
            return 'completed';
        }
        if (str_contains($s, 'progress') || str_contains($s, 'prebie') || str_contains($s, 'aktu')) {
            return 'in_progress';
        }

        return 'pending';
    }

    private function languageId(Request $request): int
    {
        $locale = strtolower((string) $request->header('X-Locale', 'sk'));
        return $locale === 'en' ? LanguageType::ENGLISH->value : LanguageType::SLOVAK->value;
    }

    private function categoryName(Request $request): ?string
    {
        if (! $this->category) {
            return null;
        }

        return $this->category->categoryTranslations
            ->firstWhere('language_id', $this->languageId($request))?->name
            ?? $this->category->categoryTranslations->first()?->name
            ?? $this->category->slug;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'submitted_at'   => $this->submitted_at,
            'last_update'    => $this->last_update,
            'academic_flag'  => $this->academic_flag,
            'reference' => $this->reference,
            'team_members' => $this->whenLoaded('team', function () {
                return $this->team?->members->map(function ($member) {
                    return [
                        'user_id'   => $member->id,
                        'name'      => $member->name,
                        'surname'   => $member->surname,
                        'role_id'   => $member->pivot?->team_role_id,
                        'role_name' => match($member->pivot?->team_role_id) {
                            1 => 'Vedúci tímu',
                            2 => 'Člen',
                            default => null,
                        },
                        'student'   => $member->student ? [
                            'id'             => $member->student->id,
                            'academic_flags' => $member->student->academicFlags,
                        ] : null,
                    ];
                });
            }),
            'call'           => [
                'id'   => $this->call?->id,
                'name' => $this->call?->name,
            ],
            'call_id'        => $this->call_id,
            'team_id'        => $this->team_id,
            'team'           => $this->whenLoaded('team', function () {
                return [
                    'id'   => $this->team?->id,
                    'name' => $this->team?->name,
                ];
            }),
            'created_by'     => $this->created_by,
            'form_data'      => $this->form_data,
            'status'         => [
                'id'   => $this->status?->id,
                'name' => $this->status?->name,
            ],
            'documents'      => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    $latest = $document->relationLoaded('versions')
                        ? $document->versions->sortByDesc('id')->first()
                        : $document->versions()->orderByDesc('id')->first();

                    return [
                        'id'          => $document->id,
                        'name'        => $latest?->file_name ?? ('Dokument #'.$document->id),
                        'uploaded_at' => $latest?->created_at ?? $latest?->updated_at,
                    ];
                })->values();
            }),
            'status_history' => $this->whenLoaded('statusHistory', function () {
                return $this->statusHistory
                    ->map(function ($history) {
                        return [
                            'id'         => $history->id,
                            'status'     => [
                                'id'   => $history->status?->id,
                                'name' => $history->status?->name,
                            ],
                            'note'       => $history->note,
                            'created_at' => $history->created_at,
                        ];
                    })
                    ->values();
            }),
            'category'       => $this->whenLoaded('category', function () use ($request) {
                return [
                    'id' => $this->category?->id,
                    'name' => $this->categoryName($request),
                ];
            }),
            'milestones'     => $this->whenLoaded('milestones', function () {
                return $this->milestones->map(function (Milestone $milestone) {
                    $ui = self::milestoneUiStatus($milestone->status);

                    return [
                        'id'           => $milestone->id,
                        'title'        => $milestone->name,
                        'due_date'     => $milestone->deadline?->format('Y-m-d'),
                        'status'       => $ui,
                        'description'  => $milestone->comments,
                        'completed_at' => $ui === 'completed' ? $milestone->updated_at?->format('Y-m-d') : null,
                    ];
                })->values();
            }),
            // OPRAVA: Pridané mapovanie mentorships pre frontend vrátane detailov priradeného mentora
            'mentorships'    => $this->whenLoaded('mentorships', function () {
                return $this->mentorships->map(function ($mentorship) {
                    return [
                        'id'     => $mentorship->id,
                        'mentor' => $mentorship->mentor ? [
                            'id'      => $mentorship->mentor->id,
                            'name'    => $mentorship->mentor->name,
                            'surname' => $mentorship->mentor->surname,
                        ] : null,

                        'commission' => $this->whenLoaded('evaluations', function () {
                            // Vytiahneme prvé hodnotenie, z neho člena a z neho komisiu
                            $firstEvaluation = $this->evaluations->first();
                            $commission = $firstEvaluation?->commissionMember?->commission;

                            if (!$commission) {
                                return null;
                            }

                            return [
                                'id'   => $commission->id,
                                'name' => $commission->name,
                                // Zoberieme všetky načítané evaluácie a spravíme z nich zoznam členov pre frontend
                                'members' => $this->evaluations->map(function ($evaluation) {
                                    $member = $evaluation->commissionMember;
                                    return [
                                        'id'           => $member?->id,
                                        'user_id'      => $member?->user_id,
                                        'name'         => $member?->user?->name,
                                        'surname'      => $member?->user?->surname,
                                        'submitted_at' => $evaluation->submitted_at, // Frontend hneď vidí status
                                    ];
                                }),
                            ];
                        }),
                    ];
                })->values();
            }),
        ];
    }
}
