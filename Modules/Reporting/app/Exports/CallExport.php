<?php

namespace Modules\Reporting\Exports;

use Modules\Programs\Models\Call;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class CallExport implements FromView, ShouldAutoSize, WithEvents
{
    protected ?array $filters;

    public function __construct(?array $filters = null)
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Call::query();
        $status       = $this->filters['status'] ?? request('status');
        $deadlineFrom = $this->filters['deadline_from'] ?? request('deadline_from');
        $deadlineTo   = $this->filters['deadline_to'] ?? request('deadline_to');

        $callId = $this->filters['call_id'] ?? null;
        if (!empty($callId)) {
            $query->where('id', $callId);
        }

        if (!empty($status)) {
            $mainTable = $query->getModel()->getTable();

            $query->whereHas('statusHistory', function ($q) use ($status, $mainTable) {
                $q->whereHas('status', function ($statusQuery) use ($status) {
                    $statusQuery->where('name', $status);
                })
                    ->where('id', function ($subQuery) use ($mainTable) {
                        $subQuery->select('id')
                            ->from('status_of_call_has_call')
                            ->whereColumn('call_id', $mainTable . '.id')
                            ->latest()
                            ->limit(1);
                    });
            });
        }

        if (!empty($deadlineFrom)) {
            $query->whereDate('application_deadline', '>=', $deadlineFrom);
        }

        if (!empty($deadlineTo)) {
            $query->whereDate('application_deadline', '<=', $deadlineTo);
        }

        return view('reporting::calls_export', [
            'calls' => $query->get(),
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:F1')->getFont()->setBold(true);
            },
        ];
    }
}
