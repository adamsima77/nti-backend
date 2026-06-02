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

        $query = User::query()
            ->with('status')
            ->select(['id', 'name', 'surname', 'email', 'status_id', 'created_at']);

        if (! empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('surname', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        if (! empty($this->filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $this->filters['role']));
        }

        if (! empty($this->filters['status'])) {
            $query->where('status_id', $this->filters['status']);
        }

        return $query;
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
