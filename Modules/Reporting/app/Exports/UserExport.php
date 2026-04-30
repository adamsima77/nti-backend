<?php

namespace Modules\Reporting\Exports;

use Modules\IdentityAccess\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class UserExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query ?? User::query()->select(['id', 'name', 'surname', 'email', 'status_id', 'created_at']);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Meno',
            'Priezvisko',
            'Email',
            'Status',
            'Vytvorené'
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->surname,
            $user->email,
            optional($user->status)->name,
            $user->created_at?->toDateTimeString(),
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
