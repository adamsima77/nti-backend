<?php

namespace Modules\Reporting\Exports;

use Modules\Programs\Models\Call;
use Modules\Reporting\Support\CallReportLabels;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class CallExport implements FromArray, ShouldAutoSize, WithEvents
{
    protected ?array $filters;

    public function __construct(?array $filters = null)
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        $callId = $this->filters['call_id'] ?? null;

        if (!empty($callId)) {
            return $this->reportRows((int) $callId, $this->filters['lang'] ?? 'sk');
        }

        return $this->listRows();
    }

    private function listRows(): array
    {
        $query        = Call::query();
        $status       = $this->filters['status'] ?? request('status');
        $deadlineFrom = $this->filters['deadline_from'] ?? request('deadline_from');
        $deadlineTo   = $this->filters['deadline_to'] ?? request('deadline_to');

        if (!empty($status)) {
            $mainTable = $query->getModel()->getTable();
            $query->whereHas('statusHistory', function ($q) use ($status, $mainTable) {
                $q->whereHas('status', fn ($sq) => $sq->where('name', $status))
                  ->where('id', fn ($sub) => $sub->select('id')
                      ->from('status_of_call_has_call')
                      ->whereColumn('call_id', $mainTable . '.id')
                      ->latest()
                      ->limit(1));
            });
        }

        if (!empty($deadlineFrom)) {
            $query->whereDate('application_deadline', '>=', $deadlineFrom);
        }

        if (!empty($deadlineTo)) {
            $query->whereDate('application_deadline', '<=', $deadlineTo);
        }

        $rows = [['ID', 'Názov', 'Popis', 'Deadline prihlášok', 'Začiatok projektu', 'Koniec projektu']];

        foreach ($query->get() as $call) {
            $rows[] = [
                $call->id,
                $call->name,
                strip_tags(substr($call->description ?? '', 0, 150)),
                $call->application_deadline?->format('d.m.Y H:i') ?? '—',
                $call->project_start?->format('d.m.Y') ?? '—',
                $call->project_end?->format('d.m.Y') ?? '—',
            ];
        }

        return $rows;
    }

    private function reportRows(int $callId, string $lang = 'sk'): array
    {
        $call = Call::with(CallReportLabels::relations())->findOrFail($callId);

        $tr = $call->callTranslations->firstWhere('language.name', $lang)
            ?? $call->callTranslations->first();
        $callName        = $tr->name        ?? $call->name        ?? '-';
        $callDescription = $tr->description ?? $call->description ?? '-';

        $l = CallReportLabels::get($lang);

        $application = $call->applications->first();
        $rows        = [];

        $section = fn (string $title) => [[$title, ''], ['', '']];
        $kv      = fn (string $k, $v)  => [$k, (string) $v];
        $blank   = ['', ''];

        array_push($rows, ...$section($l['basic_info']));
        $rows[] = $kv($l['call_name'],     $callName);
        $rows[] = $kv($l['program'],       $call->program->typeOfProgram->name ?? '-');
        $rows[] = $kv($l['partner'],       $call->organization->name ?? '-');
        $rows[] = $kv($l['product_owner'], $call->productOwner
            ? $call->productOwner->name . ' ' . $call->productOwner->surname . ' (' . $call->productOwner->email . ')'
            : '-');
        $rows[] = $kv($l['deadline'],      $call->application_deadline?->format('d.m.Y') ?? '-');
        $rows[] = $kv($l['project_start'], $call->project_start?->format('d.m.Y') ?? '-');
        $rows[] = $kv($l['project_end'],   $call->project_end?->format('d.m.Y') ?? '-');
        $rows[] = $kv($l['budget'],        $call->budget ? number_format($call->budget, 2, ',', ' ') . ' €' : '-');
        $rows[] = $kv($l['status'],        $call->currentStatusHistory?->status?->name ?? '-');
        $rows[] = $blank;

        array_push($rows, ...$section($l['description']));
        $rows[] = [strip_tags($callDescription), ''];
        $rows[] = $blank;

        if ($application) {
            array_push($rows, ...$section($l['team']));
            if ($application->team) {
                $rows[] = $kv($l['team_name'], $application->team->name);
                $rows[] = $blank;
                $rows[] = ['#', $l['name'], $l['email']];
                foreach ($application->team->members as $i => $m) {
                    $rows[] = [$i + 1, $m->name . ' ' . $m->surname, $m->email];
                }
            } else {
                $rows[] = [$l['no_team'], ''];
            }
            $rows[] = $blank;

            array_push($rows, ...$section($l['mentor']));
            if ($application->mentorships->isNotEmpty()) {
                $rows[] = [$l['name'], $l['email']];
                foreach ($application->mentorships as $ms) {
                    $rows[] = [$ms->mentor->name . ' ' . $ms->mentor->surname, $ms->mentor->email];
                }
            } else {
                $rows[] = [$l['no_mentor'], ''];
            }
            $rows[] = $blank;

            array_push($rows, ...$section($l['commission']));
            $commission = $call->commission->first();
            if ($commission) {
                $rows[] = $kv($l['commission_name'], $commission->name);
                $evaluators = $commission->members->filter(fn ($m) => $m->call_id === null);
                if ($evaluators->isNotEmpty()) {
                    $rows[] = $blank;
                    $rows[] = ['#', $l['name'], $l['email']];
                    foreach ($evaluators->values() as $i => $m) {
                        $rows[] = [$i + 1, $m->user->name . ' ' . $m->user->surname, $m->user->email];
                    }
                }
                $rep = $call->commissionCompanyRep;
                if ($rep?->user) {
                    $rows[] = $blank;
                    $rows[] = $kv($l['company_rep'], $rep->user->name . ' ' . $rep->user->surname . ' (' . $rep->user->email . ')');
                }
            } else {
                $rows[] = [$l['no_commission'], ''];
            }
            $rows[] = $blank;

            array_push($rows, ...$section($l['kpi']));
            if ($application->kpis->isNotEmpty()) {
                $rows[] = [$l['metric'], $l['target'], $l['actual'], $l['achievement']];
                foreach ($application->kpis as $kpi) {
                    $pct    = $kpi->achievement_percentage;
                    $rows[] = [
                        $kpi->metric_name . ($kpi->unit ? ' (' . $kpi->unit . ')' : ''),
                        $kpi->target_value ?? '-',
                        $kpi->actual_value ?? '-',
                        $pct !== null ? number_format($pct, 1) . ' %' : '-',
                    ];
                }
            } else {
                $rows[] = [$l['no_kpi'], ''];
            }
            $rows[] = $blank;

            array_push($rows, ...$section($l['milestones']));
            if ($application->milestones->isNotEmpty()) {
                $rows[] = ['#', $l['milestone_name'], $l['deadline_col']];
                foreach ($application->milestones->sortBy('deadline')->values() as $i => $ms) {
                    $rows[] = [$i + 1, $ms->name, $ms->deadline?->format('d.m.Y') ?? '-'];
                }
            } else {
                $rows[] = [$l['no_milestones'], ''];
            }
            $rows[] = $blank;
        } else {
            $rows[] = [$l['no_application'], ''];
            $rows[] = $blank;
        }

        array_push($rows, ...$section($l['criteria']));
        if ($call->callCriteria->isNotEmpty()) {
            $rows[] = ['#', $l['criterion'], $l['weight']];
            foreach ($call->callCriteria as $i => $c) {
                $rows[] = [$i + 1, $c->name, $c->pivot->weight ?? '-'];
            }
        } else {
            $rows[] = [$l['no_criteria'], ''];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1')->getFont()->setBold(true);
            },
        ];
    }
}
