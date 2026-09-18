<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function getLiveMetrics()
    {
        // 1. Data Terakhir (Live) — cached 3 detik biar tahan burst polling
        $latest = \Illuminate\Support\Facades\Cache::remember('latest_sensor_reading', 3, function () {
            return SensorLog::latest()->first();
        });

        // 2. Statistik Hari Ini — cache key include tanggal biar auto-reset saat ganti hari
        $todayKey = 'daily_sensor_stats:' . \Carbon\Carbon::today()->toDateString();
        $stats = \Illuminate\Support\Facades\Cache::remember($todayKey, 60, function () {
            return SensorLog::where('created_at', '>=', \Carbon\Carbon::today())
                ->selectRaw('MAX(temp) as max_temp, MIN(temp) as min_temp, AVG(hum) as avg_hum, MAX(smoke) as max_smoke')
                ->first();
        });

        $cols = ['id', 'temp', 'hum', 'smoke', 'fire', 'smoke1', 'smoke2', 'smoke3', 'flame1', 'flame2', 'created_at'];

        // 3. History Grafik — ambil 120 titik terbaru
        $history = SensorLog::latest()->take(120)->get($cols)->reverse()->values();

        // 4. Recent Logs (5 data terakhir untuk tabel)
        $recentLogs = SensorLog::latest()->take(5)->get($cols);

        // 5. Total Count (cache 5 menit karena COUNT(*) berat di MySQL)
        $totalCount = \Illuminate\Support\Facades\Cache::remember('total_sensor_count', 300, function () {
            return SensorLog::count();
        });

        return response()->json([
            'status' => 'success',
            'latest' => $latest,
            'total_count' => $totalCount,
            'stats' => [
                'max_temp' => round($stats->max_temp ?? 0, 1),
                'min_temp' => round($stats->min_temp ?? 0, 1),
                'avg_hum' => round($stats->avg_hum ?? 0, 1),
                'max_smoke' => $stats->max_smoke ?? 0,
            ],
            'history' => $history,
            'recent' => $recentLogs,
            'config' => [
                'temp_limit'  => (float) config('services.sensor.temp_limit', 35),
                'smoke_limit' => (int)   config('services.sensor.smoke_limit', 600),
                'timeout'     => (int)   env('SENSOR_TIMEOUT', 120),
            ],
            'alarm_state'   => (bool) \Illuminate\Support\Facades\Cache::get('sensor.alarm.state', false),
            'server_time'   => now()->toIso8601String(),
        ]);
    }

    private function applyFilters(Request $request)
    {
        $query = SensorLog::query();
        $label = "Hari Ini";

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
            $label = "Custom Range ({$request->start_date} s/d {$request->end_date})";
        } elseif ($request->has('month') && $request->month) {
            $query->whereMonth('created_at', Carbon::parse($request->month)->month)
                ->whereYear('created_at', Carbon::parse($request->month)->year);
            $label = "Bulan " . Carbon::parse($request->month)->format('M Y');
        } elseif ($request->has('year') && $request->year) {
            $query->whereYear('created_at', $request->year);
            $label = "Tahun {$request->year}";
        } else {
            $query->whereDate('created_at', Carbon::today());
        }

        return [$query, $label];
    }

    public function export(Request $request)
    {
        [$query, $label] = $this->applyFilters($request);

        if (!(clone $query)->exists()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk rentang waktu ini.');
        }

        $ctx = $this->buildReportContext($request, $query, $label);

        $filename = "NOC_Report_" . now()->format('Ymd_His') . ".xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\NocSensorReport($ctx),
            $filename
        );
    }

    /**
     * Bangun konteks lengkap untuk Excel & PDF report.
     * Menghasilkan: overall stats, threshold breach, aggregated series, hourly rekap, incidents.
     */
    private function buildReportContext(Request $request, $query, string $label): array
    {
        $tLimit = (float) config('services.sensor.temp_limit', 35);
        $sLimit = (int)   config('services.sensor.smoke_limit', 600);

        // Hitung rentang hari & interval agregasi
        $diffDays = $this->estimateDiffDays($request);
        [$interval, $aggLabel] = $this->pickAggregation($diffDays);

        // Aggregated detail (untuk sheet Detail Data & chart)
        $aggregated = (clone $query)->selectRaw("
            FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at) / {$interval})) as bucket,
            AVG(temp)   as temp,
            MAX(temp)   as temp_max,
            AVG(hum)    as hum,
            AVG(smoke)  as smoke,
            MAX(smoke)  as smoke_max,
            AVG(smoke1) as smoke1,
            AVG(smoke2) as smoke2,
            AVG(smoke3) as smoke3,
            MAX(flame1) as flame1,
            MAX(flame2) as flame2,
            MIN(created_at) as created_at
        ")->groupBy('bucket')->orderBy('bucket', 'ASC')->get();

        // Rekap per periode (jam/hari) untuk sheet HourlySheet dan PDF trend
        $groupFmt   = $diffDays > 3 ? "%Y-%m-%d" : "%Y-%m-%d %H:00";
        $groupLabel = $diffDays > 3 ? 'Per Hari' : 'Per Jam';

        $hourly = (clone $query)->selectRaw("
            DATE_FORMAT(created_at, '{$groupFmt}') as label,
            AVG(temp)   as avg_temp,
            MAX(temp)   as max_temp,
            MIN(temp)   as min_temp,
            AVG(hum)    as avg_hum,
            AVG(smoke)  as avg_smoke,
            MAX(smoke)  as max_smoke,
            AVG(smoke1) as avg_s1,
            AVG(smoke2) as avg_s2,
            AVG(smoke3) as avg_s3,
            SUM(CASE WHEN flame1 = 1 OR flame2 = 1 THEN 1 ELSE 0 END) as flame_count,
            SUM(CASE WHEN temp > {$tLimit} OR smoke > {$sLimit} OR flame1 = 1 OR flame2 = 1 THEN 1 ELSE 0 END) as critical_count,
            COUNT(*) as total
        ")->groupBy('label')->orderBy('label', 'ASC')->get();

        // Overall stats
        $totalRecords = (clone $query)->count();
        $overall = [
            'max_temp'    => (float) ((clone $query)->max('temp') ?? 0),
            'min_temp'    => (float) ((clone $query)->min('temp') ?? 0),
            'avg_temp'    => (float) ((clone $query)->avg('temp') ?? 0),
            'avg_hum'     => (float) ((clone $query)->avg('hum') ?? 0),
            'max_smoke'   => (int)   ((clone $query)->max('smoke') ?? 0),
            'max_s1'      => (int)   ((clone $query)->max('smoke1') ?? 0),
            'max_s2'      => (int)   ((clone $query)->max('smoke2') ?? 0),
            'max_s3'      => (int)   ((clone $query)->max('smoke3') ?? 0),
            'flame1_count'=> (clone $query)->where('flame1', 1)->count(),
            'flame2_count'=> (clone $query)->where('flame2', 1)->count(),
            'fired'       => (clone $query)->where('fire', 1)->exists(),
            'critical_count' => (clone $query)->where(function ($q) use ($tLimit, $sLimit) {
                $q->where('temp', '>', $tLimit)
                  ->orWhere('smoke', '>', $sLimit)
                  ->orWhere('fire', 1);
            })->count(),
        ];

        // Threshold breach percentages
        $tempBreach  = (clone $query)->where('temp', '>', $tLimit)->count();
        $smokeBreach = (clone $query)->where('smoke', '>', $sLimit)->count();
        $fireBreach  = (clone $query)->where('fire', 1)->count();
        $pct = fn($n) => $totalRecords > 0 ? round(($n / $totalRecords) * 100, 2) : 0;
        $breach = [
            'temp' => $tempBreach, 'temp_pct' => $pct($tempBreach),
            'smoke' => $smokeBreach, 'smoke_pct' => $pct($smokeBreach),
            'fire' => $fireBreach,  'fire_pct'  => $pct($fireBreach),
        ];

        // Incidents (max 200, sorted by severity heuristic — flame first, then most critical)
        $incidents = (clone $query)
            ->where(function ($q) use ($tLimit, $sLimit) {
                $q->where('temp', '>', $tLimit)
                  ->orWhere('smoke', '>', $sLimit)
                  ->orWhere('fire', 1);
            })
            ->orderByDesc('fire')
            ->orderByDesc('smoke')
            ->orderByDesc('temp')
            ->orderBy('created_at')
            ->limit(200)
            ->get(['created_at', 'temp', 'hum', 'smoke', 'fire', 'flame1', 'flame2']);

        return [
            'periode'      => $label,
            'tLimit'       => $tLimit,
            'sLimit'       => $sLimit,
            'diffDays'     => $diffDays,
            'aggLabel'     => $aggLabel,
            'groupLabel'   => $groupLabel,
            'totalRecords' => $totalRecords,
            'aggregated'   => $aggregated,
            'hourly'       => $hourly,
            'overall'      => $overall,
            'breach'       => $breach,
            'incidents'    => $incidents,
        ];
    }

    private function estimateDiffDays(Request $request): int
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            return max(1, (int) \Carbon\Carbon::parse($request->start_date)
                ->diffInDays(\Carbon\Carbon::parse($request->end_date)));
        }
        if ($request->filled('month')) return 30;
        if ($request->filled('year'))  return 365;
        return 1;
    }

    private function pickAggregation(int $diffDays): array
    {
        if ($diffDays <= 1)  return [300,  '5 Menit'];
        if ($diffDays <= 7)  return [900,  '15 Menit'];
        if ($diffDays <= 31) return [3600, '1 Jam'];
        return [86400, '1 Hari'];
    }

    public function exportPdf(Request $request)
    {
        [$query, $label] = $this->applyFilters($request);

        if (!(clone $query)->exists()) {
            return redirect()->back()->with('error', 'Tidak ada data untuk rentang waktu ini.');
        }

        $ctx = $this->buildReportContext($request, $query, $label);
        $charts = $this->renderReportCharts($ctx);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sensor_pdf', [
            'ctx'    => $ctx,
            'charts' => $charts,
            'now'    => now(),
        ])->setPaper('a4', 'portrait');

        $filename = "NOC_Report_" . now()->format('Ymd_His') . ".pdf";
        return $pdf->download($filename);
    }

    /**
     * Render chart PNG via QuickChart.io — 4 chart untuk PDF.
     * Return array [envChart, airChart, breachChart, hourlyChart] atau null kalau gagal.
     */
    private function renderReportCharts(array $ctx): array
    {
        $hourly = $ctx['hourly'];
        $labels = $hourly->pluck('label')->toArray();

        // Reduce labels di chart supaya tidak terlalu padat (max ~40 titik)
        if (count($labels) > 40) {
            $step = (int) ceil(count($labels) / 40);
            $labels = array_values(array_filter($labels, fn($_, $i) => $i % $step === 0, ARRAY_FILTER_USE_BOTH));
            $hourly = $hourly->filter(fn($_, $i) => $i % $step === 0)->values();
        }

        $tempData  = $hourly->pluck('avg_temp')->map(fn($v) => round((float) $v, 1))->toArray();
        $humData   = $hourly->pluck('avg_hum')->map(fn($v) => round((float) $v, 1))->toArray();
        $smokeMax  = $hourly->pluck('max_smoke')->map(fn($v) => (int) $v)->toArray();
        $s1        = $hourly->pluck('avg_s1')->map(fn($v) => round((float) $v, 1))->toArray();
        $s2        = $hourly->pluck('avg_s2')->map(fn($v) => round((float) $v, 1))->toArray();
        $s3        = $hourly->pluck('avg_s3')->map(fn($v) => round((float) $v, 1))->toArray();
        $critical  = $hourly->pluck('critical_count')->map(fn($v) => (int) $v)->toArray();

        // Threshold line
        $tempThreshold = array_fill(0, count($labels), $ctx['tLimit']);
        $smokeThreshold = array_fill(0, count($labels), $ctx['sLimit']);

        // Chart 1 — Environmental Trend (Temp + Humidity dual axis + threshold line)
        $chartEnv = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Suhu (°C)',  'data' => $tempData, 'borderColor' => '#e65100', 'backgroundColor' => 'rgba(230,81,0,0.1)', 'yAxisID' => 'y1', 'fill' => true, 'tension' => 0.35, 'borderWidth' => 2.5, 'pointRadius' => 0],
                    ['label' => 'Kelembaban (%)', 'data' => $humData, 'borderColor' => '#0277bd', 'yAxisID' => 'y2', 'fill' => false, 'tension' => 0.35, 'borderWidth' => 2, 'pointRadius' => 0],
                    ['label' => 'Batas Suhu', 'data' => $tempThreshold, 'borderColor' => '#c62828', 'borderDash' => [6,4], 'yAxisID' => 'y1', 'fill' => false, 'borderWidth' => 1.5, 'pointRadius' => 0],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'TREND SUHU & KELEMBABAN', 'fontSize' => 14, 'fontStyle' => 'bold'],
                'legend' => ['position' => 'top', 'labels' => ['fontSize' => 11]],
                'scales' => [
                    'yAxes' => [
                        ['id' => 'y1', 'position' => 'left',  'scaleLabel' => ['display' => true, 'labelString' => 'Suhu (°C)'], 'ticks' => ['beginAtZero' => false]],
                        ['id' => 'y2', 'position' => 'right', 'scaleLabel' => ['display' => true, 'labelString' => 'Kelembaban (%)'], 'gridLines' => ['drawOnChartArea' => false]],
                    ],
                ],
            ],
        ];

        // Chart 2 — Air Quality Breakdown (S1/S2/S3 + MAX line + threshold)
        $chartAir = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'S1', 'data' => $s1, 'borderColor' => '#00838f', 'fill' => false, 'borderWidth' => 1.4, 'pointRadius' => 0, 'tension' => 0.3],
                    ['label' => 'S2', 'data' => $s2, 'borderColor' => '#d84315', 'fill' => false, 'borderWidth' => 1.4, 'pointRadius' => 0, 'tension' => 0.3],
                    ['label' => 'S3', 'data' => $s3, 'borderColor' => '#f9a825', 'fill' => false, 'borderWidth' => 1.4, 'pointRadius' => 0, 'tension' => 0.3],
                    ['label' => 'MAX (Total)', 'data' => $smokeMax, 'borderColor' => '#2e7d32', 'backgroundColor' => 'rgba(46,125,50,0.15)', 'fill' => true, 'borderWidth' => 2.5, 'pointRadius' => 0, 'tension' => 0.3],
                    ['label' => 'Batas Asap', 'data' => $smokeThreshold, 'borderColor' => '#c62828', 'borderDash' => [6,4], 'fill' => false, 'borderWidth' => 1.5, 'pointRadius' => 0],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'KUALITAS UDARA — MULTI SENSOR (PPM)', 'fontSize' => 14, 'fontStyle' => 'bold'],
                'legend' => ['position' => 'top', 'labels' => ['fontSize' => 11]],
                'scales' => ['yAxes' => [['scaleLabel' => ['display' => true, 'labelString' => 'PPM'], 'ticks' => ['beginAtZero' => true]]]],
            ],
        ];

        // Chart 3 — Threshold breach bar
        $breachChart = [
            'type' => 'bar',
            'data' => [
                'labels' => ['Suhu > ' . $ctx['tLimit'] . '°C', 'Asap > ' . $ctx['sLimit'] . ' PPM', 'Flame Detect'],
                'datasets' => [
                    [
                        'label' => 'Jumlah Event',
                        'data' => [$ctx['breach']['temp'], $ctx['breach']['smoke'], $ctx['breach']['fire']],
                        'backgroundColor' => ['#ef6c00', '#c62828', '#6a1b9a'],
                    ],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'ANALISIS PELANGGARAN AMBANG BATAS', 'fontSize' => 14, 'fontStyle' => 'bold'],
                'legend' => ['display' => false],
                'scales' => ['yAxes' => [['ticks' => ['beginAtZero' => true, 'stepSize' => 1]]]],
            ],
        ];

        // Chart 4 — Critical events per periode
        $criticalChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Insiden per periode', 'data' => $critical, 'backgroundColor' => 'rgba(198,40,40,0.7)', 'borderColor' => '#c62828', 'borderWidth' => 1],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'DISTRIBUSI INSIDEN PER PERIODE', 'fontSize' => 14, 'fontStyle' => 'bold'],
                'legend' => ['display' => false],
                'scales' => ['yAxes' => [['ticks' => ['beginAtZero' => true, 'stepSize' => 1]]]],
            ],
        ];

        return [
            'env'      => $this->quickChart($chartEnv,      760, 300),
            'air'      => $this->quickChart($chartAir,      760, 300),
            'breach'   => $this->quickChart($breachChart,   380, 240),
            'critical' => $this->quickChart($criticalChart, 380, 240),
        ];
    }

    private function quickChart(array $chart, int $w, int $h): ?string
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(15)
                ->post('https://quickchart.io/chart', [
                    'chart' => $chart,
                    'width' => $w,
                    'height' => $h,
                    'backgroundColor' => 'white',
                    'devicePixelRatio' => 2,
                    'version' => '2.9.4',
                ]);
            if ($resp->successful()) {
                return 'data:image/png;base64,' . base64_encode($resp->body());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('QuickChart fail: ' . $e->getMessage());
        }
        return null;
    }
}