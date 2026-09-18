@php
    $o = $ctx['overall'];
    $b = $ctx['breach'];
    $tLimit = $ctx['tLimit'];
    $sLimit = $ctx['sLimit'];
    $critical = (int) $o['critical_count'];
    $verdictColor = $critical > 0 ? '#c62828' : '#2e7d32';
    $verdictBg    = $critical > 0 ? '#ffebee' : '#e8f5e9';
    $verdictText  = $critical > 0
        ? 'ATTENTION REQUIRED — Anomali terdeteksi selama periode monitoring'
        : 'SISTEM DALAM PARAMETER NORMAL — Seluruh sensor beroperasi stabil';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>NOC Monitoring Report — {{ $ctx['periode'] }}</title>
    <style>
        @page { margin: 100px 32px 40px 32px; }
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; line-height: 1.45; }

        /* Header + Footer */
        header {
            position: fixed; top: -80px; left: 0; right: 0; height: 70px;
            border-bottom: 2px solid #004d60; padding-bottom: 4px;
        }
        header table { width: 100%; }
        header td { padding: 0; vertical-align: middle; border: none; }
        header .logo { width: 60px; }
        header .logo img { width: 55px; }
        header .brand { padding-left: 12px; }
        header .brand .name { color: #004d60; font-size: 14px; font-weight: bold; }
        header .brand .addr { color: #666; font-size: 9px; }
        header .doc-meta { text-align: right; font-size: 9px; color: #666; }
        header .doc-meta .doc-title { font-size: 11px; font-weight: bold; color: #004d60; text-transform: uppercase; letter-spacing: 1px; }

        footer {
            position: fixed; bottom: -25px; left: 0; right: 0; height: 20px;
            font-size: 8px; color: #999;
            border-top: 1px solid #ddd; padding-top: 4px;
        }
        footer table { width: 100%; }
        footer td { border: none; padding: 0; }
        footer .right { text-align: right; }
        .page-num:after { content: "Hal. " counter(page); }

        /* Title bar & subtitle */
        .report-title {
            background: linear-gradient(135deg, #004d60, #006680);
            color: white; padding: 14px 18px; border-radius: 6px;
            margin-bottom: 14px;
        }
        .report-title h1 { margin: 0; font-size: 17px; letter-spacing: 2px; text-transform: uppercase; }
        .report-title .sub { margin-top: 3px; font-size: 10px; opacity: .9; letter-spacing: 1px; }

        /* Section heading */
        .sec-title {
            font-size: 12px; font-weight: bold; color: #004d60;
            border-left: 3px solid #004d60; padding: 4px 0 4px 8px;
            margin: 14px 0 8px; text-transform: uppercase; letter-spacing: 1px;
        }

        /* KPI grid */
        table.kpi-grid { width: 100%; border-collapse: separate; border-spacing: 5px; }
        table.kpi-grid td {
            width: 16.66%; padding: 10px 8px; border-radius: 5px;
            background: #f5f7fa; border: 1px solid #e0e0e0;
            text-align: center; vertical-align: middle;
        }
        table.kpi-grid .kpi-lab { font-size: 7px; letter-spacing: 1.5px; color: #666; text-transform: uppercase; margin-bottom: 3px; font-weight: bold; }
        table.kpi-grid .kpi-val { font-size: 13px; font-weight: bold; letter-spacing: .5px; }

        /* Verdict box */
        .verdict {
            padding: 12px 14px; margin-top: 10px; border-radius: 6px;
            background: {{ $verdictBg }}; border: 1.5px solid {{ $verdictColor }};
            text-align: center; font-weight: bold; color: {{ $verdictColor }};
            font-size: 11px; letter-spacing: .5px;
        }

        /* Info bar */
        .meta-bar {
            background: #f0f4f7; border-left: 3px solid #004d60;
            padding: 8px 12px; border-radius: 4px; margin-bottom: 12px;
            font-size: 10px;
        }
        .meta-bar table { width: 100%; }
        .meta-bar td { padding: 2px 0; border: none; }
        .meta-bar .label { color: #666; font-weight: bold; width: 25%; }
        .meta-bar .value { color: #333; }

        /* Chart container */
        .chart-box {
            border: 1px solid #d0d7dc; border-radius: 6px;
            padding: 8px; margin-bottom: 12px;
            text-align: center; background: #fafbfc;
        }
        .chart-box h3 { margin: 0 0 6px; font-size: 10px; color: #004d60; letter-spacing: 1px; text-transform: uppercase; }
        .chart-box img { width: 100%; max-height: 240px; object-fit: contain; }
        .chart-half-row td { width: 50%; vertical-align: top; padding: 0 3px; border: none; }
        .chart-half-row .chart-box img { max-height: 200px; }

        /* Detail table */
        table.data-table {
            width: 100%; border-collapse: collapse; margin-top: 6px;
            font-size: 8.5px;
        }
        table.data-table th {
            background: #004d60; color: white; padding: 5px 4px;
            text-transform: uppercase; letter-spacing: .5px; font-size: 8px;
            border: 1px solid #003940;
        }
        table.data-table td {
            padding: 4px; border: 1px solid #e0e0e0; text-align: center;
        }
        table.data-table tr.critical td { background: #ffebee; color: #c62828; font-weight: bold; }
        table.data-table tr.warn td { background: #fff3e0; }

        /* Threshold summary compact */
        table.breach-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.breach-table td {
            padding: 8px 10px; border: 1px solid #e0e0e0;
            font-size: 10px;
        }
        table.breach-table .lab { background: #f5f5f5; font-weight: bold; width: 35%; }
        table.breach-table .val { font-weight: bold; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 3px;
            font-size: 9px; font-weight: bold; letter-spacing: .5px;
        }
        .badge-danger  { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .badge-warn    { background: #fff3e0; color: #ef6c00; border: 1px solid #ffb74d; }
        .badge-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .badge-info    { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }

        .page-break { page-break-after: always; }
        .avoid-break { page-break-inside: avoid; }

        /* Executive summary paragraph */
        .exec-summary {
            background: #fff; border: 1px solid #e0e0e0; border-left: 3px solid #004d60;
            padding: 10px 14px; margin-bottom: 12px; font-size: 10px; line-height: 1.55;
        }
        .exec-summary strong { color: #004d60; }
    </style>
</head>
<body>

<header>
    <table>
        <tr>
            <td class="logo"><img src="{{ public_path('favicon.png') }}" alt="Logo"></td>
            <td class="brand">
                <div class="name">PT. INKASA JAYA ALUMINIUM</div>
                <div class="addr">Jl. Raya Winong Km 1,5, Pasuruan · Jawa Timur, Indonesia 61254</div>
            </td>
            <td class="doc-meta">
                <div class="doc-title">NOC Monitoring Report</div>
                <div>{{ $now->format('d M Y H:i') }} WIB</div>
            </td>
        </tr>
    </table>
</header>

<footer>
    <table>
        <tr>
            <td>NOC Command Center · IoT Server Inkasa · Confidential</td>
            <td class="right"><span class="page-num"></span></td>
        </tr>
    </table>
</footer>

{{-- ============= PAGE 1 — EXECUTIVE SUMMARY ============= --}}
<div class="report-title">
    <h1>Ringkasan Eksekutif</h1>
    <div class="sub">{{ strtoupper($ctx['periode']) }}</div>
</div>

<div class="meta-bar">
    <table>
        <tr>
            <td class="label">Total Record</td>
            <td class="value">{{ number_format($ctx['totalRecords']) }} pembacaan sensor</td>
            <td class="label">Interval Agregasi</td>
            <td class="value">{{ $ctx['aggLabel'] }}</td>
        </tr>
        <tr>
            <td class="label">Grouping Rekap</td>
            <td class="value">{{ $ctx['groupLabel'] }}</td>
            <td class="label">Batas Alarm</td>
            <td class="value">Suhu &gt; {{ $tLimit }}°C · Asap &gt; {{ $sLimit }} PPM</td>
        </tr>
    </table>
</div>

<div class="sec-title">Key Performance Indicators</div>
<table class="kpi-grid">
    <tr>
        <td>
            <div class="kpi-lab">MAX TEMP</div>
            <div class="kpi-val" style="color:#e65100">{{ number_format($o['max_temp'], 1) }} °C</div>
        </td>
        <td>
            <div class="kpi-lab">MIN TEMP</div>
            <div class="kpi-val" style="color:#0277bd">{{ number_format($o['min_temp'], 1) }} °C</div>
        </td>
        <td>
            <div class="kpi-lab">AVG TEMP</div>
            <div class="kpi-val" style="color:#00838f">{{ number_format($o['avg_temp'], 1) }} °C</div>
        </td>
        <td>
            <div class="kpi-lab">AVG HUM</div>
            <div class="kpi-val" style="color:#00695c">{{ number_format($o['avg_hum'], 1) }} %</div>
        </td>
        <td>
            <div class="kpi-lab">MAX SMOKE</div>
            <div class="kpi-val" style="color:#c62828">{{ number_format($o['max_smoke']) }} PPM</div>
        </td>
        <td>
            <div class="kpi-lab">INSIDEN</div>
            <div class="kpi-val" style="color:#d84315">{{ number_format($critical) }}</div>
        </td>
    </tr>
</table>

<div class="verdict">
    {!! $critical > 0 ? '⚠  ' . $verdictText : '✓  ' . $verdictText !!}
</div>

<div class="sec-title">Ringkasan Naratif</div>
<div class="exec-summary">
    Selama periode <strong>{{ $ctx['periode'] }}</strong>, sistem NOC merekam total
    <strong>{{ number_format($ctx['totalRecords']) }}</strong> pembacaan dari sensor multi-titik
    (3 sensor asap MQ + 2 sensor api). Rentang suhu tercatat antara
    <strong>{{ number_format($o['min_temp'], 1) }}°C</strong> hingga
    <strong>{{ number_format($o['max_temp'], 1) }}°C</strong> (rata-rata
    <strong>{{ number_format($o['avg_temp'], 1) }}°C</strong>), dengan kelembaban rata-rata
    <strong>{{ number_format($o['avg_hum'], 1) }}%</strong>. Puncak konsentrasi asap
    tertinggi mencapai <strong>{{ number_format($o['max_smoke']) }} PPM</strong>
    (batas alarm {{ $sLimit }} PPM). Sensor api {{ $o['fired'] ? 'terdeteksi TRIGGER pada periode ini' : 'tidak trigger sama sekali' }}.

    @if($critical > 0)
        Sebanyak <strong>{{ number_format($critical) }} insiden</strong>
        ({{ $b['temp'] }} pelanggaran batas suhu, {{ $b['smoke'] }} pelanggaran batas asap,
        {{ $b['fire'] }} deteksi api) tercatat dan memerlukan review manual.
        Persentase waktu sistem dalam kondisi tidak-normal:
        <strong>{{ round(($critical / max(1, $ctx['totalRecords'])) * 100, 2) }}%</strong>.
    @else
        Tidak ada pelanggaran ambang batas selama periode ini.
        Semua parameter beroperasi dalam batas aman.
    @endif
</div>

<div class="sec-title">Analisis Pelanggaran Ambang Batas</div>
<table class="breach-table">
    <tr>
        <td class="lab">Pelanggaran Suhu (&gt; {{ $tLimit }}°C)</td>
        <td class="val">{{ number_format($b['temp']) }} event</td>
        <td>{{ $b['temp_pct'] }}% dari total</td>
        <td>
            @if($b['temp'] === 0) <span class="badge badge-success">Bersih</span>
            @elseif($b['temp_pct'] < 1) <span class="badge badge-info">Minor</span>
            @elseif($b['temp_pct'] < 5) <span class="badge badge-warn">Perhatian</span>
            @else <span class="badge badge-danger">Kritis</span>
            @endif
        </td>
    </tr>
    <tr>
        <td class="lab">Pelanggaran Asap (&gt; {{ $sLimit }} PPM)</td>
        <td class="val">{{ number_format($b['smoke']) }} event</td>
        <td>{{ $b['smoke_pct'] }}% dari total</td>
        <td>
            @if($b['smoke'] === 0) <span class="badge badge-success">Bersih</span>
            @elseif($b['smoke_pct'] < 1) <span class="badge badge-info">Minor</span>
            @elseif($b['smoke_pct'] < 5) <span class="badge badge-warn">Perhatian</span>
            @else <span class="badge badge-danger">Kritis</span>
            @endif
        </td>
    </tr>
    <tr>
        <td class="lab">Deteksi Api (Flame)</td>
        <td class="val">{{ number_format($b['fire']) }} event</td>
        <td>{{ $b['fire_pct'] }}% dari total</td>
        <td>
            @if($b['fire'] === 0) <span class="badge badge-success">Bersih</span>
            @else <span class="badge badge-danger">Trigger</span>
            @endif
        </td>
    </tr>
</table>

<div class="page-break"></div>

{{-- ============= PAGE 2 — TREND CHARTS ============= --}}
<div class="report-title">
    <h1>Analisis Tren Sensor</h1>
    <div class="sub">Grafik rekap {{ $ctx['groupLabel'] }} · {{ strtoupper($ctx['periode']) }}</div>
</div>

@if(!empty($charts['env']))
<div class="chart-box avoid-break">
    <h3>Grafik 1 — Trend Suhu & Kelembaban</h3>
    <img src="{{ $charts['env'] }}" alt="Environment Trend">
</div>
@endif

@if(!empty($charts['air']))
<div class="chart-box avoid-break">
    <h3>Grafik 2 — Kualitas Udara Multi Sensor</h3>
    <img src="{{ $charts['air'] }}" alt="Air Quality Trend">
</div>
@endif

<table class="chart-half-row" style="width:100%; border-collapse:collapse; margin-top:4px;">
    <tr>
        @if(!empty($charts['breach']))
        <td>
            <div class="chart-box avoid-break">
                <h3>Grafik 3 — Pelanggaran Ambang</h3>
                <img src="{{ $charts['breach'] }}" alt="Breach Bar">
            </div>
        </td>
        @endif
        @if(!empty($charts['critical']))
        <td>
            <div class="chart-box avoid-break">
                <h3>Grafik 4 — Distribusi Insiden</h3>
                <img src="{{ $charts['critical'] }}" alt="Critical Distribution">
            </div>
        </td>
        @endif
    </tr>
</table>

<div class="page-break"></div>

{{-- ============= PAGE 3 — REKAP PER PERIODE ============= --}}
<div class="report-title">
    <h1>Rekap {{ $ctx['groupLabel'] }}</h1>
    <div class="sub">Statistik per {{ strtolower($ctx['groupLabel']) }} · {{ strtoupper($ctx['periode']) }}</div>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:20%">Periode</th>
            <th>Avg Temp</th>
            <th>Max Temp</th>
            <th>Avg Hum</th>
            <th>Avg Smoke</th>
            <th>Max Smoke</th>
            <th>Flame</th>
            <th>Insiden</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ctx['hourly'] as $i => $r)
            @php
                $rowCritical = (int) $r->critical_count > 0;
                $rowWarn = !$rowCritical && ((float) $r->max_temp > $tLimit * 0.9 || (int) $r->max_smoke > $sLimit * 0.8);
                $status = $rowCritical ? 'CRITICAL' : ($rowWarn ? 'WARNING' : 'STABLE');
                $badge = $rowCritical ? 'badge-danger' : ($rowWarn ? 'badge-warn' : 'badge-success');
            @endphp
            <tr class="{{ $rowCritical ? 'critical' : ($rowWarn ? 'warn' : '') }}">
                <td>{{ $i + 1 }}</td>
                <td style="text-align:left; font-family:'Courier New',monospace;">{{ $r->label }}</td>
                <td>{{ number_format((float) $r->avg_temp, 1) }}</td>
                <td>{{ number_format((float) $r->max_temp, 1) }}</td>
                <td>{{ number_format((float) $r->avg_hum, 1) }}</td>
                <td>{{ (int) round((float) $r->avg_smoke) }}</td>
                <td>{{ (int) $r->max_smoke }}</td>
                <td>{{ (int) $r->flame_count }}</td>
                <td>{{ (int) $r->critical_count }}</td>
                <td><span class="badge {{ $badge }}">{{ $status }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- ============= PAGE 4 — LOG INSIDEN ============= --}}
@if(count($ctx['incidents']) > 0)
<div class="page-break"></div>
<div class="report-title">
    <h1>Log Insiden Terperinci</h1>
    <div class="sub">Semua kejadian yang melewati ambang batas · Max 200 kejadian teratas</div>
</div>

<div class="exec-summary">
    Tabel berikut mencantumkan <strong>{{ count($ctx['incidents']) }}</strong> insiden teratas
    (diurutkan dari fire terlebih dahulu, lalu asap tertinggi, lalu suhu tertinggi).
    Data lengkap semua insiden dapat dilihat di sheet <em>"Log Insiden"</em> pada laporan Excel.
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:16%">Timestamp</th>
            <th>Suhu (°C)</th>
            <th>Asap (PPM)</th>
            <th>Flame</th>
            <th style="width:40%">Penyebab</th>
            <th>Keparahan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ctx['incidents'] as $i => $r)
            @php
                $temp = (float) $r->temp;
                $smoke = (int) $r->smoke;
                $fire = (int) $r->fire === 1;
                $reasons = [];
                if ($temp > $tLimit)  $reasons[] = 'Suhu ' . number_format($temp, 1) . '°C > ' . $tLimit;
                if ($smoke > $sLimit) $reasons[] = 'Asap ' . $smoke . ' > ' . $sLimit;
                if ($fire)            $reasons[] = 'Flame trigger';
                $severity = $fire ? 'DANGER' : ($temp > $tLimit * 1.15 || $smoke > $sLimit * 1.5 ? 'HIGH' : 'MEDIUM');
                $sevBadge = $fire || $severity === 'DANGER' ? 'badge-danger'
                    : ($severity === 'HIGH' ? 'badge-warn' : 'badge-info');
                $ts = is_string($r->created_at) ? \Carbon\Carbon::parse($r->created_at) : $r->created_at;
            @endphp
            <tr class="{{ $fire || $severity === 'DANGER' ? 'critical' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td style="font-family:'Courier New',monospace;">{{ $ts->format('d/m/Y H:i:s') }}</td>
                <td>{{ number_format($temp, 1) }}</td>
                <td>{{ $smoke }}</td>
                <td>{{ $fire ? 'FIRE' : '-' }}</td>
                <td style="text-align:left; font-size:8.5px;">{{ implode(' · ', $reasons) }}</td>
                <td><span class="badge {{ $sevBadge }}">{{ $severity }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
