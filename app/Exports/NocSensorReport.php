<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\SummarySheet;
use App\Exports\Sheets\DetailSheet;
use App\Exports\Sheets\HourlySheet;
use App\Exports\Sheets\IncidentSheet;

class NocSensorReport implements WithMultipleSheets
{
    public function __construct(
        protected array $ctx
    ) {}

    public function sheets(): array
    {
        return [
            'Ringkasan'    => new SummarySheet($this->ctx),
            'Detail Data'  => new DetailSheet($this->ctx),
            'Rekap Per Jam'=> new HourlySheet($this->ctx),
            'Log Insiden'  => new IncidentSheet($this->ctx),
        ];
    }
}
