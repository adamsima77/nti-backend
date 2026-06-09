<?php

namespace Modules\Applications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;

class ApplicationResource extends JsonResource
{
    /**
     * Map backend milestone / status strings to frontend-friendly slugs.
     */
    protected static function milestoneUiStatus(?string $status): string
    {
        $s = mb_strtolower((string)$status);

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
        $locale = strtolower((string)$request->header('X-Locale', 'sk'));
        return $locale === 'en' ? LanguageType::ENGLISH->value : LanguageType::SLOVAK->value;
    }

    private function categoryName(Request $request): ?string
    {
        if (!$this->category) {
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
        $user = $request->user();

        // Overenie roly (upravte podľa vášho systému, napr. $user?->hasRole('admin'))
        $isAdmin = $user && ($user->isAdmin() || $user->isSuperadmin());

        return [
            'id' => $this->id,
            'submitted_at' => $this->submitted_at,
            'last_update' => $this->last_update,
            'academic_flag' => $this->academic_flag,
            'reference' => $this->reference,

            'team_members' => $this->whenLoaded('team', function () {
                return $this->team?->members->map(function ($member) {
                    return [
                        'user_id' => $member->id,
                        'name' => $member->name,
                        'surname' => $member->surname,
                        'role_id' => $member->pivot?->team_role_id,
                        'role_name' => $member->pivot?->role?->name ?? 'Člen tímu',
                        'student' => $member->student ? [
                            'id' => $member->student->id,
                            'academic_flags' => $member->student->academicFlags,
                        ] : null,
                    ];
                });
            }),

            'call' => [
                'id' => $this->call?->id,
                'name' => $this->call?->name,
            ],
            'call_id' => $this->call_id,
            'team_id' => $this->team_id,
            'team' => $this->whenLoaded('team', function () {
                return [
                    'id' => $this->team?->id,
                    'name' => $this->team?->name,
                ];
            }),
            'created_by' => $this->created_by,
            'form_data' => $this->form_data,

            'status' => [
                'id' => $this->status?->id,
                'name' => $this->status?->name,
            ],

            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    $latest = $document->relationLoaded('versions')
                        ? $document->versions->sortByDesc('id')->first()
                        : $document->versions()->orderByDesc('id')->first();

                    return [
                        'id' => $document->id,
                        'name' => $latest?->file_name ?? ('Dokument #' . $document->id),
                        'uploaded_at' => $latest?->created_at ?? $latest?->updated_at,
                    ];
                })->values();
            }),

            'status_history' => $this->whenLoaded('statusHistory', function () {
                $userIds = $this->statusHistory->pluck('changed_by')->unique();
                $users = User::whereIn('id', $userIds)->get()->keyBy('id');

                return $this->statusHistory
                    ->sortBy('created_at')
                    ->map(function ($history) use ($users) {
                        $user = $users->get($history->changed_by);
                        return [
                            'id' => $history->id,
                            'status' => [
                                'id' => $history->status?->id,
                                'name' => $history->status?->name,
                            ],
                            'note' => $history->note,
                            'created_at' => $history->created_at,
                            'changed_by' => [
                                'id' => $history->changed_by,
                                'name' => $user ? $user->name . ' ' . $user->surname : 'Systém',
                                'updated_at' => $history->updated_at,
                            ],
                        ];
                    })
                    ->values();
            }),

            'category' => $this->whenLoaded('category', function () use ($request) {
                return [
                    'id' => $this->category?->id,
                    'name' => $this->categoryName($request),
                ];
            }),

            'milestones' => $this->whenLoaded('milestones', function () {
                return $this->milestones->map(function (Milestone $milestone) {
                    $ui = self::milestoneUiStatus($milestone->status);

                    return [
                        'id' => $milestone->id,
                        'title' => $milestone->name,
                        'due_date' => $milestone->deadline?->format('Y-m-d'),
                        'status' => $ui,
                        'description' => $milestone->comments,
                        'completed_at' => $ui === 'completed'
                            ? $milestone->updated_at?->format('Y-m-d')
                            : null,
                    ];
                })->values();
            }),

            'mentorships' => $this->whenLoaded('mentorships', function () {
                return $this->mentorships->map(function ($mentorship) {
                    return [
                        'id' => $mentorship->id,
                        'mentor' => $mentorship->mentor ? [
                            'id' => $mentorship->mentor->id,
                            'name' => $mentorship->mentor->name,
                            'surname' => $mentorship->mentor->surname,
                        ] : null,
                    ];
                })->values();
            }),

            // --- PODMIENENÉ ZlÚČENIE: Spustí sa iba ak JE používateľ Admin a ZÁROVEŇ sú načítané evaluations ---
            $this->mergeWhen($isAdmin && $this->relationLoaded('evaluations'), function () {
                $byCommission = $this->evaluations->groupBy(
                    fn($e) => $e->commissionMember?->commission_id ?? 0
                );

                return [
                    'evaluations' => $byCommission->map(function ($evaluations) {
                        $commission = $evaluations->first()?->commissionMember?->commission;

                        return [
                            'commission' => $commission ? [
                                'id' => $commission->id,
                                'name' => $commission->name,
                            ] : null,
                            'members' => $evaluations->map(function ($evaluation) {
                                $member = $evaluation->commissionMember;

                                return [
                                    'id' => $member?->id,
                                    'user_id' => $member?->user_id,
                                    'name' => $member?->user?->name,
                                    'surname' => $member?->user?->surname,
                                    'internal_note' => $evaluation->internal_note,
                                    'submitted_at' => $evaluation->submitted_at,
                                    'scores' => $evaluation->relationLoaded('scores')
                                        ? $evaluation->scores->map(fn($score) => [
                                            'criterion_id' => $score->criterion_id,
                                            'criterion_name' => $score->criterion?->name,
                                            'score' => $score->score,
                                            'comment' => $score->comment,
                                        ])->values()
                                        : [],
                                ];
                            })->values(),
                        ];
                    })->values()
                ];
            }),
        ];
    }
}
