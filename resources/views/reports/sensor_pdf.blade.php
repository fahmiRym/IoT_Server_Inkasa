@php
    $tLimit = env('SENSOR_TEMP_LIMIT', 35);
    $sLimit = env('SENSOR_SMOKE_LIMIT', 1000);
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>NOC Monitoring Report</title>
    <style>
        @page {
            margin: 110px 40px 50px 40px;
        }

        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 2px solid #004d60;
            padding-bottom: 5px;
        }

        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 20px;
            text-align: right;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .page-number:after {
            content: "Page " counter(page);
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border: none;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .logo-box {
            width: 70px;
            text-align: center;
        }

        .logo-box img {
            width: 70px;
            height: auto;
        }

        .company-info {
            font-size: 10px;
            color: #444;
        }

        .company-info b {
            font-size: 13px;
            color: #004d60;
        }

        .report-title-section {
            text-align: center;
            margin-bottom: 10px;
        }

        .report-title-section h1 {
            margin: 0;
            font-size: 18px;
            color: #004d60;
            text-transform: uppercase;
        }

        .report-title-section p {
            margin: 5px 0;
            font-weight: bold;
            font-size: 12px;
            color: #666;
        }

        .info-bar {
            background: #eee;
            padding: 8px 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .info-bar table {
            width: 100%;
            border: none;
        }

        .info-bar td {
            border: none;
            padding: 0;
            font-size: 11px;
        }

        .chart-container {
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #ccc;
            padding: 8px;
            border-radius: 5px;
        }

        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 5px;
            color: #004d60;
            font-size: 12px;
        }

        .chart-container img {
            width: 100%;
            max-height: 180px;
            object-fit: contain;
        }

        .summary-box {
            margin-top: 15px;
            padding: 15px;
            border: 2px solid #004d60;
            background: #f0f7f8;
            border-radius: 8px;
            page-break-inside: avoid;
        }

        .summary-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 10px;
            color: #004d60;
            border-bottom: 1px solid #004d60;
            display: inline-block;
            padding-bottom: 3px;
        }

        table.summary-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }

        table.summary-table td {
            border: none;
            padding: 4px 0;
            font-size: 11px;
            vertical-align: middle;
        }

        table.summary-table td.label {
            width: 40%;
            font-weight: bold;
        }

        table.summary-table td.value {
            width: 60%;
        }

        .badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-danger {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
    </style>
</head>

<body>
    <header>
        <table class="header-table">
            <tr>
                <td width="15%">
                    <div class="logo-box">
                        <img src="{{ public_path('favicon.png') }}" alt="Logo">
                    </div>
                </td>
                <td width="85%" class="company-info" style="text-align: left; padding-left: 15px; padding-top: 5px;">
                    <b>PT. INKASA JAYA ALUMINIUM</b><br>
                    Jl. Raya Winong Km 1,5, Pasuruan<br>
                    Jawa Timur, Indonesia 61254
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <span class="page-number"></span>
    </footer>

    <div class="report-title-section">
        <h1>NOC COMMAND CENTER REPORT</h1>
        <p>{{ strtoupper($periode_label) }}</p>
    </div>

    <div class="info-bar">
        <table>
            <tr>
                <td><strong>Print Date:</strong> {{ now()->format('d M Y H:i:s') }}</td>
                <td style="text-align: right;"><strong>Total Data Analyzed:</strong>
                    {{ number_format($totalRecords) }} records</td>
            </tr>
        </table>
    </div>

    @if(!empty($chartSrc1))
        <div class="chart-container">
            <h3>CHART 1: AIR QUALITY SENSOR BREAKDOWN (S1, S2, S3)</h3>
            <img src="{{ $chartSrc1 }}" alt="Air Quality Chart">
        </div>
    @endif

    @if(!empty($chartSrc2))
        <div class="chart-container">
            <h3>CHART 2: TEMPERATURE & HUMIDITY TRENDS</h3>
            <img src="{{ $chartSrc2 }}" alt="Environment Chart">
        </div>
    @endif

    <div class="summary-box">
        <div class="summary-title">ENVIRONMENTAL ANALYTICS SUMMARY</div>
        <table class="summary-table">
            <tr>
                <td class="label">Max Recorded Temp</td>
                <td class="value">: <span
                        style="font-size: 12px; font-weight: bold; color: #e65100;">{{ number_format($overall['max_temp'], 1) }}
                        °C</span></td>
            </tr>
            <tr>
                <td class="label">Min Recorded Temp</td>
                <td class="value">: {{ number_format($overall['min_temp'], 1) }} °C</td>
            </tr>
            <tr>
                <td class="label">Avg Humidity Level</td>
                <td class="value">: {{ number_format($overall['avg_hum'], 1) }} %</td>
            </tr>
            <tr>
                <td class="label">Peak Smoke S1 / S2 / S3</td>
                <td class="value">: {{ $overall['max_s1'] }} / {{ $overall['max_s2'] }} / {{ $overall['max_s3'] }} PPM
                </td>
            </tr>
            <tr>
                <td class="label">Overall Max Smoke</td>
                <td class="value">: <span style="font-weight: bold;">{{ number_format($overall['max_smoke']) }}
                        PPM</span></td>
            </tr>
            <tr>
                <td class="label">Fire/Flame Detection</td>
                <td class="value">:
                    @if($overall['fired'])
                        <span class="badge badge-danger">🔥 DETECTED (POSITIVE)</span>
                    @else
                        <span class="badge badge-success">✅ NONE (NEGATIVE)</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Total Critical Incidents</td>
                <td class="value">: <span style="font-weight: bold;">{{ number_format($overall['critical_count']) }}
                        Occurrences</span></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 10px;">
                    <div style="background: {{ $overall['critical_count'] > 0 ? '#ffebee' : '#e8f5e9' }}; 
                                border: 1px solid {{ $overall['critical_count'] > 0 ? '#ef9a9a' : '#a5d6a7' }}; 
                                padding: 8px; border-radius: 4px; text-align: center;">
                        <strong
                            style="color: {{ $overall['critical_count'] > 0 ? '#c62828' : '#2e7d32' }}; font-size: 11px;">
                            FINAL ASSESSMENT:
                            {{ $overall['critical_count'] > 0 ? 'ATTENTION REQUIRED - ANOMALIES DETECTED' : 'SYSTEM OPERATING WITHIN STABLE PARAMETERS' }}
                        </strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>