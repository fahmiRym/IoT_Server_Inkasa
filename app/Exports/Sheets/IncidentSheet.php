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

class IncidentSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithColumnWidths, WithCustomStartCell
{
    protected int $rowNumber = 0;

    public function __construct(protected array $ctx) {}

    public function title(): string { return 'Log Insiden'; }
    public function startCell(): string { return 'A6'; }

    public function collection()
    {
        return $this->ctx['incidents'];
    }

    public function headings(): array
    {
        return ['NO', 'TIMESTAMP', 'TEMP (°C)', 'SMOKE (PPM)', 'FLAME', 'PENYEBAB', 'KEPARAHAN'];
    }

    public function columnWidths(): array
    {
        return ['A' => 5, 'B' => 22, 'C' => 12, 'D' => 14, 'E' => 10, 'F' => 40, 'G' => 14];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $temp = (float) $row->temp;
        $smoke = (int) $row->smoke;
        $fire = (int) $row->fire === 1;

        $reasons = [];
        if ($temp > $this->ctx['tLimit']) $reasons[] = sprintf('Suhu %.1f°C > %s°C', $temp, $this->ctx['tLimit']);
        if ($smoke > $this->ctx['sLimit']) $reasons[] = sprintf('Asap %d PPM > %s PPM', $smoke, $this->ctx['sLimit']);
        if ($fire) $reasons[] = 'Flame terdeteksi';

        $severity = $fire ? 'DANGER'
            : ($temp > $this->ctx['tLimit'] * 1.15 || $smoke > $this->ctx['sLimit'] * 1.5 ? 'HIGH' : 'MEDIUM');

        $created = is_string($row->created_at) ? \Carbon\Carbon::parse($row->created_at) : $row->created_at;

        return [
            $this->rowNumber,
            $created ? $created->format('d/m/Y H:i:s') : '-',
            number_format($temp, 1),
            $smoke,
            $fire ? 'FIRE' : '-',
            implode(' · ', $reasons) ?: '-',
            $severity,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $c = $this->ctx;

                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'NOC MONITORING · LOG INSIDEN (SUHU/ASAP/FLAME MELEWATI BATAS)');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC62828');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', strtoupper($c['periode'] ?? '-') . ' · Batas: Temp > ' . $c['tLimit'] . '°C atau Smoke > ' . $c['sLimit'] . ' PPM atau Flame trigger');
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF666666');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A4:G4');
                $sheet->setCellValue('A4', 'Total insiden ter-record: ' . count($c['incidents']));
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFC62828');
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Heading style
                $sheet->getStyle('A6:G6')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A6:G6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF002D33');
                $sheet->getStyle('A6:G6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension(6)->setRowHeight(20);

                $lastRow = $sheet->getHighestRow();
                if ($lastRow < 7) {
                    // No incidents
                    $sheet->mergeCells('A7:G7');
                    $sheet->setCellValue('A7', '✓ Tidak ada insiden pada periode ini — semua parameter dalam batas normal.');
                    $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF2E7D32');
                    $sheet->getStyle('A7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F5E9');
                    $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getRowDimension(7)->setRowHeight(30);
                    return;
                }

                $range = 'A7:G' . $lastRow;
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('FFDDDDDD');
                $sheet->getStyle('F7:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                foreach ([
                    ['"DANGER"', 'FFFFFFFF', 'FFC62828'],
                    ['"HIGH"',   'FFFFFFFF', 'FFEF6C00'],
                    ['"MEDIUM"', 'FF6D4C41', 'FFFFF3E0'],
                ] as [$val, $fg, $bg]) {
                    $cond = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
                    $cond->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
                        ->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
                        ->addCondition($val);
                    $cond->getStyle()->getFont()->setBold(true)->getColor()->setARGB($fg);
                    $cond->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                    $existing = $sheet->getStyle('G7:G' . $lastRow)->getConditionalStyles();
                    $existing[] = $cond;
                    $sheet->getStyle('G7:G' . $lastRow)->setConditionalStyles($existing);
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter('A6:G6');
                $sheet->getHeaderFooter()->setOddFooter('&L' . ($c['periode'] ?? '') . '&R&Phal / &N');
            },
        ];
    }
}
