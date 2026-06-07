<?php

namespace Modules\Reporting\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Applications\Models\Application;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApplicationExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    // ── Query ──────────────────────────────────────────────────────────────

    public function query()
    {
        $query = Application::query()
            ->with([
                'team',
                'call',
                'status',
                'mentorships.mentor',
                'creator',
            ]);

        if (! empty($this->filters['call_id'])) {
            $query->where('call_id', $this->filters['call_id']);
        }

        if (! empty($this->filters['active_status'])) {
            $query->where('active_status', $this->filters['active_status']);
        }

        if (! empty($this->filters['submitted_from'])) {
            $query->whereDate('submitted_at', '>=', $this->filters['submitted_from']);
        }

        if (! empty($this->filters['submitted_to'])) {
            $query->whereDate('submitted_at', '<=', $this->filters['submitted_to']);
        }

        return $query->orderByDesc('submitted_at');
    }

    // ── Headings ───────────────────────────────────────────────────────────

    public function headings(): array
    {
        return [
            'ID',
            'Referencia',
            'Tím',
            'Výzva',
            'Stav',
            'Mentor(i)',
            'Vytvorené používateľom',
            'Dátum podania',
            'Dátum vytvorenia',
        ];
    }

    // ── Row mapping ────────────────────────────────────────────────────────

    public function map($application): array
    {
        $mentors = $application->mentorships
            ->map(fn ($ms) => $ms->mentor
                ? trim("{$ms->mentor->name} {$ms->mentor->surname}")
                : null)
            ->filter()
            ->implode(', ');

        return [
            $application->id,
            $application->reference ?? '—',
            $application->team?->name ?? '—',
            $application->call?->name ?? '—',
            $application->status?->name ?? '—',
            $mentors ?: '—',
            $application->creator?->email ?? '—',
            $application->submitted_at?->format('d.m.Y H:i') ?? '—',
            $application->created_at?->format('d.m.Y H:i') ?? '—',
        ];
    }

    // ── Column styles ──────────────────────────────────────────────────────

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row: dark navy background, white bold text, centred
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A5F'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    // ── Sheet-level events ─────────────────────────────────────────────────

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $lastRow   = $sheet->getHighestRow();
                $lastCol   = 'I'; // column count matches headings()

                // Freeze the header row
                $sheet->freezePane('A2');

                // Thin border on the whole table
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(
                    Border::BORDER_THIN
                );

                // Zebra striping on data rows
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF0F4FA');
                    }
                }

                // Wrap text in the "Výzva" column (C) so long names don't overflow
                $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setWrapText(true);

                // Row height for header
                $sheet->getRowDimension(1)->setRowHeight(22);
            },
        ];
    }
}
