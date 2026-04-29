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
        // 1. Data Terakhir (Live)
        $latest = SensorLog::latest()->first();

        // 2. Statistik Hari Ini
        $stats = SensorLog::whereDate('created_at', Carbon::today())
            ->selectRaw('MAX(temp) as max_temp, MIN(temp) as min_temp, AVG(hum) as avg_hum, MAX(smoke) as max_smoke')
            ->first();

        // 3. History Grafik — ambil 120 titik terbaru
        $history = SensorLog::latest()->take(120)->get(['id', 'temp', 'hum', 'smoke', 'fire', 'smoke1', 'smoke2', 'smoke3', 'flame1', 'flame2', 'created_at'])->reverse()->values();

        // 4. Recent Logs (5 data terakhir untuk tabel)
        $recentLogs = SensorLog::latest()->take(5)->get(['id', 'temp', 'hum', 'smoke', 'fire', 'smoke1', 'smoke2', 'smoke3', 'flame1', 'flame2', 'created_at']);

        return response()->json([
            'status' => 'success',
            'latest' => $latest,
            'total_count' => SensorLog::count(),
            'stats' => [
                'max_temp' => round($stats->max_temp ?? 0, 1),
                'min_temp' => round($stats->min_temp ?? 0, 1),
                'avg_hum' => round($stats->avg_hum ?? 0, 1),
                'max_smoke' => $stats->max_smoke ?? 0,
            ],
            'history' => $history,
            'recent' => $recentLogs,
            'config' => [
                'temp_limit' => (float) env('SENSOR_TEMP_LIMIT', 35),
                'smoke_limit' => (int) env('SENSOR_SMOKE_LIMIT', 1000),
                'timeout' => (int) env('SENSOR_TIMEOUT', 120),
            ]
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
        $data = $query->orderBy('created_at', 'ASC')->get();
        // Use a very unique filename to prevent cache issues
        $filename = "Report_Excel_NOC_Inkasa" . now()->format('His') . "_" . uniqid() . ".xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SaktiSensorExport($data, $label),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        [$query, $label] = $this->applyFilters($request);

        $tLimit = env('SENSOR_TEMP_LIMIT', 35);
        $sLimit = env('SENSOR_SMOKE_LIMIT', 600);

        $diffDays = 0;
        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $diffDays = \Carbon\Carbon::parse($request->start_date)->diffInDays(\Carbon\Carbon::parse($request->end_date));
        } elseif ($request->has('month') && $request->month) {
            $diffDays = 30;
        } elseif ($request->has('year') && $request->year) {
            $diffDays = 365;
        }

        $groupByFormat = $diffDays > 3 ? "%Y-%m-%d" : "%Y-%m-%d %H:00";

        // Aggregate for Chart and Summary Tabular Data
        $stats = (clone $query)->selectRaw("
            DATE_FORMAT(created_at, '{$groupByFormat}') as label,
            AVG(temp) as temp,
            AVG(hum) as hum,
            AVG(smoke) as smoke_avg,
            MAX(smoke) as smoke_max,
            AVG(smoke1) as s1,
            AVG(smoke2) as s2,
            AVG(smoke3) as s3,
            MAX(flame1) as f1,
            MAX(flame2) as f2
        ")->groupBy('label')->orderBy('label', 'ASC')->get();

        $totalRecords = (clone $query)->count();
        if ($totalRecords == 0) {
            return redirect()->back()->with('error', 'Tidak ada data untuk rentang waktu ini.');
        }

        $overallStats = [
            'max_temp' => (clone $query)->max('temp') ?? 0,
            'min_temp' => (clone $query)->min('temp') ?? 0,
            'avg_hum' => (clone $query)->avg('hum') ?? 0,
            'max_smoke' => (clone $query)->max('smoke') ?? 0,
            'max_s1' => (clone $query)->max('smoke1') ?? 0,
            'max_s2' => (clone $query)->max('smoke2') ?? 0,
            'max_s3' => (clone $query)->max('smoke3') ?? 0,
            'fired' => (clone $query)->where('fire', 1)->count() > 0,
            'critical_count' => (clone $query)->where(function ($q) use ($tLimit, $sLimit) {
                $q->where('temp', '>', $tLimit)
                    ->orWhere('smoke', '>', $sLimit)
                    ->orWhere('fire', 1);
            })->count()
        ];

        // --- CHART 1: AIR QUALITY (SENSOR BREAKDOWN) ---
        $chartAir = [
            'type' => 'line',
            'data' => [
                'labels' => $stats->pluck('label')->toArray(),
                'datasets' => [
                    ['label' => 'S1 (PPM)', 'data' => $stats->pluck('s1')->map(fn($v) => round($v, 1))->toArray(), 'borderColor' => '#00d4ff', 'fill' => false, 'borderWidth' => 1.5, 'pointRadius' => 0],
                    ['label' => 'S2 (PPM)', 'data' => $stats->pluck('s2')->map(fn($v) => round($v, 1))->toArray(), 'borderColor' => '#ff0055', 'fill' => false, 'borderWidth' => 1.5, 'pointRadius' => 0],
                    ['label' => 'S3 (PPM)', 'data' => $stats->pluck('s3')->map(fn($v) => round($v, 1))->toArray(), 'borderColor' => '#ffcc00', 'fill' => false, 'borderWidth' => 1.5, 'pointRadius' => 0],
                    ['label' => 'TOTAL (MAX)', 'data' => $stats->pluck('smoke_max')->toArray(), 'borderColor' => '#00ff00', 'backgroundColor' => 'rgba(0,255,0,0.1)', 'fill' => true, 'borderWidth' => 3, 'pointRadius' => 0]
                ]
            ],
            'options' => [
                'title' => ['display' => true, 'text' => 'AIR QUALITY ANALYTICS (PPM)'],
                'elements' => ['line' => ['tension' => 0.4]]
            ]
        ];

        // --- CHART 2: TEMP & HUMIDITY ---
        $chartEnv = [
            'type' => 'line',
            'data' => [
                'labels' => $stats->pluck('label')->toArray(),
                'datasets' => [
                    ['label' => 'Temp (°C)', 'data' => $stats->pluck('temp')->map(fn($v) => round($v, 1))->toArray(), 'borderColor' => '#ff4400', 'yAxisID' => 'y', 'fill' => false],
                    ['label' => 'Humidity (%)', 'data' => $stats->pluck('hum')->map(fn($v) => round($v, 1))->toArray(), 'borderColor' => '#0088ff', 'yAxisID' => 'y1', 'fill' => false]
                ]
            ],
            'options' => [
                'title' => ['display' => true, 'text' => 'ENVIRONMENTAL TRENDS'],
                'scales' => [
                    'y' => ['type' => 'linear', 'display' => true, 'position' => 'left'],
                    'y1' => ['type' => 'linear', 'display' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]]
                ]
            ]
        ];

        $chartSrc1 = null;
        $chartSrc2 = null;

        try {
            $client = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(25);

            // Fetch Air Chart
            $res1 = $client->post('https://quickchart.io/chart', ['chart' => $chartAir, 'width' => 700, 'height' => 250, 'backgroundColor' => 'white']);
            if ($res1->successful())
                $chartSrc1 = 'data:image/png;base64,' . base64_encode($res1->body());

            // Fetch Env Chart
            $res2 = $client->post('https://quickchart.io/chart', ['chart' => $chartEnv, 'width' => 700, 'height' => 250, 'backgroundColor' => 'white']);
            if ($res2->successful())
                $chartSrc2 = 'data:image/png;base64,' . base64_encode($res2->body());

        } catch (\Exception $e) {
            \Log::error("QuickChart Multi-Chart Fail: " . $e->getMessage());
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sensor_pdf', [
            'stats' => $stats,
            'overall' => $overallStats,
            'totalRecords' => $totalRecords,
            'periode_label' => $label,
            'chartSrc1' => $chartSrc1,
            'chartSrc2' => $chartSrc2,
            'tLimit' => $tLimit,
            'sLimit' => $sLimit
        ])->setPaper('a4', 'portrait');

        $filename = "Report_PDF_NOC_Inkasa" . now()->format('Ymd_His') . ".pdf";
        return $pdf->download($filename);
    }
}