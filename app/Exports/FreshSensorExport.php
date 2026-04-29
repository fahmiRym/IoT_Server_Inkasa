<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Contracts\View\View;

class FreshSensorExport implements FromView, WithColumnWidths, WithEvents, WithDrawings
{
    protected $data;
    protected $periode;

    public function __construct($data, $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }

    public function view(): View
    {
        return view('reports.sensor_excel', [
            'data' => $this->data,
            'periode_label' => $this->periode
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 25,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
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
                $sheet->getHeaderFooter()->setOddFooter('&RPage &P of &N');
                $sheet->getStyle('A:F')->getAlignment()->setVertical('center');
                
                // Add a small VERSION marker in cell G1 to verify this is the latest code
                $sheet->setCellValue('G1', 'V2-FORCE');
                $sheet->getStyle('G1')->getFont()->setSize(8)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('EEEEEE'));
            },
        ];
    }
}
