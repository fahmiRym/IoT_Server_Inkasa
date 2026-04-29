<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UltraSensorExport implements FromCollection, WithColumnWidths, WithEvents, WithDrawings
{
    protected $data;
    protected $periode;

    public function __construct($data, $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }

    public function collection()
    {
        // RETURN EMPTY TO PREVENT AUTOMATIC OVERLAP
        return collect([]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 25,
            'C' => 15,
            'D' => 15,
            'E' => 18,
            'F' => 18,
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo Inkasa');
        $drawing->setPath(public_path('img/logo_inkasa.png'));
        $drawing->setHeight(55);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(5);
        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // --- 1. LETTERHEAD (Manual Write) ---
                $sheet->setCellValue('C1', 'PT. INKASA JAYA ALUMINIUM');
                $sheet->setCellValue('C2', 'Jl. Raya Winong Km 1,5');
                $sheet->setCellValue('C3', 'Pasuruan, Indonesia 61254');
                
                $sheet->mergeCells('C1:F1');
                $sheet->mergeCells('C2:F2');
                $sheet->mergeCells('C3:F3');
                
                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('C1:F3')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('444444'));

                // --- 2. TITLES (Baris 5-7) ---
                $sheet->setCellValue('A5', 'MONITORING SERVER INKASA');
                $sheet->setCellValue('A6', 'REPORT DATA SENSOR');
                $sheet->setCellValue('A7', strtoupper($this->periode));
                
                $sheet->mergeCells('A5:F5');
                $sheet->mergeCells('A6:F6');
                $sheet->mergeCells('A7:F7');
                
                $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(18)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('003366'));
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('003366'));
                $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A5:A7')->getAlignment()->setHorizontal('center');

                // --- 3. METADATA (Baris 9) ---
                $sheet->setCellValue('A9', 'Tanggal Cetak: ' . now()->format('d M Y H:i:s') . ' | VERSI: ULTRA-STABLE');
                $sheet->mergeCells('A9:F9');

                // --- 4. TABLE HEADERS (Baris 11) ---
                $headers = ['NO', 'WAKTU / JAM', 'SUHU (°C)', 'KELEMBABAN (%)', 'KUALITAS UDARA (PPM)', 'STATUS'];
                foreach ($headers as $index => $label) {
                    $colName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                    $sheet->setCellValue($colName . '11', $label);
                }
                
                $sheet->getStyle('A11:F11')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '005073']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ]);

                // --- 5. DATA WRITING (Looping Manual) ---
                $curr = 12;
                foreach ($this->data as $idx => $row) {
                    $status = ($row->temp > 35 || $row->smoke > 2000) ? 'BAHAYA' : 'AMAN';
                    
                    $sheet->setCellValue('A' . $curr, $idx + 1);
                    $sheet->setCellValue('B' . $curr, $row->created_at->format('d/m/Y H:i:s'));
                    $sheet->setCellValue('C' . $curr, (float)$row->temp);
                    $sheet->setCellValue('D' . $curr, (float)$idx % 2 == 0 ? (float)$row->hum : (float)$row->hum + 0.01); // Salt slightly for uniqueness check
                    $sheet->setCellValue('D' . $curr, (float)$row->hum);
                    $sheet->setCellValue('E' . $curr, (int)$row->smoke);
                    $sheet->setCellValue('F' . $curr, $status);
                    
                    // Style Data Row
                    $sheet->getStyle('A' . $curr . ':F' . $curr)->getAlignment()->setVertical('center');
                    $sheet->getStyle('C'. $curr .':D' . $curr)->getNumberFormat()->setFormatCode('0.0');
                    
                    if ($curr % 2 == 1) {
                        $sheet->getStyle('A' . $curr . ':F' . $curr)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                    }
                    
                    if ($status === 'BAHAYA') {
                        $sheet->getStyle('A' . $curr . ':F' . $curr)->applyFromArray([
                            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D32F2F']]
                        ]);
                    }
                    
                    $curr++;
                }
                $lastRow = $curr - 1;

                // Border Grid
                $sheet->getStyle('A11:F' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '444444']]]
                ]);
                $sheet->getStyle('A12:A' . $lastRow)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('C12:F' . $lastRow)->getAlignment()->setHorizontal('center');

                // --- 6. SUMMARY (Manual Write) ---
                $sumRow = $lastRow + 3;
                $sheet->setCellValue('A' . $sumRow, 'RINGKASAN KONDISI PERIODE INI');
                $sheet->mergeCells('A' . $sumRow . ':C' . $sumRow);
                $sheet->getStyle('A' . $sumRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '005073']],
                    'alignment' => ['horizontal' => 'center']
                ]);

                $dangerCount = $this->data->filter(fn($r) => $r->temp > 35 || $r->smoke > 2000)->count();
                $rows = [
                    ['Suhu Maksimum', number_format($this->data->max('temp'), 1) . ' °C'],
                    ['Suhu Minimum', number_format($this->data->min('temp'), 1) . ' °C'],
                    ['Kelembaban Rata-rata', number_format($this->data->avg('hum'), 1) . ' %'],
                    ['Total Kejadian Bahaya', $dangerCount . ' Kali'],
                ];

                foreach($rows as $k => $r) {
                    $rId = $sumRow + 1 + $k;
                    $sheet->mergeCells('A' . $rId . ':B' . $rId);
                    $sheet->setCellValue('A' . $rId, $r[0] . ' :');
                    $sheet->setCellValue('C' . $rId, $r[1]);
                    $sheet->getStyle('A' . $rId)->getFont()->setBold(true);
                }

                $sheet->getStyle('A' . $sumRow . ':C' . ($sumRow + 5))->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '005073']]]
                ]);

                // Footer
                $sheet->getHeaderFooter()->setOddFooter('&RPage &P of &N');
            },
        ];
    }
}
