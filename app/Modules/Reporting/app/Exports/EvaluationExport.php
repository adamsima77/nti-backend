<?php

namespace Modules\Reporting\Exports;

use Modules\Evaluation\Models\EvaluationScore;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class EvaluationExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $query;
    protected $filters;

    public function __construct($query = null)
    {
        if (is_array($query)) {
            $this->filters = $query;
            $this->query = null;
        } else {
            $this->query = $query;
            $this->filters = null;
        }
    }

    public function query()
    {
        if ($this->query !== null) {
            return $this->query;
        }

        $query = EvaluationScore::query()
            ->with(['evaluation.application.call', 'evaluation.application.team', 'evaluation.commissionMember.user', 'criterion', 'evaluation.decision']);

        if (! empty($this->filters['call_id'])) {
            $query->whereHas('evaluation.application', function ($builder) {
                $builder->where('call_id', $this->filters['call_id']);
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Evaluation ID',
            'Application ID',
            'Evaluator Name',
            'Project Name',
            'Team Name',
            'Criterion Name',
            'Score',
            'Max Score',
            'Recommendation',
            'Submitted At',
        ];
    }

    public function map($scoreRow): array
    {
        $evaluation = $scoreRow->evaluation;
        $application = $evaluation->application;
        $evaluatorName = optional($evaluation->commissionMember->user)->email
            ?? optional($evaluation->commissionMember->user)->name
            ?? null;
        $recommendation = $this->normalizeRecommendation(optional($evaluation->decision)->name);
        $submittedAt = optional($evaluation->submitted_at?->toDateTimeString());
        $maxScore = 20;

        return [
            $evaluation->id,
            $evaluation->application_id,
            $evaluatorName,
            optional($application->call)->name,
            optional($application->team)->name,
            optional($scoreRow->criterion)->name,
            $scoreRow->score !== null ? (float) $scoreRow->score : null,
            $maxScore,
            $recommendation,
            $submittedAt,
        ];
    }

    protected function normalizeRecommendation(?string $decisionName): string
    {
        if ($decisionName === null) {
            return 'supplement';
        }

        $normalized = mb_strtolower(trim($decisionName));

        return match (true) {
            str_contains($normalized, 'schválen') || str_contains($normalized, 'approved') => 'approve',
            str_contains($normalized, 'zamiet') || str_contains($normalized, 'rejected') => 'reject',
            default => 'supplement',
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:J1')->getFont()->setBold(true);
            },
        ];
    }
}
