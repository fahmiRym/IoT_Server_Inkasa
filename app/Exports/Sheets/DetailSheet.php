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
use PhpOffice\PhpSpreadsheet\Conditional\Conditional;
use PhpOffice\PhpSpreadsheet\Conditional\ConditionalFormatting\Wizard\CellValue as CV;
use PhpOffice\PhpSpreadsheet\Style\Conditional as StyleConditional;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Style;

class DetailSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithColumnWidths, WithCustomStartCell
{
    protected int $rowNumber = 0;

    public function __construct(protected array $ctx) {}

    public function title(): string { return 'Detail Data'; }
    public function startCell(): string { return 'A6'; }

    public function collection()
    {
        return $this->ctx['aggregated'];
    }

    public function headings(): array
    {
        return ['NO', 'TIMESTAMP', 'TEMP (°C)', 'HUM (%)', 'AVG SMOKE (PPM)',
                'S1', 'S2', 'S3', 'F1', 'F2', 'STATUS'];
    }

    public function columnWidths(): array
    {
        return ['A' => 5, 'B' => 22, 'C' => 12, 'D' => 12, 'E' => 15,
                'F' => 10, 'G' => 10, 'H' => 10, 'I' => 10, 'J' => 10, 'K' => 14];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $temp = (float) ($row->temp ?? 0);
        $smoke = (float) ($row->smoke ?? 0);
        $created = is_string($row->created_at) ? \Carbon\Carbon::parse($row->created_at) : $row->created_at;

        $isCritical = $temp > $this->ctx['tLimit']
            || $smoke > $this->ctx['sLimit']
            || (int) $row->flame1 === 1
            || (int) $row->flame2 === 1;

        return [
            $this->rowNumber,
            $created ? $created->format('d/m/Y H:i:s') : '-',
            number_format($temp, 1),
            number_format((float) $row->hum, 1),
            (int) round($smoke),
            (int) round((float) $row->smoke1),
            (int) round((float) $row->smoke2),
            (int) round((float) $row->smoke3),
            ((int) $row->flame1 === 1) ? 'FIRE' : 'SAFE',
            ((int) $row->flame2 === 1) ? 'FIRE' : 'SAFE',
            $isCritical ? 'CRITICAL' : 'STABLE',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $c = $this->ctx;

                // Title bar
                $sheet->mergeCells('A1:K1');
                $sheet->setCellValue('A1', 'NOC MONITORING · DETAIL DATA (' . ($c['aggLabel'] ?? '') . ')');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF004D60');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(22);

                $sheet->mergeCells('A2:K2');
                $sheet->setCellValue('A2', strtoupper($c['periode'] ?? '-') . ' · Batas: Temp > ' . $c['tLimit'] . '°C atau Smoke > ' . $c['sLimit'] . ' PPM = CRITICAL');
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF666666');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A4:K4');
                $sheet->setCellValue('A4', 'Dicetak: ' . now()->format('d M Y H:i:s'));
                $sheet->getStyle('A4')->getFont()->setSize(9)->getColor()->setARGB('FF999999');
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Headings row
                $sheet->getStyle('A6:K6')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A6:K6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF002D33');
                $sheet->getStyle('A6:K6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(6)->setRowHeight(20);

                $lastRow = $sheet->getHighestRow();
                if ($lastRow < 7) return;

                // Data cell alignment & borders
                $range = 'A7:K' . $lastRow;
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB('FFDDDDDD');
                $sheet->getStyle('A7:A' . $lastRow)->getFont()->getColor()->setARGB('FF999999');

                // Conditional formatting: STATUS column K
                $condCritical = new StyleConditional();
                $condCritical->setConditionType(StyleConditional::CONDITION_CELLIS)
                    ->setOperatorType(StyleConditional::OPERATOR_EQUAL)
                    ->addCondition('"CRITICAL"');
                $condCritical->getStyle()->getFont()->setBold(true)->getColor()->setARGB('FFC62828');
                $condCritical->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFEBEE');

                $condStable = new StyleConditional();
                $condStable->setConditionType(StyleConditional::CONDITION_CELLIS)
                    ->setOperatorType(StyleConditional::OPERATOR_EQUAL)
                    ->addCondition('"STABLE"');
                $condStable->getStyle()->getFont()->getColor()->setARGB('FF2E7D32');

                $sheet->getStyle('K7:K' . $lastRow)->setConditionalStyles([$condCritical, $condStable]);

                // Conditional formatting: F1/F2 FIRE
                $condFire = new StyleConditional();
                $condFire->setConditionType(StyleConditional::CONDITION_CELLIS)
                    ->setOperatorType(StyleConditional::OPERATOR_EQUAL)
                    ->addCondition('"FIRE"');
                $condFire->getStyle()->getFont()->setBold(true)->getColor()->setARGB('FFC62828');
                $sheet->getStyle('I7:J' . $lastRow)->setConditionalStyles([$condFire]);

                // Conditional formatting: TEMP > tLimit (col C)
                $condTemp = new StyleConditional();
                $condTemp->setConditionType(StyleConditional::CONDITION_CELLIS)
                    ->setOperatorType(StyleConditional::OPERATOR_GREATERTHAN)
                    ->addCondition((string) $c['tLimit']);
                $condTemp->getStyle()->getFont()->setBold(true)->getColor()->setARGB('FFEF6C00');
                $sheet->getStyle('C7:C' . $lastRow)->setConditionalStyles([$condTemp]);

                // Conditional formatting: SMOKE > sLimit (col E)
                $condSmoke = new StyleConditional();
                $condSmoke->setConditionType(StyleConditional::CONDITION_CELLIS)
                    ->setOperatorType(StyleConditional::OPERATOR_GREATERTHAN)
                    ->addCondition((string) $c['sLimit']);
                $condSmoke->getStyle()->getFont()->setBold(true)->getColor()->setARGB('FFC62828');
                $sheet->getStyle('E7:E' . $lastRow)->setConditionalStyles([$condSmoke]);

                // Freeze pane
                $sheet->freezePane('A7');
                // Auto filter
                $sheet->setAutoFilter('A6:K6');

                $sheet->getHeaderFooter()->setOddFooter('&L' . ($c['periode'] ?? '') . '&R&Phal / &N');
            },
        ];
    }
}
