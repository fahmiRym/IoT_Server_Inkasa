<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SensorExport implements FromCollection, WithColumnWidths, WithEvents, WithDrawings, WithCustomStartCell
{
    protected $data;
    protected $periode;

    public function __construct($data, $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }

    public function startCell(): string
    {
        return 'A12';
    }

    public function collection()
    {
        // Return only the ACTUAL data mapped for rows starting from 12
        return $this->data->map(function ($row, $index) {
            $status = ($row->temp > 35 || $row->smoke > 2000) ? 'BAHAYA' : 'AMAN';
            return [
                $index + 1,
                $row->created_at->format('d/m/Y H:i:s'),
                (float)$row->temp,
                (float)$row->hum,
                (int)$row->smoke,
                $status
            ];
        });
    }

    // This is the CRITICAL part for overlap prevention
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // 1. Shift existing data down (just in case startCell failed in specific versions)
                // We'll actually clear the top rows and write headers manually
                
                // --- A. LETTERHEAD (Baris 1-3) ---
                $sheet->mergeCells('C1:F1'); $sheet->setCellValue('C1', 'PT. INKASA JAYA ALUMINIUM');
                $sheet->mergeCells('C2:F2'); $sheet->setCellValue('C2', 'Jl. Raya Winong Km 1,5');
                $sheet->mergeCells('C3:F3'); $sheet->setCellValue('C3', 'Pasuruan, Indonesia 61254');
                
                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('C1:F3')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('444444'));

                // --- B. TITLES (Baris 5-7) ---
                $sheet->mergeCells('A5:F5'); $sheet->setCellValue('A5', 'MONITORING SERVER INKASA');
                $sheet->mergeCells('A6:F6'); $sheet->setCellValue('A6', 'REPORT DATA SENSOR');
                $sheet->mergeCells('A7:F7'); $sheet->setCellValue('A7', strtoupper($this->periode));
                
                $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(18)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('003366'));
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('003366'));
                $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A5:A7')->getAlignment()->setHorizontal('center');

                // --- C. TABLE HEADER (Baris 11) ---
                $headers = ['NO', 'WAKTU / JAM', 'SUHU (°C)', 'KELEMBABAN (%)', 'KUALITAS UDARA (PPM)', 'STATUS'];
                foreach ($headers as $k => $h) {
                    $col = chr(65 + $k);
                    $sheet->setCellValue($col . '11', $h);
                }
                
                $sheet->getStyle('A11:F11')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '005073']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                // --- D. FORMAT DATA ROWS ---
                $lastDataRow = $this->data->count() + 11;
                $sheet->getStyle('A11:F' . $lastDataRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ]);
                $sheet->getStyle('C12:F' . $lastDataRow)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('C12:D' . $lastDataRow)->getNumberFormat()->setFormatCode('0.0');

                // Zebra & Danger
                for ($i = 12; $i <= $lastDataRow; $i++) {
                    if ($i % 2 == 1) {
                        $sheet->getStyle('A' . $i . ':F' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                    }
                    if ($sheet->getCell('F' . $i)->getValue() === 'BAHAYA') {
                        $sheet->getStyle('A' . $i . ':F' . $i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D32F2F');
                        $sheet->getStyle('A' . $i . ':F' . $i)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                    }
                }

                // Footer & Page Number
                $sheet->getHeaderFooter()->setOddFooter('&RPage &P of &N');
            },
        ];
    }

    public function columnWidths(): array {
        return ['A' => 8, 'B' => 25, 'C' => 15, 'D' => 15, 'E' => 20, 'F' => 15];
    }

    public function drawings() {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath(public_path('img/logo_inkasa.png'));
        $drawing->setHeight(55);
        $drawing->setCoordinates('A1');
        return $drawing;
    }
}
