<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center - Ultimate CORE</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-deep: #020408;
            --panel: rgba(10, 15, 25, 0.7);
            --border: rgba(0, 243, 255, 0.15);
            --cyan: #00f3ff;
            --pink: #ff0055;
            --green: #0aff00;
            --yellow: #ffcc00;
            --text-vibrant: #e6f1f8;
            --text-dim: #708090;
            --accent-glow: rgba(0, 243, 255, 0.3);
        }

        * {
            cursor: crosshair;
        }

        body {
            background-color: var(--bg-deep);
            color: var(--text-vibrant);
            font-family: 'Rajdhani', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            background-image:
                radial-gradient(circle at 5% 5%, rgba(0, 243, 255, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 95% 95%, rgba(255, 0, 85, 0.1) 0%, transparent 40%),
                url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
        }

        /* --- AMBIENT DECOR --- */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(var(--border) 1px, transparent 1px), linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: -1;
            opacity: 0.15;
            mask-image: radial-gradient(circle, black, transparent 80%);
        }

        /* --- CUSTOM SCROLLBAR --- */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-deep);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--cyan);
        }

        .container {
            width: 100%;
            max-width: 1800px;
            margin: 0 auto;
            padding: 15px;
            display: grid;
            grid-template-columns: 300px minmax(0, 1fr);
            gap: 15px;
            box-sizing: border-box;
            height: 100vh;
        }

        /* --- TOP HEADER --- */
        header {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 25px;
            background: linear-gradient(90deg, var(--panel), transparent);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .node-status-indicator {
            width: 40px;
            height: 40px;
            border: 2px solid var(--border);
            border-radius: 50%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 0, 85, 0.1);
            overflow: hidden;
        }

        .node-status-indicator img {
            width: 70%;
            height: 70%;
            object-fit: contain;
            filter: drop-shadow(0 0 5px var(--pink));
            animation: breath 2s infinite ease-in-out;
        }

        @keyframes breath {

            0%,
            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.1);
                opacity: 1;
            }
        }

        .brand h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.6rem;
            margin: 0;
            letter-spacing: 4px;
            text-transform: uppercase;
            font-weight: 900;
            color: #fff;
            text-shadow: 0 0 20px var(--accent-glow);
        }

        /* --- SIDEBAR --- */
        aside {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .glass-card {
            background: var(--panel);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 15px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .glass-card:hover {
            border-color: var(--cyan);
            transform: translateX(5px);
        }

        .card-title {
            font-family: 'Orbitron';
            font-size: 0.6rem;
            color: var(--cyan);
            letter-spacing: 1.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* --- SYSTEM HEALTH --- */
        .health-matrix {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 10px;
        }

        .matrix-item {
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 5px;
            border-radius: 6px;
        }

        .matrix-val {
            font-family: 'JetBrains Mono';
            font-size: 1rem;
            color: var(--cyan);
        }

        .matrix-lab {
            font-size: 0.55rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* --- METRICS --- */
        main {
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-width: 0;
        }

        .metrics-hud {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .metric-glow {
            padding: 15px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            position: relative;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            min-height: 280px;
        }

        .metric-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, transparent 40%, rgba(0, 243, 255, 0.05));
            pointer-events: none;
        }

        .val-large {
            font-family: 'Orbitron';
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1;
            margin: 10px 0;
        }

        .unit {
            font-size: 1rem;
            color: var(--text-dim);
            margin-left: 5px;
        }

        .stat-footer {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--border);
            padding-top: 10px;
            font-size: 0.8rem;
            margin-top: auto;
        }

        .mini-chart-box {
            flex: 1;
            width: 100%;
            min-height: 80px;
            margin: 10px 0;
            position: relative;
        }

        /* --- CHARTS --- */
        .charts-row {
            display: none;
        }

        /* Hidden as requested */
        .chart-box {
            height: 180px;
            position: relative;
        }

        .chart-full {
            height: 180px;
        }

        /* --- BUTTONS --- */
        .cyber-btn {
            width: 100%;
            background: rgba(0, 243, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--cyan);
            padding: 12px;
            border-radius: 8px;
            font-family: 'Orbitron';
            font-size: 0.75rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-bottom: 10px;
        }

        .cyber-btn:hover {
            background: var(--cyan);
            color: #000;
            box-shadow: 0 0 15px var(--cyan);
        }

        .cyber-btn.pdf {
            border-color: var(--pink);
            color: var(--pink);
        }

        .cyber-btn.pdf:hover {
            background: var(--pink);
            color: #fff;
            box-shadow: 0 0 15px var(--pink);
        }

        /* --- LOG TABLE --- */
        .table-wrap {
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
        }

        th {
            background: rgba(0, 243, 255, 0.05);
            padding: 12px;
            text-align: left;
            font-size: 0.75rem;
            color: var(--cyan);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-family: 'JetBrains Mono';
            font-size: 0.85rem;
        }

        .row-danger {
            background: rgba(255, 0, 85, 0.1);
        }

        /* --- ALERT OVERLAY --- */
        #alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 0, 85, 0.4);
            backdrop-filter: blur(10px);
            z-index: 9999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: flash red 1s infinite alternate;
        }

        @keyframes flash-red {
            from {
                background: rgba(255, 0, 85, 0.4);
            }

            to {
                background: rgba(255, 0, 85, 0.7);
            }
        }

        .alert-box {
            background: #000;
            border: 2px solid var(--pink);
            padding: 40px;
            text-align: center;
            box-shadow: 0 0 50px var(--pink);
            border-radius: 20px;
        }

        .alert-box h2 {
            font-family: 'Orbitron';
            color: var(--pink);
            font-size: 3rem;
            margin: 0;
        }

        .alert-box p {
            font-size: 1.5rem;
            margin: 20px 0;
        }

        /* --- BADGES --- */
        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-secure {
            background: rgba(10, 255, 0, 0.2);
            color: var(--green);
            border: 1px solid var(--green);
        }

        .badge-danger {
            background: rgba(255, 0, 85, 0.2);
            color: var(--pink);
            border: 1px solid var(--pink);
        }

        /* --- ANIMATIONS --- */
        .glow-text {
            text-shadow: 0 0 10px currentColor;
        }

        .moving-grid {
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            from {
                background-position: 0 0;
            }

            to {
                background-position: 50px 50px;
            }
        }

        .sub-val {
            font-size: 0.65rem;
            color: var(--text-dim);
            font-family: 'JetBrains Mono';
        }

        .node-badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 0.6rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-right: 2px;
        }

        .node-badge.active {
            color: var(--pink);
            border-color: var(--pink);
            background: rgba(255, 0, 85, 0.1);
        }

        /* --- FUTURISTIC INPUTS --- */
        input[type="date"],
        input[type="month"],
        select {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border);
            color: var(--text-vibrant);
            font-family: 'JetBrains Mono';
            font-size: 0.75rem;
            padding: 8px;
            border-radius: 6px;
            width: 100%;
            outline: none;
            transition: 0.3s;
        }

        input:focus,
        select:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 10px var(--accent-glow);
        }

        ::-webkit-calendar-picker-indicator {
            filter: invert(1) hue-rotate(180deg) brightness(1.5);
            cursor: pointer;
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background: var(--green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--green);
            animation: pulse-green 1.5s infinite;
        }

        @keyframes pulse-green {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                opacity: 1;
            }
        }

        /* Scanning Line Effect */
        .glass-card::before {
            content: '';
            position: absolute;
            top: -100%;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(to bottom, transparent, rgba(0, 243, 255, 0.05), transparent);
            animation: scan 6s linear infinite;
            pointer-events: none;
        }

        @keyframes scan {
            from {
                top: -100%;
            }

            to {
                top: 200%;
            }
        }

        .label-caps {
            font-size: 0.6rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <div class="grid-overlay moving-grid"></div>

    <div id="alert-overlay">
        <div class="alert-box">
            <i class="fas fa-exclamation-triangle"
                style="font-size: 5rem; color: var(--pink); margin-bottom: 20px;"></i>
            <h2>SYSTEM CRITICAL</h2>
            <p id="alert-msg">HIGH VALUES DETECTED</p>
            <button onclick="dismissAlert()" class="cyber-btn"
                style="border-color: var(--pink); color: var(--pink); width: 200px; margin: 0 auto;">DISMISS
                ALARM</button>
        </div>
    </div>

    <div class="container">
        <header>
            <div class="brand-container">
                <div class="node-status-indicator">
                    <img src="{{ asset('favicon.png') }}" alt="Logo">
                </div>
                <div class="brand">
                    <h1>NOC COMMAND INKASA</h1>
                    <div
                        style="color: var(--cyan); font-family: 'JetBrains Mono'; font-size: 0.7rem; opacity: 0.6; display: flex; gap: 10px;">
                        <span>SYS_VER: 2.1.ULTIMATE</span>
                        <span>PROTO: HTTP_REST</span>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div id="clock" style="font-size: 2.8rem; font-family: 'Orbitron'; font-weight: 700;">00:00:00</div>
                <div id="date" style="color: var(--cyan); letter-spacing: 3px; font-weight: 600;">...</div>
            </div>
        </header>

        <aside>
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="glass-card" style="border-color: var(--green); margin-bottom: 10px;">
                    <span style="color: var(--green); font-family: 'JetBrains Mono'; font-size: 0.8rem;">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </span>
                </div>
            @endif
            @if(session('error'))
                <div class="glass-card" style="border-color: var(--pink); margin-bottom: 10px;">
                    <span style="color: var(--pink); font-family: 'JetBrains Mono'; font-size: 0.8rem;">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </span>
                </div>
            @endif

            <div class="glass-card">
                <span class="card-title">SYSTEM MATRIX</span>
                <div id="status-hero" style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
                    <div class="pulse-dot" id="main-status-dot"></div>
                    <div>
                        <div id="conn-text"
                            style="font-weight: 900; font-size: 1.2rem; letter-spacing: 2px; color: var(--green); font-family: 'Orbitron';">
                            CORE ACTIVE</div>
                        <div style="display: flex; gap: 10px; margin-top: 4px;">
                            <span class="sub-val">UPTIME: <span id="uptime-val"
                                    style="color:#fff">--:--:--</span></span>
                            <span class="sub-val">SYNC: <span id="sync-val" style="color:var(--cyan)">100%</span></span>
                        </div>
                    </div>
                </div>
                <div class="health-matrix">
                    <div class="matrix-item">
                        <div class="matrix-val" id="total-logs">0</div>
                        <div class="matrix-lab">TOTAL_LOGS</div>
                    </div>
                    <div class="matrix-item">
                        <div class="matrix-val" style="color:var(--yellow); font-size: 0.8rem;" id="last-fetch">--:--:--
                        </div>
                        <div class="matrix-lab">LAST_FETCH</div>
                    </div>
                    <div class="matrix-item" style="grid-column: span 2; border-color: var(--yellow);">
                        <div class="matrix-val" style="font-size: 0.75rem; color: var(--yellow); letter-spacing: 1px;">
                            PACK_V2_ACTIVE</div>
                        <div class="matrix-lab">PROTOCOL_VERSION</div>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <span class="card-title">REPORT GENERATOR</span>
                <form id="reportForm" action="{{ route('export.csv') }}" method="GET">
                    <div style="margin-bottom: 15px;">
                        <label class="label-caps"><i class="fas fa-calendar-alt" style="margin-right:5px"></i> DATE
                            RANGE SELECTION</label>
                        <div style="display: flex; gap: 8px; margin-top: 8px;">
                            <input type="date" name="start_date" placeholder="START">
                            <input type="date" name="end_date" placeholder="END">
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label class="label-caps"><i class="fas fa-layer-group" style="margin-right:5px"></i> MONTH
                            SELECTION</label>
                        <div style="margin-top: 8px;">
                            <input type="month" name="month">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label class="label-caps"><i class="fas fa-archive" style="margin-right:5px"></i> YEARLY
                            ARCHIVE</label>
                        <div style="margin-top: 8px;">
                            <select name="year">
                                <option value="">-- CURRENT YEAR --</option>
                                @for($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <button type="submit" class="cyber-btn" onclick="setExportFormat('excel')">
                            <i class="fas fa-file-excel"></i> EXCEL
                        </button>

                        <button type="button" class="cyber-btn pdf" onclick="setExportFormat('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </form>
            </div>

            <div class="glass-card">
                <span class="card-title">DATABASE MANAGEMENT</span>
                <div style="margin-bottom: 15px;">
                    <label class="label-caps"><i class="fas fa-download" style="margin-right:5px"></i> EXPORT
                        SQL</label>
                    <a href="{{ route('db.export') }}" class="cyber-btn" style="margin-top: 8px;">
                        <i class="fas fa-database"></i> DOWNLOAD BACKUP
                    </a>
                </div>
                <div>
                    <label class="label-caps"><i class="fas fa-upload" style="margin-right:5px"></i> RESTORE SQL</label>
                    <form action="{{ route('db.import') }}" method="POST" enctype="multipart/form-data"
                        style="margin-top: 8px;">
                        @csrf
                        <input type="file" name="backup_file" accept=".sql" style="margin-bottom: 10px;">
                        <button type="submit" class="cyber-btn pdf">
                            <i class="fas fa-file-import"></i> UPLOAD & RESTORE
                        </button>
                    </form>
                </div>
            </div>

            <div class="glass-card" style="border-bottom: 2px solid var(--border);">
                <span class="card-title">SECURITY HUD</span>
                <button onclick="toggleMute()" class="cyber-btn"
                    style="width: 100%; justify-content: space-between; padding: 15px; background: rgba(0, 243, 255, 0.03);">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-shield-alt" id="bell-icon"
                            style="color:var(--cyan); filter:drop-shadow(0 0 5px var(--cyan))"></i>
                        <span style="font-family:'Orbitron'; font-size:0.8rem">ALARM_MODE</span>
                    </div>
                    <span id="alarm-status"
                        style="font-family:'Orbitron'; color:var(--cyan); letter-spacing:1px;">ACTIVE</span>
                </button>
                <div id="safety-log"
                    style="font-size: 0.65rem; color: var(--text-dim); margin-top: 15px; font-family: 'JetBrains Mono'; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 6px; border-left: 2px solid var(--text-dim);">
                    > SYNCING_SECURITY_MOD...
                </div>
            </div>
        </aside>

        <main>
            <div class="metrics-hud">
                <!-- TEMPERATURE -->
                <div class="metric-glow" style="border-left: 4px solid var(--cyan);">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <span class="card-title" style="margin:0">ENVIRONMENT TEMP</span>
                        <i class="fas fa-thermometer-half"
                            style="color:var(--cyan); font-size:1.2rem; filter:drop-shadow(0 0 5px var(--cyan))"></i>
                    </div>
                    <div class="val-large" id="t-val">--<span class="unit">°C</span></div>
                    <div id="t-limit-text"
                        style="font-size: 0.65rem; color: var(--cyan); margin-bottom: 5px; font-family: 'JetBrains Mono'; opacity: 0.8; height: 12px;">
                        <!-- AUTO POPULATED -->
                    </div>
                    <div class="mini-chart-box">
                        <canvas id="chartTemp"></canvas>
                    </div>
                    <div class="stat-footer">
                        <span>HI: <span id="stat-max-temp" style="color:var(--cyan)">--</span></span>
                        <span>LOW: <span id="stat-min-temp" style="color:var(--text-dim)">--</span></span>
                    </div>
                </div>

                <!-- HUMIDITY -->
                <div class="metric-glow" style="border-left: 4px solid var(--yellow);">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <span class="card-title" style="margin:0">HUMIDITY SENSOR</span>
                        <i class="fas fa-droplet"
                            style="color:var(--yellow); font-size:1.2rem; filter:drop-shadow(0 0 5px var(--yellow))"></i>
                    </div>
                    <div class="val-large" id="h-val">--<span class="unit">%</span></div>
                    <div class="mini-chart-box">
                        <canvas id="chartHum"></canvas>
                    </div>
                    <div class="stat-footer">
                        <span>AVG: <span id="stat-avg-hum" style="color:var(--yellow)">--</span></span>
                        <span id="h-status" style="color:var(--green)">SECURE</span>
                    </div>
                </div>

                <!-- AIR QUALITY -->
                <div class="metric-glow" id="card-smoke" style="border-left: 4px solid var(--green);">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <span class="card-title" style="margin:0">AIR QUALITY (AQS)</span>
                        <i class="fas fa-wind"
                            style="color:var(--green); font-size:1.2rem; filter:drop-shadow(0 0 5px var(--green))"></i>
                    </div>
                    <div class="val-large" id="s-val">----<span class="unit">PPM</span></div>
                    <div
                        style="display: flex; gap: 5px; margin-top: -5px; margin-bottom: 5px; font-size: 0.6rem; font-family: 'JetBrains Mono'; opacity: 0.7;">
                        <div style="flex:1; border: 1px solid var(--border); padding: 2px; text-align:center;">S1:<span
                                id="s1-val">--</span></div>
                        <div style="flex:1; border: 1px solid var(--border); padding: 2px; text-align:center;">S2:<span
                                id="s2-val">--</span></div>
                        <div style="flex:1; border: 1px solid var(--border); padding: 2px; text-align:center;">S3:<span
                                id="s3-val">--</span></div>
                    </div>
                    <div id="s-limit-text"
                        style="font-size: 0.65rem; color: var(--green); margin-bottom: 5px; font-family: 'JetBrains Mono'; opacity: 0.8; height: 12px;">
                        <!-- AUTO POPULATED -->
                    </div>
                    <div class="mini-chart-box">
                        <canvas id="chartSmoke"></canvas>
                    </div>
                    <div class="stat-footer">
                        <span>MAX: <span id="stat-max-smoke" style="color:var(--green)">--</span></span>
                        <span id="s-label" style="font-weight:700">OPTIMAL</span>
                    </div>
                </div>

                <!-- FLAME SENSOR -->
                <div class="metric-glow" id="card-fire" style="border-left: 4px solid var(--text-dim);">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <span class="card-title" style="margin:0">FLAME SENSOR</span>
                        <i class="fas fa-fire" id="fire-icon" style="color:var(--text-dim); font-size:1.2rem;"></i>
                    </div>
                    <div class="val-large" id="f-val" style="font-size:2.2rem; color:var(--text-dim);">CLEAR</div>
                    <div
                        style="display: flex; gap: 5px; margin-top: -5px; margin-bottom: 5px; font-size: 0.6rem; font-family: 'JetBrains Mono'; opacity: 0.7;">
                        <div style="flex:1; border: 1px solid var(--border); padding: 2px; text-align:center;">F1:<span
                                id="f1-val">OK</span></div>
                        <div style="flex:1; border: 1px solid var(--border); padding: 2px; text-align:center;">F2:<span
                                id="f2-val">OK</span></div>
                    </div>
                    <div class="mini-chart-box">
                        <canvas id="chartFire"></canvas>
                    </div>
                    <div class="stat-footer">
                        <span id="f-label" style="font-weight:700; color:var(--text-dim)">SECURE</span>
                        <span><i class="fas fa-shield-virus"></i> LIVE</span>
                    </div>
                </div>
            </div>
            <!-- MASTER LOG STREAM & CCTV PREVIEW ROW -->
            <div
                style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; align-items: start;">
                <!-- CCTV PREVIEW -->
                <div class="glass-card" style="height: 100%; display: flex; flex-direction: column;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="card-title"><i class="fas fa-video" style="margin-right: 5px;"></i> LIVE CCTV
                            SURVEILLANCE</span>
                        <span class="node-badge active"
                            style="margin-bottom: 12px; border-color: var(--cyan); color: var(--cyan); background: rgba(0, 243, 255, 0.1);">CAM_01:
                            192.168.99.253</span>
                    </div>
                    <div
                        style="width: 100%; flex-grow: 1; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; min-height: 250px; position: relative;">
                        <!-- Loading Text (Fallback) -->
                        <span
                            style="position: absolute; color: var(--text-dim); font-family: 'JetBrains Mono'; animation: pulse-green 1.5s infinite; text-align: center; z-index: 1;">
                            <i class="fas fa-satellite-dish"
                                style="display: block; font-size: 2rem; margin-bottom: 10px;"></i> ESTABLISHING WEBRTC
                            LINK...
                        </span>

                        <!-- MediaMTX WebRTC Iframe -->
                        <iframe id="cctv-frame" src="https://cctv.inkalum.com/cctv1/"
                            style="width: 100%; height: 100%; border: none; position: relative; z-index: 2;"
                            allowfullscreen></iframe>

                        <!-- Overlay to prevent interaction if needed, and to keep border clean -->
                        <div
                            style="position: absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; box-shadow: inset 0 0 10px rgba(0,0,0,0.5); z-index: 3;">
                        </div>
                    </div>
                </div>

                <!-- MASTER LOG STREAM -->
                <div class="glass-card" style="height: 100%;">
                    <span class="card-title">MASTER LOG STREAM</span>
                    <div class="table-wrap" style="height: 300px; overflow-y: auto;">
                        <table>
                            <thead style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th>IDENTIFIER_TIME</th>
                                    <th>TEMP</th>
                                    <th>HUM</th>
                                    <th>AQS</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="logs-table-body">
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 30px; color: var(--text-dim);">
                                        PENDING CORE SYNC...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // --- CORE LOGIC ---
        let isMuted = false;
        let startTime = Date.now();
        let charts = {};

        function setExportFormat(format) {
            const form = document.getElementById('reportForm');
            form.action = format === 'pdf' ? "{{ route('export.pdf') }}" : "{{ route('export.csv') }}";
            if (format === 'pdf') form.submit();
        }

        // --- CHART CONFIG ---
        function initMasterCharts() {
            const createStyle = (col, fill = true) => ({
                borderColor: col,
                backgroundColor: (ctx) => {
                    if (!fill) return 'transparent';
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 250);
                    g.addColorStop(0, col + '22');
                    g.addColorStop(1, 'transparent');
                    return g;
                },
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 6,
                fill: fill,
                tension: 0.4
            });

            const options = {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 800, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', titleFont: { family: 'Orbitron' }, bodyFont: { family: 'Rajdhani' } }
                },
                scales: {
                    x: { display: false },
                    y: {
                        display: true,
                        grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 9, family: 'JetBrains Mono' }, maxTicksLimit: 4 }
                    }
                }
            };

            charts.temp = new Chart(document.getElementById('chartTemp'), {
                type: 'line', data: { labels: [], datasets: [{ label: 'TEMP', ...createStyle('#00f3ff'), data: [] }] }, options
            });
            charts.hum = new Chart(document.getElementById('chartHum'), {
                type: 'line', data: { labels: [], datasets: [{ label: 'HUM', ...createStyle('#ffcc00'), data: [] }] }, options
            });

            // Smoke Chart with Total + Multi-S
            charts.smoke = new Chart(document.getElementById('chartSmoke'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        { label: 'TOTAL AQS', ...createStyle('#0aff00', true), data: [], borderWidth: 3 },
                        { label: 'S1', ...createStyle('#00f3ff', false), data: [], borderWidth: 1, dash: [2, 2] },
                        { label: 'S2', ...createStyle('#ff0055', false), data: [], borderWidth: 1, dash: [2, 2] },
                        { label: 'S3', ...createStyle('#ffcc00', false), data: [], borderWidth: 1, dash: [2, 2] }
                    ]
                },
                options: {
                    ...options,
                    plugins: { legend: { display: true, position: 'top', labels: { color: '#ccc', boxWidth: 10, font: { size: 8 } } } }
                }
            });

            // Flame Chart
            charts.fire = new Chart(document.getElementById('chartFire'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        { label: 'F1', borderColor: 'var(--pink)', data: [], tension: 0.1, fill: false, pointRadius: 0 },
                        { label: 'F2', borderColor: 'var(--cyan)', data: [], tension: 0.1, fill: false, pointRadius: 0 }
                    ]
                },
                options: {
                    ...options,
                    scales: {
                        x: { display: false },
                        y: { min: 0, max: 1.2, ticks: { display: false }, grid: { display: false } }
                    },
                    plugins: { legend: { display: true, position: 'top', labels: { color: '#ccc', size: 8 } } }
                }
            });
        }

        // --- DATA STREAM ---
        async function syncCore() {
            try {
                const resp = await fetch("/api/live-data?t=" + Date.now());
                const data = await resp.json();
                if (data.status === 'success') {
                    window.lastConfig = data.config;
                    updateCoreUI(data);
                    if (data.history && data.history.length > 0) {
                        updateCoreCharts(data.history);
                    }
                    updateCoreLogs(data.recent);
                }
            } catch (e) {
                console.error("Sync Error:", e);
                document.getElementById('conn-text').innerText = "CORE OFFLINE";
                document.getElementById('conn-text').style.color = "var(--pink)";
            }
        }

        function updateCoreUI(data) {
            const latest = data.latest;
            if (!latest) return;

            const overlay = document.getElementById('alert-overlay');
            const alertMsg = document.getElementById('alert-msg');

            // Gunakan 1 angka di belakang koma (toFixed(1)) agar presisi sama dengan Blynk
            document.getElementById('t-val').innerHTML = `${parseFloat(latest.temp).toFixed(1)}<span class="unit">°C</span>`;
            document.getElementById('h-val').innerHTML = `${parseFloat(latest.hum).toFixed(1)}<span class="unit">%</span>`;
            document.getElementById('s-val').innerHTML = `${latest.smoke}<span class="unit">PPM</span>`;

            document.getElementById('stat-max-smoke').innerText = data.stats.max_smoke + "P";

            // Update Log Count
            if (data.total_count) {
                document.getElementById('total-logs').innerText = data.total_count.toLocaleString();
            }

            // Last Fetch Display
            const lastDate = new Date(latest.created_at);
            document.getElementById('last-fetch').innerText = lastDate.toLocaleTimeString('id-ID');

            // Update Individual Sensors (Safely)
            const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };

            setEl('s1-val', latest.smoke1 || 0);
            setEl('s2-val', latest.smoke2 || 0);
            setEl('s3-val', latest.smoke3 || 0);

            const f1el = document.getElementById('f1-val');
            if (f1el) {
                f1el.innerText = (latest.flame1 == 1) ? 'FIRE' : 'OK';
                f1el.style.color = (latest.flame1 == 1) ? 'var(--pink)' : 'var(--green)';
            }
            const f2el = document.getElementById('f2-val');
            if (f2el) {
                f2el.innerText = (latest.flame2 == 1) ? 'FIRE' : 'OK';
                f2el.style.color = (latest.flame2 == 1) ? 'var(--pink)' : 'var(--green)';
            }


            // Connection Check
            const diff = (new Date() - new Date(latest.created_at)) / 1000;
            const statusText = document.getElementById('conn-text');
            const syncVal = document.getElementById('sync-val');

            const timeoutLimit = data.config ? data.config.timeout : 120;
            const warningLimit = timeoutLimit / 2;

            if (diff > timeoutLimit) {
                statusText.innerText = "SENSOR_LOST";
                statusText.style.color = "var(--pink)";
                syncVal.innerText = "0%";
                syncVal.style.color = "var(--pink)";
            } else if (diff > warningLimit) {
                statusText.innerText = "LATENCY_HIGH";
                statusText.style.color = "var(--yellow)";
                syncVal.innerText = "50%";
                syncVal.style.color = "var(--yellow)";
            } else {
                statusText.innerText = "CORE_ACTIVE";
                statusText.style.color = "var(--green)";
                syncVal.innerText = "100%";
                syncVal.style.color = "var(--cyan)";
            }

            // Environmental & Security Alert Logic
            const log = document.getElementById('safety-log');

            // Dynamic limits
            const tLimit = data.config ? data.config.temp_limit : 35;
            const sLimit = data.config ? data.config.smoke_limit : 1000;

            const tLimitEl = document.getElementById('t-limit-text');
            if (tLimitEl) tLimitEl.innerText = `OPTIMAL RANGE: 18.0°C - ${tLimit.toFixed(1)}°C`;

            const sLimitEl = document.getElementById('s-limit-text');
            if (sLimitEl) sLimitEl.innerText = `DANGER THRESHOLD: >${sLimit} PPM`;

            const isFire = latest.fire == 1 || latest.fire === true;
            const isSmokeCritical = latest.smoke > sLimit;
            const isTempCritical = latest.temp > tLimit;
            const isCritical = isFire || isSmokeCritical || isTempCritical;

            // Update Fire Card UI
            const fireCard = document.getElementById('card-fire');
            const fireIcon = document.getElementById('fire-icon');
            const fireVal = document.getElementById('f-val');
            const fireLabel = document.getElementById('f-label');

            if (isFire) {
                fireCard.style.borderColor = 'var(--pink)';
                fireCard.style.boxShadow = '0 0 20px rgba(255, 0, 85, 0.5)';
                fireIcon.style.color = 'var(--pink)';
                fireIcon.style.filter = 'drop-shadow(0 0 8px var(--pink))';
                fireVal.innerText = 'FIRE!';
                fireVal.style.color = 'var(--pink)';
                fireLabel.innerText = '‼ DANGER';
                fireLabel.style.color = 'var(--pink)';
            } else {
                fireCard.style.borderColor = 'var(--green)';
                fireCard.style.boxShadow = 'none';
                fireIcon.style.color = 'var(--green)';
                fireIcon.style.filter = 'drop-shadow(0 0 5px var(--green))';
                fireVal.innerText = 'CLEAR';
                fireVal.style.color = 'var(--green)';
                fireLabel.innerText = 'SECURE';
                fireLabel.style.color = 'var(--green)';
            }

            // Update Smoke/Temp Card UI
            const smokeCard = document.getElementById('card-smoke');
            const smokeLabel = document.getElementById('s-label');

            if (isSmokeCritical || isTempCritical) {
                smokeCard.style.borderColor = "var(--pink)";
                smokeCard.style.boxShadow = "0 0 20px rgba(255, 0, 85, 0.3)";
                smokeLabel.innerText = "!!!! CRITICAL !!!";
                smokeLabel.style.color = "var(--pink)";
            } else {
                smokeCard.style.borderColor = "var(--green)";
                smokeCard.style.boxShadow = "none";
                smokeLabel.innerText = "OPTIMAL";
                smokeLabel.style.color = "var(--green)";
            }

            // Global Alert Handling
            if (isCritical) {
                let messages = [];
                if (isFire) messages.push("🔥 FLAME DETECTED!");
                if (isSmokeCritical) messages.push(`💨 SMOKE: ${latest.smoke} PPM`);
                if (isTempCritical) messages.push(`🌡️ TEMP: ${latest.temp}°C`);

                const fullMsg = messages.join(" | ");
                log.innerText = `> [${new Date().toLocaleTimeString()}] ALERT: ${fullMsg}`;
                log.style.color = "var(--pink)";

                const isSnoozed = window.alertSnoozeUntil && Date.now() < window.alertSnoozeUntil;

                if (!isMuted && !isSnoozed) {
                    overlay.style.display = 'flex';
                    alertMsg.innerText = fullMsg;
                    playBeep();

                    // Desktop Notification
                    if (window.Notification) {
                        if (Notification.permission === "granted") {
                            if (!window.lastNotificationTime || (Date.now() - window.lastNotificationTime > 30000)) {
                                new Notification("NOC SYSTEM ALERT", {
                                    body: fullMsg,
                                    icon: "{{ asset('favicon.png') }}"
                                });
                                window.lastNotificationTime = Date.now();
                            }
                        } else if (Notification.permission !== "denied") {
                            Notification.requestPermission();
                        }
                    }
                }
            } else {
                log.innerText = `> [${new Date().toLocaleTimeString()}] SYSTEM CHECK: OK`;
                log.style.color = "var(--text-dim)";
                overlay.style.display = 'none';
            }
        }

        function updateCoreCharts(history) {
            const labels = history.map(h => new Date(h.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
            charts.temp.data.labels = labels;
            charts.temp.data.datasets[0].data = history.map(h => h.temp);
            charts.temp.update();

            charts.hum.data.labels = labels;
            charts.hum.data.datasets[0].data = history.map(h => h.hum);
            charts.hum.update();

            charts.smoke.data.labels = labels;
            charts.smoke.data.datasets[0].data = history.map(h => h.smoke || 0);
            charts.smoke.data.datasets[1].data = history.map(h => h.smoke1 || 0);
            charts.smoke.data.datasets[2].data = history.map(h => h.smoke2 || 0);
            charts.smoke.data.datasets[3].data = history.map(h => h.smoke3 || 0);
            charts.smoke.update();

            if (charts.fire) {
                charts.fire.data.labels = labels;
                charts.fire.data.datasets[0].data = history.map(h => (h.flame1 == 1) ? 1 : 0);
                charts.fire.data.datasets[1].data = history.map(h => (h.flame2 == 1) ? 1 : 0);
                charts.fire.update();
            }
        }

        function updateCoreLogs(recent) {
            const tbody = document.getElementById('logs-table-body');
            tbody.innerHTML = recent.map(log => {
                const danger = log.temp > (window.lastConfig?.temp_limit || 35) || log.smoke > (window.lastConfig?.smoke_limit || 1000) || log.fire;

                return `
                <tr class="${danger ? 'row-danger' : ''}">
                    <td style="color:var(--cyan)">${new Date(log.created_at).toLocaleTimeString('id-ID')}</td>
                    <td style="font-weight:700; color:${log.temp > (window.lastConfig?.temp_limit || 35) ? 'var(--pink)' : 'inherit'}">${parseFloat(log.temp).toFixed(1)}°C</td>
                    <td>${parseFloat(log.hum).toFixed(1)}%</td>
                    <td style="color:${log.smoke > (window.lastConfig?.smoke_limit || 1000) ? 'var(--pink)' : 'inherit'}">${log.smoke}</td>
                    <td>
                        <span class="badge ${danger ? 'badge-danger' : 'badge-secure'}">
                            ${danger ? 'CRITICAL' : 'SECURE'}
                        </span>
                    </td>
                </tr>
            `;
            }).join('');
        }

        let audioCtx = null;

        function initAudio() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        }

        // Initialize/Resume audio on first user interaction
        ['click', 'touchstart', 'keydown'].forEach(event => {
            document.body.addEventListener(event, initAudio, { once: true });
        });

        function playBeep() {
            initAudio();
            if (!audioCtx || audioCtx.state === 'suspended') return;

            const osc = audioCtx.createOscillator();
            const g = audioCtx.createGain();

            // Buat suara yang lebih terdengar seperti alarm (siren effect)
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(880, audioCtx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.2);
            osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.4);

            // Volume ditingkatkan (sebelumnya 0.02, sekarang 0.3)
            g.gain.setValueAtTime(0.3, audioCtx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);

            osc.connect(g);
            g.connect(audioCtx.destination);

            osc.start();
            osc.stop(audioCtx.currentTime + 0.5);
        }

        function toggleMute() {
            initAudio(); // Aktifkan audio saat user klik tombol mute/unmute
            isMuted = !isMuted;
            document.getElementById('alarm-status').innerText = isMuted ? "MUTED" : "ACTIVE";
            document.getElementById('bell-icon').className = isMuted ? "fas fa-volume-mute" : "fas fa-shield-alt";
            if (isMuted) dismissAlert();
        }

        function dismissAlert() {
            initAudio();
            document.getElementById('alert-overlay').style.display = 'none';
            // Snooze alert for 30 seconds after manual dismissal
            window.alertSnoozeUntil = Date.now() + 30000;
        }

        // CLOCK & UPTIME
        setInterval(() => {
            document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('date').innerText = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).toUpperCase();

            const uptime = Math.floor((Date.now() - startTime) / 1000);
            const h = Math.floor(uptime / 3600).toString().padStart(2, '0');
            const m = Math.floor((uptime % 3600) / 60).toString().padStart(2, '0');
            const s = (uptime % 60).toString().padStart(2, '0');
            document.getElementById('uptime-val').innerText = `${h}:${m}:${s}`;
        }, 1000);

        window.onload = () => {
            initMasterCharts();
            syncCore();
            setInterval(syncCore, 5000);
            if (window.Notification && Notification.permission !== "denied") Notification.requestPermission();

            // Auto-refresh CCTV iframe every 60 seconds to recover from "stream not found"
            setInterval(() => {
                const cctvFrame = document.getElementById('cctv-frame');
                if (cctvFrame) {
                    const baseUrl = "https://cctv.inkalum.com/cctv1/";
                    cctvFrame.src = baseUrl + "?t=" + Date.now();
                }
            }, 60000);
        };

    </script>

</body>

</html>