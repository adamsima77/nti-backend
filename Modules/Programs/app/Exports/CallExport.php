<?php

namespace Modules\Programs\Exports;

use Modules\Programs\Models\Call;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class CallExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query ?? Call::query()->select(['id', 'name', 'description', 'application_deadline', 'project_start', 'project_end']);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Názov',
            'Popis',
            'Deadline prihlášok',
            'Začiatok projektu',
            'Koniec projektu'
        ];
    }

    public function map($call): array
    {
        return [
            $call->id,
            $call->name,
            strip_tags(substr($call->description ?? '', 0, 200)),
            $call->application_deadline?->toDateTimeString(),
            $call->project_start?->toDateTimeString(),
            $call->project_end?->toDateTimeString(),
        ];
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
