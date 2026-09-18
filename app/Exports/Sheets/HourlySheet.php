<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

class HourlySheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithColumnWidths, WithCustomStartCell
{
    protected int $rowNumber = 0;

    public function __construct(protected array $ctx) {}

    public function title(): string { return 'Rekap Per Jam'; }
    public function startCell(): string { return 'A6'; }

    public function collection()
    {
        return $this->ctx['hourly'];
    }

    public function headings(): array
    {
        return ['NO', 'PERIODE', 'AVG TEMP (°C)', 'MAX TEMP (°C)', 'AVG HUM (%)',
                'AVG SMOKE', 'MAX SMOKE', 'FLAME EVT', 'CRITICAL EVT', 'STATUS'];
    }

    public function columnWidths(): array
    {
        return ['A' => 5, 'B' => 22, 'C' => 14, 'D' => 14, 'E' => 12,
                'F' => 12, 'G' => 12, 'H' => 12, 'I' => 14, 'J' => 14];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $maxTemp = (float) ($row->max_temp ?? 0);
        $maxSmoke = (int) ($row->max_smoke ?? 0);
        $critical = (int) ($row->critical_count ?? 0);
        $flame = (int) ($row->flame_count ?? 0);

        $status = $critical > 0 ? 'CRITICAL'
            : ($maxTemp > $this->ctx['tLimit'] * 0.9 || $maxSmoke > $this->ctx['sLimit'] * 0.8
                ? 'WARNING' : 'STABLE');

        return [
            $this->rowNumber,
            $row->label,
            number_format((float) $row->avg_temp, 1),
            number_format($maxTemp, 1),
            number_format((float) $row->avg_hum, 1),
            (int) round((float) $row->avg_smoke),
            $maxSmoke,
            $flame,
            $critical,
            $status,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $c = $this->ctx;

                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'NOC MONITORING · REKAP PER PERIODE');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF004D60');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->mergeCells('A2:J2');
                $sheet->setCellValue('A2', strtoupper($c['periode'] ?? '-') . ' · Grouping: ' . ($c['groupLabel'] ?? '-'));
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF666666');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Heading style
                $sheet->getStyle('A6:J6')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A6:J6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF002D33');
                $sheet->getStyle('A6:J6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension(6)->setRowHeight(20);

                $lastRow = $sheet->getHighestRow();
                if ($lastRow < 7) return;

                $range = 'A7:J' . $lastRow;
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('FFDDDDDD');

                // Conditional STATUS
                foreach ([
                    ['"CRITICAL"', 'FFC62828', 'FFFFEBEE'],
                    ['"WARNING"',  'FFEF6C00', 'FFFFF3E0'],
                    ['"STABLE"',   'FF2E7D32', null],
                ] as [$val, $fg, $bg]) {
                    $cond = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
                    $cond->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
                        ->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
                        ->addCondition($val);
                    $cond->getStyle()->getFont()->setBold(true)->getColor()->setARGB($fg);
                    if ($bg) {
                        $cond->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                    }
                    $existing = $sheet->getStyle('J7:J' . $lastRow)->getConditionalStyles();
                    $existing[] = $cond;
                    $sheet->getStyle('J7:J' . $lastRow)->setConditionalStyles($existing);
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter('A6:J6');
                $sheet->getHeaderFooter()->setOddFooter('&L' . ($c['periode'] ?? '') . '&R&Phal / &N');
            },
        ];
    }
}
