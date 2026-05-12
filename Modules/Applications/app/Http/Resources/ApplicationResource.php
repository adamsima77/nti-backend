<?php

namespace Modules\Applications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'submitted_at'   => $this->submitted_at,
            'last_update'    => $this->last_update,
            'call'           => [
                'id'   => $this->call?->id,
                'name' => $this->call?->name,
            ],
            'team_id'        => $this->team_id,
            'team'           => $this->whenLoaded('team', function () {
                return [
                    'id'   => $this->team?->id,
                    'name' => $this->team?->name,
                ];
            }),
            'created_by'     => $this->created_by,
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
        ];
    }
}
