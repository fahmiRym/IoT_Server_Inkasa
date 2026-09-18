<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\{Chart, DataSeries, DataSeriesValues, Legend, PlotArea, Title};
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class SummarySheet implements FromArray, WithTitle, WithEvents, WithColumnWidths
{
    public function __construct(protected array $ctx) {}

    public function title(): string { return 'Ringkasan'; }

    public function array(): array
    {
        // We use AfterSheet to build the layout, so return empty
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 4, 'B' => 26, 'C' => 22, 'D' => 22, 'E' => 22, 'F' => 22, 'G' => 22,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $c = $this->ctx;

                // ===== HEADER COMPANY =====
                $sheet->mergeCells('B2:G2');
                $sheet->setCellValue('B2', 'PT. INKASA JAYA ALUMINIUM');
                $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF004D60');
                $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('B3:G3');
                $sheet->setCellValue('B3', 'NOC COMMAND CENTER · MONITORING REPORT');
                $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF666666');
                $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('B4:G4');
                $sheet->setCellValue('B4', strtoupper($c['periode'] ?? '-'));
                $sheet->getStyle('B4')->getFont()->setItalic(true)->setSize(11)->getColor()->setARGB('FF999999');
                $sheet->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ===== INFO META =====
                $sheet->setCellValue('B6', 'Tanggal Cetak');
                $sheet->setCellValue('C6', now()->format('d M Y H:i:s'));
                $sheet->setCellValue('B7', 'Total Record');
                $sheet->setCellValue('C7', number_format($c['totalRecords']));
                $sheet->setCellValue('B8', 'Interval Agregasi');
                $sheet->setCellValue('C8', $c['aggLabel'] ?? '-');
                $sheet->setCellValue('B9', 'Batas Suhu');
                $sheet->setCellValue('C9', $c['tLimit'] . ' °C');
                $sheet->setCellValue('B10', 'Batas Asap (Smoke)');
                $sheet->setCellValue('C10', $c['sLimit'] . ' PPM');

                $sheet->getStyle('B6:B10')->getFont()->setBold(true);
                $sheet->getStyle('B6:C10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

                // ===== KPI CARDS (row 12) =====
                $sheet->mergeCells('B12:G12');
                $sheet->setCellValue('B12', 'KEY PERFORMANCE INDICATORS');
                $this->sectionTitle($sheet, 'B12:G12');

                $kpis = [
                    ['B14', 'MAX TEMP',     number_format($c['overall']['max_temp'], 1) . ' °C', 'FFEF6C00'],
                    ['C14', 'MIN TEMP',     number_format($c['overall']['min_temp'], 1) . ' °C', 'FF0277BD'],
                    ['D14', 'AVG TEMP',     number_format($c['overall']['avg_temp'], 1) . ' °C', 'FF00838F'],
                    ['E14', 'AVG HUM',      number_format($c['overall']['avg_hum'], 1) . ' %',  'FF00695C'],
                    ['F14', 'MAX SMOKE',    number_format($c['overall']['max_smoke']) . ' PPM', 'FFC62828'],
                    ['G14', 'CRITICAL EVT', number_format($c['overall']['critical_count']),      'FFD84315'],
                ];
                foreach ($kpis as [$cell, $lab, $val, $col]) {
                    $labCell = $cell;
                    $valCell = substr($cell, 0, 1) . '15';
                    $sheet->setCellValue($labCell, $lab);
                    $sheet->setCellValue($valCell, $val);
                    $sheet->getStyle($labCell)->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF666666');
                    $sheet->getStyle($labCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($valCell)->getFont()->setBold(true)->setSize(14)->getColor()->setARGB($col);
                    $sheet->getStyle($valCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("$labCell:$valCell")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("$labCell:$valCell")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F7FA');
                }
                $sheet->getRowDimension(15)->setRowHeight(28);

                // ===== BREAKDOWN SENSOR (row 17) =====
                $sheet->mergeCells('B17:G17');
                $sheet->setCellValue('B17', 'SENSOR BREAKDOWN (PEAK VALUES)');
                $this->sectionTitle($sheet, 'B17:G17');

                $sensorRows = [
                    ['SMOKE 1 (MAX)', number_format($c['overall']['max_s1']) . ' PPM'],
                    ['SMOKE 2 (MAX)', number_format($c['overall']['max_s2']) . ' PPM'],
                    ['SMOKE 3 (MAX)', number_format($c['overall']['max_s3']) . ' PPM'],
                    ['FLAME 1', $c['overall']['flame1_count'] > 0 ? ('TRIGGERED (' . $c['overall']['flame1_count'] . 'x)') : 'CLEAR'],
                    ['FLAME 2', $c['overall']['flame2_count'] > 0 ? ('TRIGGERED (' . $c['overall']['flame2_count'] . 'x)') : 'CLEAR'],
                ];
                $r = 19;
                foreach ($sensorRows as [$lab, $val]) {
                    $sheet->setCellValue("B$r", $lab);
                    $sheet->mergeCells("C$r:G$r");
                    $sheet->setCellValue("C$r", $val);
                    $sheet->getStyle("B$r")->getFont()->setBold(true);
                    $sheet->getStyle("B$r:G$r")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
                    $r++;
                }

                // ===== THRESHOLD BREACH (row 25) =====
                $sheet->mergeCells('B25:G25');
                $sheet->setCellValue('B25', 'THRESHOLD BREACH ANALYSIS');
                $this->sectionTitle($sheet, 'B25:G25');

                $br = $c['breach'];
                $breachRows = [
                    ['Suhu > ' . $c['tLimit'] . ' °C', $br['temp'] . ' event',
                        $br['temp_pct'] . ' % dari total'],
                    ['Asap > ' . $c['sLimit'] . ' PPM', $br['smoke'] . ' event',
                        $br['smoke_pct'] . ' % dari total'],
                    ['Flame Detection', $br['fire'] . ' event',
                        $br['fire_pct'] . ' % dari total'],
                ];
                $r = 27;
                foreach ($breachRows as [$lab, $count, $pct]) {
                    $sheet->setCellValue("B$r", $lab);
                    $sheet->setCellValue("C$r", $count);
                    $sheet->mergeCells("D$r:G$r");
                    $sheet->setCellValue("D$r", $pct);
                    $sheet->getStyle("B$r")->getFont()->setBold(true);
                    $sheet->getStyle("B$r:G$r")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
                    $r++;
                }

                // ===== FINAL ASSESSMENT (row 32) =====
                $sheet->mergeCells('B32:G32');
                $verdict = $c['overall']['critical_count'] > 0
                    ? '⚠ ATTENTION REQUIRED — ANOMALI TERDETEKSI'
                    : '✓ SISTEM DALAM PARAMETER NORMAL';
                $sheet->setCellValue('B32', $verdict);
                $sheet->getStyle('B32')->getFont()->setBold(true)->setSize(13)->getColor()
                    ->setARGB($c['overall']['critical_count'] > 0 ? 'FFC62828' : 'FF2E7D32');
                $sheet->getStyle('B32')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()
                    ->setARGB($c['overall']['critical_count'] > 0 ? 'FFFFEBEE' : 'FFE8F5E9');
                $sheet->getStyle('B32')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(32)->setRowHeight(32);
                $sheet->getStyle('B32')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setARGB($c['overall']['critical_count'] > 0 ? 'FFC62828' : 'FF2E7D32');

                // Footer
                $sheet->mergeCells('B34:G34');
                $sheet->setCellValue('B34', 'Dokumen ini di-generate otomatis oleh NOC Command Center — IoT Server Inkasa');
                $sheet->getStyle('B34')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF999999');
                $sheet->getStyle('B34')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getHeaderFooter()->setOddFooter('&L&B' . ($c['periode'] ?? '') . '&R&Phal / &N');
            },
        ];
    }

    private function sectionTitle($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF004D60');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $row = (int) preg_replace('/\D/', '', explode(':', $range)[0]);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }
}
