<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;

class SaktiSensorExport implements FromCollection, WithMapping, WithHeadings, WithColumnWidths, WithEvents, WithCustomStartCell
{
    protected $query;
    protected $periode;
    protected $tLimit;
    protected $sLimit;
    protected $rowNumber = 0;

    public function __construct($query, $periode)
    {
        $this->query = $query;
        $this->periode = $periode;
        $this->tLimit = env('SENSOR_TEMP_LIMIT', 35);
        $this->sLimit = env('SENSOR_SMOKE_LIMIT', 1000);
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function startCell(): string
    {
        return 'A11';
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $isBahaya = ($row->temp > $this->tLimit || $row->smoke > $this->sLimit);
        
        $createdAt = $row->created_at;
        if (is_string($createdAt)) {
            $createdAt = \Carbon\Carbon::parse($createdAt);
        }
        
        return [
            $this->rowNumber,
            $createdAt ? $createdAt->format('d/m/Y H:i:s') : '-',
            number_format($row->temp, 1),
            number_format($row->hum, 1),
            intval(round($row->smoke)),
            intval(round($row->smoke1 ?? 0)),
            intval(round($row->smoke2 ?? 0)),
            intval(round($row->smoke3 ?? 0)),
            $row->flame1 ? 'FIRE' : 'SAFE',
            $row->flame2 ? 'FIRE' : 'SAFE',
            $isBahaya ? 'CRITICAL' : 'STABLE',
        ];
    }

    public function headings(): array
    {
        return [
            'NO',
            'TIME STAMP',
            'TEMP (°C)',
            'HUM (%)',
            'SMOKE (AVG)',
            'S1',
            'S2',
            'S3',
            'F1',
            'F2',
            'NODE STATUS',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 25,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 10,
            'J' => 10,
            'K' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // PT INKASA JAYA ALUMINIUM
                $sheet->mergeCells('C1:F1');
                $sheet->setCellValue('C1', 'PT. INKASA JAYA ALUMINIUM');
                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF004D60');
                
                $sheet->mergeCells('C2:F2');
                $sheet->setCellValue('C2', 'Jl. Raya Winong Km 1,5, Pasuruan, Indonesia');
                
                // Title
                $sheet->mergeCells('A4:K4');
                $sheet->setCellValue('A4', 'MONITORING NOC COMMAND CENTER');
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF004D60');
                $sheet->getStyle('A4')->getAlignment()->setHorizontal('center');
                
                // Periode
                $sheet->mergeCells('A5:K5');
                $sheet->setCellValue('A5', strtoupper($this->periode));
                $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF004D60');
                $sheet->getStyle('A5')->getAlignment()->setHorizontal('center');
                
                // Print date
                $sheet->mergeCells('A7:K7');
                $sheet->setCellValue('A7', 'Dicetak pada: ' . now()->format('d M Y H:i:s') . ' | Versi: Sakti-ULTIMATE');
                $sheet->getStyle('A7')->getFont()->setItalic(true);
                
                // Headings
                $sheet->getStyle('A11:K11')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A11:K11')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF002D33');
                $sheet->getStyle('A11:K11')->getAlignment()->setHorizontal('center')->setVertical('center');
                
                $highestRow = $sheet->getHighestRow();
                
                // Apply conditional formatting background color if needed
                // It is a bit complex for Excel, we'll just keep the font colors from map() if we wanted, 
                // but since mapping doesn't allow returning cell styles, we can just apply basic borders.
                $sheet->getStyle('A11:K' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                
                $sheet->getStyle('A:K')->getAlignment()->setVertical('center')->setHorizontal('center');
                
                $sheet->getHeaderFooter()->setOddFooter('&RPage &P of &N');
            },
        ];
    }
}
