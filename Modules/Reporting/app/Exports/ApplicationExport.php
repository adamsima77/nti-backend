<?php

namespace Modules\Reporting\Exports;

use Modules\Applications\Models\Application;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ApplicationExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
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

        $query = Application::query()->select(['id', 'call_id', 'team_id', 'created_by', 'submitted_at']);

        if (! empty($this->filters['call_id'])) {
            $query->where('call_id', $this->filters['call_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Call ID',
            'Team ID',
            'Vytvorené používateľom',
            'Podané'
        ];
    }

    public function map($application): array
    {
        return [
            $application->id,
            $application->call_id,
            $application->team_id,
            optional($application->creator)->email ?? $application->created_by,
            $application->submitted_at?->toDateTimeString(),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:E1')->getFont()->setBold(true);
            },
        ];
    }
}
