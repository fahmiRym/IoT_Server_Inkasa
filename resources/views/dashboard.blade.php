@extends('layouts.app')
@section('title', 'NOC Command Center - Ultimate CORE')

@push('head')
    <!-- PWA -->
    <meta name="theme-color" content="#020408">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NOC Inkasa">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <script>
        // ===== CACHE BUSTER =====
        // Cek versi UI. Kalau localStorage versi berbeda, unregister SW + clear caches + reload sekali.
        (function () {
            var UI_VER = '2026-10-16-r18';   // <- bump saat rilis CSS/JS baru
            var last = null;
            try { last = localStorage.getItem('noc-ui-ver'); } catch (e) {}
            if (last !== UI_VER) {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.getRegistrations().then(function (regs) {
                        regs.forEach(function (r) { r.unregister(); });
                    }).catch(function () {});
                }
                if (window.caches && caches.keys) {
                    caches.keys().then(function (keys) {
                        keys.forEach(function (k) { caches.delete(k); });
                    }).catch(function () {});
                }
                try { localStorage.setItem('noc-ui-ver', UI_VER); } catch (e) {}
                // Reload sekali TANPA cache
                if (!sessionStorage.getItem('noc-ui-reloaded')) {
                    sessionStorage.setItem('noc-ui-reloaded', '1');
                    setTimeout(function () { location.reload(); }, 200);
                    return;
                }
            } else {
                sessionStorage.removeItem('noc-ui-reloaded');
            }

            // Register SW baru (setelah cache lama bersih)
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {});
                });
            }
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>
@endpush

@push('styles')
    <style>
        * {
            cursor: crosshair;
        }

        body {
            /* dihapus: radial-gradient ganda + texture network image -> mahal di GPU.
               efek atmosfer dijaga via .grid-overlay + glass-card border glow. */
        }

        /* Dashboard: main wrapper dari layouts.app dinolkan paddingnya —
           .container akan mengelola padding sendiri (cegah double-padding & overflow) */
        main {
            padding: 0 !important;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        html, body { overflow-x: hidden; max-width: 100%; }

        /* SEMUA input di dashboard: allow shrink di grid/flex parent supaya tidak
           memaksa sidebar/card melebar (date input default intrinsic width ~150px) */
        .container input, .container select, .container textarea {
            min-width: 0;
            max-width: 100%;
        }
        .container input[type="date"],
        .container input[type="month"] {
            flex: 1 1 0;
            min-width: 0;
            font-size: clamp(.62rem, .65vw, .75rem);
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
            /* HARD CAP width — pakai calc dgn 100vw supaya tidak melebihi viewport
               (min() memilih yg terkecil, jaga-jaga saat viewport < 1800px) */
            max-width: min(1800px, calc(100vw - 8px));
            margin: 0 auto;
            padding: clamp(10px, 1vw, 16px);
            display: grid;
            /* Sidebar FIXED, main mengambil sisa (minmax 0 supaya bisa shrink) */
            grid-template-columns: 260px minmax(0, 1fr);
            gap: clamp(10px, 1vw, 16px);
            box-sizing: border-box;
            min-height: 100vh;
            container-type: inline-size;   /* enable container queries di children */
            overflow: hidden;               /* safety */
        }
        /* Sidebar: kunci lebar & clip semua content agar tidak memaksa grid track melebar */
        aside {
            width: 260px;
            max-width: 100%;
            overflow: hidden;
        }
        aside > * { min-width: 0; max-width: 100%; overflow: hidden; }
        aside .glass-card { overflow: hidden; min-width: 0; }
        aside .glass-card > * { min-width: 0; max-width: 100%; }

        /* Layar sangat lebar: sidebar sedikit lebih longgar */
        @media (min-width: 1600px) {
            .container { grid-template-columns: 280px minmax(0, 1fr); }
            aside { width: 280px; }
        }
        @media (min-width: 1800px) {
            .container { grid-template-columns: 300px minmax(0, 1fr); }
            aside { width: 300px; }
        }
        /* Container sempit / mobile: sidebar full width */
        @media (max-width: 1099px) {
            aside { width: 100%; }
        }

        /* --- TOP HEADER --- */
        header {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px clamp(12px, 1.5vw, 25px);
            background: linear-gradient(90deg, var(--panel), transparent);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
            position: relative;
            gap: 12px;
            flex-wrap: wrap;
            min-width: 0;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 20px;
            min-width: 0;
            flex: 1 1 auto;
        }
        .brand {
            min-width: 0;
            flex: 1 1 auto;
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
            font-size: clamp(1.05rem, 1.4vw, 1.6rem);
            margin: 0;
            letter-spacing: clamp(2px, .35vw, 4px);
            text-transform: uppercase;
            font-weight: 900;
            color: #fff;
            text-shadow: 0 0 20px var(--accent-glow);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- SIDEBAR --- */
        aside {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 0;
        }

        .glass-card {
            background:
                linear-gradient(135deg, rgba(255,255,255,0.025), rgba(255,255,255,0.005)),
                rgba(10, 15, 25, 0.55);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 14px 12px;
            position: relative;
            overflow: hidden;
            transition: border-color .25s ease, box-shadow .25s ease;
        }
        .glass-card:hover {
            border-color: rgba(0, 243, 255, 0.35);
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.08);
        }
        /* Top-highlight line untuk depth */
        .glass-card > .card-title { position: relative; z-index: 1; }

        .card-title {
            font-family: 'Orbitron';
            font-size: 0.62rem;
            color: var(--cyan);
            letter-spacing: 2px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .card-title::before {
            content: '';
            width: 3px; height: 12px;
            background: var(--cyan);
            box-shadow: 0 0 6px var(--cyan);
            border-radius: 2px;
            flex-shrink: 0;
        }
        .card-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--border), transparent);
        }

        /* --- SYSTEM HEALTH --- */
        .health-matrix {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(0, 243, 255, 0.08);
            border-radius: 10px;
        }

        .matrix-item {
            text-align: center;
            border: 1px solid rgba(0, 243, 255, 0.1);
            padding: 8px 6px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.25);
            transition: border-color .18s, background .18s;
        }
        .matrix-item:hover {
            border-color: rgba(0, 243, 255, 0.35);
            background: rgba(0, 243, 255, 0.04);
        }

        .matrix-val {
            font-family: 'JetBrains Mono';
            font-size: 1rem;
            font-weight: 700;
            color: var(--cyan);
            letter-spacing: .5px;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .matrix-lab {
            font-size: 0.52rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-family: 'JetBrains Mono';
        }

        /* --- METRICS --- */
        /* Semantic inner main di dalam .container grid: item grid kolom-2.
           Wajib min-width:0 + max-width:100% supaya grid track (minmax(0,1fr)) bisa shrink. */
        .container > main {
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }

        .metrics-hud {
            display: grid;
            /* CONTAINER QUERY-based: kartu adapt ke lebar PARENT (main), bukan viewport.
               Ini bikin card benar-benar responsif walau ada zoom browser. */
            grid-template-columns: repeat(auto-fit, minmax(min(220px, 100%), 1fr));
            gap: clamp(10px, 1vw, 16px);
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        /* Container query — di container query context, cq units = parent width */
        @container (max-width: 900px) {
            .metrics-hud { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @container (max-width: 500px) {
            .metrics-hud { grid-template-columns: 1fr; }
        }

        .metric-glow {
            padding: clamp(12px, 1vw, 18px);
            border-radius: 14px;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)),
                rgba(10, 15, 25, 0.35);
            border: 1px solid var(--border);
            position: relative;
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            /* Sudut kanan-atas gradient corner sebagai glow accent */
        }
        .metric-glow::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 60%, rgba(0, 243, 255, 0.06));
            pointer-events: none;
        }
        .metric-glow::after {
            content: '';
            position: absolute;
            top: -1px; right: -1px;
            width: 60px; height: 60px;
            background: radial-gradient(circle at top right, rgba(0, 243, 255, 0.35), transparent 70%);
            pointer-events: none;
            opacity: .5;
            transition: opacity .25s;
        }
        .metric-glow:hover {
            transform: translateY(-3px);
            border-color: rgba(0, 243, 255, 0.35);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 243, 255, 0.08);
        }
        .metric-glow:hover::after { opacity: 1; }

        /* Skeleton pulse untuk value yang belum dimuat (--) */
        .val-large:where([data-loading="1"], :empty)::after,
        .val-large:where(:not(.loaded)):has-text-loading { }
        @keyframes val-skeleton {
            0%, 100% { opacity: .5; }
            50%      { opacity: .95; }
        }
        .val-large.is-loading {
            animation: val-skeleton 1.6s ease-in-out infinite;
        }

        .val-large {
            font-family: 'Orbitron';
            font-size: clamp(1.3rem, 1.8vw, 2.1rem);
            font-weight: 900;
            line-height: 1;
            margin: 10px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .val-large .unit {
            font-size: clamp(.65rem, .7vw, .95rem);
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
            background: linear-gradient(135deg, rgba(0, 243, 255, 0.08), rgba(0, 243, 255, 0.02));
            border: 1px solid var(--border);
            color: var(--cyan);
            padding: 11px 14px;
            border-radius: 8px;
            font-family: 'Orbitron';
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: transform .18s ease, background .18s ease, box-shadow .18s ease, border-color .18s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-bottom: 10px;
            position: relative;
            overflow: hidden;
        }
        .cyber-btn i { transition: transform .18s; }
        .cyber-btn:hover {
            background: linear-gradient(135deg, rgba(0, 243, 255, 0.25), rgba(0, 243, 255, 0.08));
            border-color: var(--cyan);
            color: #fff;
            box-shadow: 0 0 18px rgba(0, 243, 255, 0.35), inset 0 0 12px rgba(0, 243, 255, 0.06);
            transform: translateY(-1px);
        }
        .cyber-btn:hover i { transform: scale(1.15); }
        .cyber-btn:active { transform: translateY(0); }

        .cyber-btn.pdf {
            background: linear-gradient(135deg, rgba(255, 0, 85, 0.1), rgba(255, 0, 85, 0.02));
            border-color: rgba(255, 0, 85, 0.3);
            color: var(--pink);
        }
        .cyber-btn.pdf:hover {
            background: linear-gradient(135deg, rgba(255, 0, 85, 0.3), rgba(255, 0, 85, 0.08));
            border-color: var(--pink);
            color: #fff;
            box-shadow: 0 0 18px rgba(255, 0, 85, 0.4), inset 0 0 12px rgba(255, 0, 85, 0.08);
        }

        /* --- LOG TABLE --- */
        .table-wrap {
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.28);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }

        /* Master Log Stream compact table — supaya tidak overflow di kolom sempit */
        .log-stream-table { table-layout: fixed; width: 100%; }
        .log-stream-table th,
        .log-stream-table td {
            padding: 9px 6px !important;
            font-size: .68rem !important;
            letter-spacing: .5px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }
        .log-stream-table th { letter-spacing: 1px !important; }
        .log-stream-table th.col-time,
        .log-stream-table td.log-time { text-align: left; padding-left: 12px !important; }
        .log-stream-table .col-time { width: 38%; }
        .log-stream-table .col-num  { width: 18%; }
        .log-stream-table .col-stat { width: 12%; }

        /* Status pill — icon-only, kompak */
        .log-status {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border-radius: 50%;
            font-size: .65rem;
            border: 1px solid;
        }
        .log-status.ok   { background: rgba(10, 255, 0, 0.15);  border-color: var(--green); color: var(--green); box-shadow: 0 0 6px rgba(10,255,0,.25); }
        .log-status.warn { background: rgba(255, 204, 0, 0.15); border-color: var(--yellow); color: var(--yellow); }
        .log-status.err  { background: rgba(255, 0, 85, 0.15);  border-color: var(--pink); color: var(--pink); box-shadow: 0 0 6px rgba(255,0,85,.35); }

        /* Di kolom yg lebih longgar (< 1300px) — kembali ke label penuh */
        @media (max-width: 1299px) {
            .log-stream-table th, .log-stream-table td { font-size: .78rem !important; padding: 10px !important; }
            .log-stream-table .col-time { width: 32%; }
            .log-stream-table .col-num  { width: 18%; }
            .log-stream-table .col-stat { width: 14%; }
        }

        th {
            background: linear-gradient(180deg, rgba(0, 243, 255, 0.08), rgba(0, 243, 255, 0.02));
            padding: 12px 14px;
            text-align: left;
            font-size: 0.62rem;
            font-family: 'JetBrains Mono';
            font-weight: 700;
            color: var(--cyan);
            letter-spacing: 2px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(0, 243, 255, 0.15);
        }

        td {
            padding: 11px 14px;
            border-bottom: 1px solid rgba(0, 243, 255, 0.05);
            font-family: 'JetBrains Mono';
            font-size: 0.78rem;
            transition: background .15s;
        }
        tbody tr { transition: background .15s; }
        tbody tr:hover td { background: rgba(0, 243, 255, 0.04); }
        tbody tr:last-child td { border-bottom: none; }

        .row-danger {
            background: linear-gradient(90deg, rgba(255, 0, 85, 0.15), rgba(255, 0, 85, 0.05));
            border-left: 3px solid var(--pink);
        }
        .row-danger td { color: #ffdae5; }

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

        /* ===== USER CHIP DI HEADER ===== */
        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 8px;
            background: rgba(0, 243, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 30px;
            backdrop-filter: blur(8px);
            max-width: 100%;
            min-width: 0;
            flex-shrink: 1;
        }
        .user-meta { min-width: 0; overflow: hidden; }
        .user-name, .user-role {
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: clamp(80px, 12vw, 160px);
        }
        .user-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), rgba(0, 243, 255, 0.2));
            display: flex; align-items: center; justify-content: center;
            color: #000;
            font-family: 'Orbitron';
            font-weight: 900;
            font-size: 0.85rem;
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.3);
            overflow: hidden;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-meta { line-height: 1.1; }
        .user-name {
            font-family: 'Orbitron';
            font-weight: 700;
            font-size: 0.78rem;
            color: #fff;
            letter-spacing: 1px;
        }
        .user-role {
            font-family: 'JetBrains Mono';
            font-size: 0.55rem;
            color: var(--cyan);
            letter-spacing: 1px;
            opacity: 0.8;
        }
        .user-actions { display: flex; gap: 5px; margin-left: 5px; }
        .user-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--cyan);
            width: 30px; height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center; justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }
        .user-btn:hover {
            background: rgba(0, 243, 255, 0.15);
            border-color: var(--cyan);
            box-shadow: 0 0 8px var(--accent-glow);
        }
        .user-btn.pink { color: var(--pink); border-color: rgba(255, 0, 85, 0.3); }
        .user-btn.pink:hover { background: rgba(255, 0, 85, 0.15); border-color: var(--pink); box-shadow: 0 0 8px rgba(255, 0, 85, 0.4); }
        @media (max-width: 700px) {
            .user-meta { display: none; }
            .user-chip { padding: 4px 6px; gap: 6px; }
        }

        /* ===== BOOT SPLASH ===== */
        #boot-splash {
            position: fixed;
            inset: 0;
            background: var(--bg-deep);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            transition: opacity 0.35s ease-out;
        }
        #boot-splash.hide {
            opacity: 0;
            pointer-events: none;
        }
        .boot-logo {
            width: 70px; height: 70px;
            border-radius: 50%;
            border: 2px solid var(--cyan);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 25px var(--accent-glow);
            animation: pulse-green 1.5s infinite;
        }
        .boot-icon {
            color: var(--cyan);
            font-size: 1.8rem;
            filter: drop-shadow(0 0 8px var(--cyan));
        }
        .boot-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 1.1rem;
            letter-spacing: 5px;
            color: #fff;
            text-shadow: 0 0 18px var(--accent-glow);
            margin-top: 4px;
        }
        .boot-status {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: var(--cyan);
            letter-spacing: 2px;
            opacity: 0.85;
        }
        .boot-bar {
            width: 180px;
            height: 3px;
            background: rgba(0, 243, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 6px;
        }
        .boot-bar-fill {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            animation: boot-slide 1.2s infinite ease-in-out;
        }
        @keyframes boot-slide {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(400%); }
        }

        @keyframes pulse-refresh {
            0%   { box-shadow: 0 0 0 0 rgba(255, 204, 0, 0.55); }
            70%  { box-shadow: 0 0 0 8px rgba(255, 204, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 204, 0, 0); }
        }
        #last-fetch-tile.flash { animation: pulse-refresh 0.9s ease-out; }
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

        /* ===== CCTV (multi-cam) ===== */
        .cctv-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 10px;
        }

        .cctv-tile {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cctv-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cctv-label {
            font-size: 0.65rem;
            font-family: 'JetBrains Mono';
            color: var(--cyan);
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cctv-live {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            font-size: 0.6rem;
            font-family: 'Orbitron';
            font-weight: 700;
            letter-spacing: 1px;
            border-radius: 3px;
            background: rgba(10, 255, 0, 0.12);
            color: var(--green);
            border: 1px solid var(--green);
        }
        .cctv-live::before {
            content: '';
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 6px var(--green);
            animation: pulse-green 1.2s infinite;
        }
        .cctv-live.stalled {
            background: rgba(255, 0, 85, 0.12);
            color: var(--pink);
            border-color: var(--pink);
        }
        .cctv-live.stalled::before { background: var(--pink); box-shadow: 0 0 6px var(--pink); }

        .cctv-controls {
            display: inline-flex;
            gap: 6px;
        }
        .cctv-controls button {
            background: rgba(0, 243, 255, 0.06);
            border: 1px solid var(--border);
            color: var(--cyan);
            width: 30px; height: 30px;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all .15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .cctv-controls button:hover {
            background: rgba(0, 243, 255, 0.18);
            border-color: var(--cyan);
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.4);
        }

        .cctv-stage {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(0, 243, 255, 0.2);
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 243, 255, 0.06);
        }
        /* Corner scan brackets — futuristic frame */
        .cctv-stage::before, .cctv-stage::after {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            border: 2px solid var(--cyan);
            z-index: 3;
            pointer-events: none;
            filter: drop-shadow(0 0 4px var(--cyan));
        }
        .cctv-stage::before {
            top: 8px; left: 8px;
            border-right: none; border-bottom: none;
            border-top-left-radius: 4px;
        }
        .cctv-stage::after {
            bottom: 8px; right: 8px;
            border-left: none; border-top: none;
            border-bottom-right-radius: 4px;
        }
        .cctv-stage .corner-tr, .cctv-stage .corner-bl {
            position: absolute;
            width: 18px; height: 18px;
            border: 2px solid var(--cyan);
            z-index: 3;
            pointer-events: none;
            filter: drop-shadow(0 0 4px var(--cyan));
        }
        .cctv-stage .corner-tr { top: 8px; right: 8px; border-left: none; border-bottom: none; border-top-right-radius: 4px; }
        .cctv-stage .corner-bl { bottom: 8px; left: 8px; border-right: none; border-top: none; border-bottom-left-radius: 4px; }

        .cctv-stage iframe,
        .cctv-stage video {
            width: 100%;
            height: 100%;
            border: none;
            position: relative;
            z-index: 2;
            object-fit: cover;
            background: #000;
        }

        .cctv-loading {
            position: absolute;
            color: var(--text-dim);
            font-family: 'JetBrains Mono';
            font-size: 0.7rem;
            text-align: center;
            z-index: 1;
            animation: pulse-green 1.5s infinite;
        }

        .cctv-meta {
            display: flex;
            justify-content: space-between;
            font-family: 'JetBrains Mono';
            font-size: 0.6rem;
            color: var(--text-dim);
            letter-spacing: 1px;
            opacity: 0.8;
        }
        .cctv-meta .ok { color: var(--green); }
        .cctv-meta .warn { color: var(--pink); }

        /* ============================================================
           SYS-CLOCK — cyber-security digital clock di bawah CCTV
           ============================================================ */
        .sys-clock {
            margin-top: 12px;
            padding: 16px 20px 14px;
            background: #050a12;
            border: 1px solid rgba(0,243,255,.2);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        /* corner brackets */
        .sys-clock::before,
        .sys-clock::after {
            content: ''; position: absolute;
            width: 14px; height: 14px;
            border: 2px solid var(--cyan);
            pointer-events: none;
            filter: drop-shadow(0 0 3px var(--cyan));
        }
        .sys-clock::before { top: 6px; left: 6px; border-right: 0; border-bottom: 0; border-top-left-radius: 3px; }
        .sys-clock::after  { bottom: 6px; right: 6px; border-left: 0; border-top: 0; border-bottom-right-radius: 3px; }

        /* Badges inline di atas aux (gantikan sc-head bar atas) */
        .sc-badges-inline {
            display: flex; gap: 6px; align-items: center; flex-wrap: wrap;
            margin-bottom: 8px;
            font-family: 'JetBrains Mono';
        }
        .sc-badge {
            padding: 3px 8px;
            border: 1px solid rgba(0,243,255,.3);
            border-radius: 3px;
            color: var(--cyan);
            background: rgba(0,243,255,.05);
            font-weight: 700;
            font-size: .55rem;
            letter-spacing: 1.5px;
        }
        .sc-badge.sync { color: var(--green); border-color: rgba(10,255,0,.3); background: rgba(10,255,0,.05); }
        .sc-badge.sync::before { content: ''; display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: var(--green); box-shadow: 0 0 5px var(--green); margin-right: 4px; animation: pulse-green 1.3s infinite; vertical-align: middle; }

        /* ===== LED Digital Clock utama ===== */
        .sc-main {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: clamp(16px, 2.5vw, 32px);
        }
        .sc-time {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-weight: 700;
            font-size: clamp(4.5rem, 9vw, 7.2rem);
            line-height: .95;
            color: var(--green);
            text-shadow: 0 0 2px rgba(10,255,0,.5);
            letter-spacing: 3px;
            font-variant-numeric: tabular-nums;
            display: flex;
            align-items: baseline;
            gap: 2px;
            position: relative;
            justify-content: center;
        }
        /* 7-segment faux background — digit "88:88:88" ghost (lebih subtle) */
        .sc-time::before {
            content: "88:88:88";
            position: absolute; inset: 0;
            color: rgba(10,255,0,.04);
            text-shadow: none;
            pointer-events: none;
            z-index: 0;
        }
        .sc-time > span { position: relative; z-index: 1; }
        .sc-time .colon { animation: blink-colon 1s steps(2, start) infinite; color: var(--green); text-shadow: none; }
        .sc-time .ms {
            font-size: .32em;
            color: var(--text-dim);
            letter-spacing: 1px;
            margin-left: 10px;
            text-shadow: none;
            opacity: .7;
            align-self: center;
        }
        @keyframes blink-colon {
            50% { opacity: .25; }
        }

        /* Right side (date + aux info) — digeser ke kiri (align left) biar menempel ke jam */
        .sc-aux {
            text-align: left;
            font-family: 'JetBrains Mono';
            line-height: 1.4;
            min-width: 0;
            padding-left: clamp(8px, 1vw, 20px);
            border-left: 1px solid rgba(0, 243, 255, 0.15);
        }
        .sc-day {
            font-family: 'Orbitron';
            font-weight: 900;
            font-size: clamp(3.6rem, 5.6vw, 5.4rem);
            letter-spacing: 7px;
            color: #fff;
            text-shadow: 0 0 2px rgba(255,255,255,.3);
            line-height: 1;
        }
        .sc-date-full {
            font-family: 'JetBrains Mono';
            font-weight: 700;
            font-size: clamp(1.6rem, 2.3vw, 2.3rem);
            color: var(--cyan);
            letter-spacing: 3px;
            margin-top: 14px;
            line-height: 1;
        }
        .sc-aux-row {
            display: flex;
            justify-content: flex-start;
            gap: 12px;
            margin-top: 10px;
            font-size: .62rem;
            letter-spacing: 1.5px;
            color: var(--text-dim);
        }
        .sc-aux-row span strong {
            color: var(--cyan);
            font-weight: 700;
            margin-left: 3px;
        }

        /* Progress day-of-year bar (LED chunks) */
        .sc-progress {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed rgba(0,243,255,.15);
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'JetBrains Mono';
            font-size: .55rem;
            letter-spacing: 1.5px;
            color: var(--text-dim);
        }
        .sc-progress .sc-prog-label { white-space: nowrap; }
        .sc-progress .sc-prog-label strong { color: var(--cyan); font-weight: 700; }
        .sc-progress .sc-prog-bar {
            flex: 1;
            height: 4px;
            background:
                repeating-linear-gradient(90deg, rgba(0,243,255,.08) 0 7px, transparent 7px 9px);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }
        .sc-progress .sc-prog-fill {
            position: absolute; top: 0; left: 0; bottom: 0;
            background: linear-gradient(90deg, var(--cyan), rgba(0,243,255,.3));
            box-shadow: 0 0 8px var(--cyan);
            border-radius: 2px;
            transition: width .4s ease;
        }
        .sc-progress .sc-prog-pct {
            color: var(--cyan);
            font-weight: 700;
            min-width: 36px;
            text-align: right;
        }

        /* Flicker effect (optional, subtle) */
        .sc-time.flicker { animation: sc-flicker 2.5s infinite; }
        @keyframes sc-flicker {
            0%, 100%, 93%, 96% { opacity: 1; }
            94% { opacity: .85; }
            95% { opacity: .95; }
        }

        @media (max-width: 700px) {
            .sys-clock { padding: 12px 14px; }
            .sc-main { grid-template-columns: 1fr; gap: 10px; }
            .sc-aux { text-align: left; }
            .sc-aux-row { justify-content: flex-start; }
            .sc-time .ms { display: none; }
        }

        /* Fullscreen styling */
        .cctv-stage:fullscreen,
        .cctv-stage:-webkit-full-screen {
            max-height: none;
            aspect-ratio: auto;
            border-radius: 0;
            border: 0;
        }
        .cctv-stage:fullscreen video,
        .cctv-stage:-webkit-full-screen video {
            object-fit: contain;
        }

        /* ===== RESPONSIVE (semua dimensi device) ===== */
        /* default: CCTV row stacked (CCTV atas, logs bawah) */
        .split-row-cctv { grid-template-columns: 1fr; }
        /* layar lebar: CCTV + LOGS side-by-side (CCTV diperluas) */
        @media (min-width: 1300px) {
            .split-row-cctv { grid-template-columns: 1.9fr 1fr; }
        }
        @media (min-width: 1700px) {
            .split-row-cctv { grid-template-columns: 2.2fr 1fr; }
        }

        /* Tablet portrait / mobile besar (< 1100): stack sidebar-bawah */
        @media (max-width: 1099px) {
            .container {
                grid-template-columns: 1fr;
                height: auto;
                min-height: 100vh;
            }

            header { order: 0; }
            main { order: 1; }
            aside { order: 2; }

            .split-row { grid-template-columns: 1fr !important; }
            .split-row-cctv { grid-template-columns: 1fr !important; }
        }

        /* Tablet kecil (< 900px) */
        @media (max-width: 899px) {
            .brand h1 { font-size: 1.15rem; }
            .cctv-grid { grid-template-columns: 1fr; }
        }

        /* Mobile besar (< 700): header stacked */
        @media (max-width: 700px) {
            * { cursor: auto; }

            .container { padding: 10px; gap: 12px; }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 12px 15px;
            }

            .brand h1 { font-size: 1rem; letter-spacing: 2px; }
            .metric-glow { min-height: auto; }
            .grid-overlay { display: none; }
            .table-wrap { overflow-x: auto; }
            .cctv-grid { grid-template-columns: 1fr; }

            /* Chip user: sembunyikan meta di mobile, sisakan avatar+aksi */
            .user-name, .user-role { font-size: .68rem; }
        }

        /* Mobile kecil (< 480) */
        @media (max-width: 480px) {
            .health-matrix { grid-template-columns: 1fr; }
            .brand-container { gap: 12px; }
            .metric-glow { padding: 12px; }
            header { padding: 10px 12px; }
        }

        /* ===== BOTTOM NAV (mobile-only) ===== */
        #bottom-nav {
            display: none;   /* hidden by default; only mobile */
        }
        @media (max-width: 900px) {
            #bottom-nav {
                display: grid;
                grid-auto-flow: column;
                grid-auto-columns: 1fr;
                position: fixed;
                left: 0; right: 0; bottom: 0;
                z-index: 150;
                background: rgba(2, 4, 8, 0.92);
                backdrop-filter: blur(14px);
                border-top: 1px solid var(--border);
                padding: 6px 4px calc(6px + env(safe-area-inset-bottom, 0));
                box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.4);
            }
            #bottom-nav .bn-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 3px;
                padding: 6px 4px;
                border-radius: 8px;
                color: var(--text-dim);
                font-family: 'JetBrains Mono';
                font-size: .55rem;
                letter-spacing: 1px;
                text-transform: uppercase;
                text-decoration: none;
                transition: color .15s, background .15s;
                margin: 0;
            }
            #bottom-nav .bn-item i { font-size: 1.05rem; }
            #bottom-nav .bn-item:active,
            #bottom-nav .bn-item:hover { color: var(--cyan); background: rgba(0, 243, 255, 0.06); }
            #bottom-nav .bn-form button {
                background: none; border: 0; cursor: pointer;
                color: inherit; font: inherit; letter-spacing: inherit; text-transform: inherit;
                display: flex; flex-direction: column; align-items: center; gap: 3px;
                padding: 0; width: 100%;
            }
            #bottom-nav .bn-item.bn-form { padding: 0; color: var(--pink); }
            #bottom-nav .bn-item.bn-form:hover { background: rgba(255, 0, 85, 0.08); }

            /* Beri space di bawah supaya konten terakhir tidak ketutup bottom-nav */
            body { padding-bottom: 68px; }

            /* Sembunyikan user-chip actions di header (sudah ada di bottom-nav) */
            .user-actions { display: none; }

            /* Scale FAB naik supaya tidak overlap bottom nav */
            #scale-fab { bottom: 72px !important; }
        }

        /* ============================================================
           SERVER-CHECK REMINDER PANEL (di bawah Master Log)
           ============================================================ */
        .scheck-panel {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 14px;
            margin-top: 12px;
            padding: 14px 16px;
            background:
                linear-gradient(135deg, rgba(255,204,0,.08), rgba(255,0,85,.03)),
                rgba(10,15,25,.65);
            border: 1px solid rgba(255,204,0,.35);
            border-left: 4px solid var(--yellow);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 24px rgba(255,204,0,.08), inset 0 0 24px rgba(255,204,0,.03);
            animation: scheck-pulse 2.4s ease-in-out infinite;
        }
        /* Inline variant — di dalam Master Log card (kolom sempit) */
        .scheck-panel--inline {
            grid-template-columns: 1fr;
            padding: 12px 14px;
            margin-top: 10px;
        }
        .scheck-panel--inline .scheck-icon {
            width: 38px; height: 38px; font-size: 1rem;
        }
        .scheck-panel--inline .scheck-title { font-size: .65rem; letter-spacing: 2px; }
        .scheck-panel--inline .scheck-desc { font-size: .78rem; margin-bottom: 6px; }
        .scheck-panel--inline .scheck-meta { gap: 10px; font-size: .55rem; }
        .scheck-panel--inline .scheck-actions { width: 100%; margin-top: 2px; }
        .scheck-panel--inline .scheck-btn {
            width: 100%; justify-content: center;
            padding: 9px 14px; font-size: .68rem; letter-spacing: 1.5px;
        }
        @keyframes scheck-pulse {
            0%, 100% { border-color: rgba(255,204,0,.35); box-shadow: 0 0 24px rgba(255,204,0,.08), inset 0 0 24px rgba(255,204,0,.03); }
            50%      { border-color: rgba(255,204,0,.6); box-shadow: 0 0 36px rgba(255,204,0,.18), inset 0 0 24px rgba(255,204,0,.05); }
        }
        .scheck-panel::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,204,0,.05), transparent);
            animation: scan 6s linear infinite;
            pointer-events: none;
        }
        .scheck-left {
            display: flex; align-items: flex-start; gap: 14px;
            min-width: 0;
            position: relative; z-index: 1;
        }
        .scheck-icon {
            flex-shrink: 0;
            width: 48px; height: 48px;
            border-radius: 10px;
            background: radial-gradient(circle, rgba(255,204,0,.25), transparent 70%);
            border: 2px solid var(--yellow);
            display: flex; align-items: center; justify-content: center;
            color: var(--yellow);
            font-size: 1.3rem;
            filter: drop-shadow(0 0 8px var(--yellow));
            animation: scheck-shake .5s cubic-bezier(.36,.07,.19,.97) infinite;
        }
        @keyframes scheck-shake {
            0%, 100% { transform: translate(0,0) rotate(0); }
            25% { transform: translate(-1px, 1px) rotate(-2deg); }
            75% { transform: translate(1px, -1px) rotate(2deg); }
        }
        .scheck-text { min-width: 0; flex: 1; }
        .scheck-title {
            font-family: 'Orbitron';
            font-weight: 900;
            font-size: .78rem;
            letter-spacing: 2.5px;
            color: var(--yellow);
            text-shadow: 0 0 12px rgba(255,204,0,.5);
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .scheck-desc {
            font-family: 'Rajdhani';
            font-size: .92rem;
            color: var(--text-vibrant);
            line-height: 1.4;
            margin-bottom: 8px;
        }
        .scheck-meta {
            display: flex; flex-wrap: wrap; gap: 14px;
            font-family: 'JetBrains Mono';
            font-size: .62rem;
            color: var(--text-dim);
            letter-spacing: .5px;
        }
        .scheck-meta i { margin-right: 4px; color: var(--yellow); opacity: .8; }
        .scheck-meta strong { color: var(--text-vibrant); font-weight: 700; }

        .scheck-actions { position: relative; z-index: 1; flex-shrink: 0; }
        .scheck-btn {
            padding: 12px 22px;
            border-radius: 10px;
            border: 2px solid;
            cursor: pointer;
            font-family: 'Orbitron';
            font-weight: 700;
            font-size: .75rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .18s;
        }
        .scheck-btn.ok {
            background: linear-gradient(135deg, rgba(10,255,0,.2), rgba(10,255,0,.05));
            border-color: var(--green);
            color: var(--green);
            box-shadow: 0 0 12px rgba(10,255,0,.2);
        }
        .scheck-btn.ok:hover {
            background: rgba(10,255,0,.35);
            box-shadow: 0 0 20px rgba(10,255,0,.5);
            transform: translateY(-2px);
        }
        .scheck-btn:disabled {
            opacity: .5; cursor: not-allowed; transform: none;
        }

        /* ACK banner (hijau, hilang pelan2) */
        .scheck-ack {
            margin-top: 10px;
            padding: 9px 14px;
            background: rgba(10,255,0,.08);
            border: 1px solid rgba(10,255,0,.3);
            border-left: 3px solid var(--green);
            border-radius: 8px;
            color: var(--green);
            font-family: 'JetBrains Mono';
            font-size: .62rem;
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            line-height: 1.4;
        }
        .scheck-ack strong { color: #d4ffcc; font-weight: 700; }
        .scheck-ack i { font-size: .85rem; flex-shrink: 0; }

        @media (max-width: 700px) {
            .scheck-panel { grid-template-columns: 1fr; }
            .scheck-actions { width: 100%; }
            .scheck-btn { width: 100%; justify-content: center; }
        }

        /* ==========================================================
           SAFETY LOCK — !important supaya tidak bisa di-override oleh
           inline style atau library CSS lain yang men-cache. Aturan
           paling akhir di file supaya cascade menang.
           ========================================================== */
        html, body { overflow-x: hidden !important; max-width: 100vw !important; }
        body > main, main.w-100 {
            padding: 0 !important;
            max-width: 100vw !important;
            width: 100% !important;
            overflow-x: hidden !important;
        }
        .container {
            max-width: min(1800px, calc(100vw - 4px)) !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        .container > main {
            min-width: 0 !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }
        .metrics-hud {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }
        .metric-glow { min-width: 0 !important; max-width: 100% !important; overflow: hidden !important; }

        /* ===== SCALE CONTROLLER (mini FAB kanan-bawah) ===== */
        #scale-fab {
            position: fixed;
            right: clamp(10px, 1.5vw, 20px);
            bottom: clamp(10px, 1.5vw, 20px);
            z-index: 200;
            display: flex;
            gap: 6px;
            padding: 6px;
            background: rgba(10, 15, 25, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5), 0 0 14px rgba(0,243,255,0.15);
        }
        #scale-fab button {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--cyan);
            font-family: 'JetBrains Mono';
            font-size: .8rem;
            cursor: pointer;
            transition: all .15s;
        }
        #scale-fab button:hover { border-color: var(--cyan); background: rgba(0,243,255,0.1); box-shadow: 0 0 8px rgba(0,243,255,0.4); }
        #scale-fab .lbl {
            display: inline-flex;
            align-items: center;
            padding: 0 8px;
            font-family: 'Orbitron';
            font-size: .55rem;
            letter-spacing: 1.5px;
            color: var(--text-dim);
            min-width: 34px;
            justify-content: center;
        }
        @media (max-width: 480px) {
            #scale-fab { right: 8px; bottom: 8px; padding: 4px; gap: 4px; }
            #scale-fab button { width: 28px; height: 28px; font-size: .7rem; }
            #scale-fab .lbl { font-size: .5rem; padding: 0 4px; min-width: 26px; }
        }
    </style>
@endpush

@section('content')
    {{-- ===== SPLASH LOADER — fade out setelah syncCore() pertama sukses ===== --}}
    <div id="boot-splash">
        <div class="boot-logo">
            <i class="fas fa-satellite-dish boot-icon"></i>
        </div>
        <div class="boot-title">NOC COMMAND INKASA</div>
        <div class="boot-status" id="boot-status">CONNECTING TO CORE…</div>
        <div class="boot-bar"><div class="boot-bar-fill"></div></div>
    </div>

    <div class="grid-overlay moving-grid"></div>

    {{-- Scale controller FAB (kanan-bawah) --}}
    <div id="scale-fab" role="group" aria-label="UI scale controller">
        <button type="button" id="scale-down" title="Perkecil tampilan (Ctrl -)"><i class="fas fa-minus"></i></button>
        <span class="lbl" id="scale-label">100%</span>
        <button type="button" id="scale-up" title="Perbesar tampilan (Ctrl +)"><i class="fas fa-plus"></i></button>
        <button type="button" id="scale-reset" title="Reset skala" style="width: 32px;"><i class="fas fa-rotate-left" style="font-size:.7rem"></i></button>
    </div>

    {{-- Sticky bottom navigation — hanya tampil di mobile (<= 900px) --}}
    @auth
    <nav id="bottom-nav" aria-label="Bottom navigation">
        <a href="#top" class="bn-item" data-target="top">
            <i class="fas fa-tachometer-alt"></i>
            <span>Live</span>
        </a>
        <a href="#cctv" class="bn-item" data-target="cctv">
            <i class="fas fa-video"></i>
            <span>CCTV</span>
        </a>
        <a href="#logs" class="bn-item" data-target="logs">
            <i class="fas fa-list"></i>
            <span>Logs</span>
        </a>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('users.index') }}" class="bn-item">
            <i class="fas fa-users-cog"></i>
            <span>Users</span>
        </a>
        @endif
        <a href="{{ route('devices.index') }}" class="bn-item">
            <i class="fas fa-mobile-alt"></i>
            <span>Devices</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="bn-item">
            <i class="fas fa-user-cog"></i>
            <span>Profil</span>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="bn-item bn-form">
            @csrf
            <button type="submit"><i class="fas fa-power-off"></i><span>Keluar</span></button>
        </form>
    </nav>
    @endauth

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
            <div style="display:flex; align-items:center; gap:18px;">
                @auth
                <div class="user-chip">
                    <div class="user-avatar">
                        @if (auth()->user()->photoUrl())
                            <img src="{{ auth()->user()->photoUrl() }}" alt="avatar">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="user-meta">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">
                            {{ '@' . auth()->user()->username }}
                            @if(auth()->user()->isSuperAdmin())
                                <span style="margin-left:6px;font-size:.5rem;background:rgba(255,0,85,.15);border:1px solid rgba(255,0,85,.3);color:#ff0055;padding:1px 6px;border-radius:10px;letter-spacing:1px;vertical-align:middle"><i class="fas fa-crown" style="font-size:.45rem;margin-right:2px"></i>SUPERADMIN</span>
                            @else
                                <span style="margin-left:6px;font-size:.5rem;background:rgba(0,243,255,.1);border:1px solid rgba(0,243,255,.3);color:var(--cyan);padding:1px 6px;border-radius:10px;letter-spacing:1px;vertical-align:middle"><i class="fas fa-user" style="font-size:.45rem;margin-right:2px"></i>ADMIN</span>
                            @endif
                        </div>
                    </div>
                    <div class="user-actions">
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('users.index') }}" class="user-btn" title="Manajemen User" style="color:#ffcc00;border-color:rgba(255,204,0,0.3);">
                                <i class="fas fa-users-cog"></i>
                            </a>
                        @endif
                        <a href="{{ route('devices.index') }}" class="user-btn" title="Perangkat & Sesi" style="color:var(--green);border-color:rgba(10,255,0,0.3);">
                            <i class="fas fa-mobile-alt"></i>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="user-btn" title="Edit Profile">
                            <i class="fas fa-user-cog"></i>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit" class="user-btn pink" title="Logout">
                                <i class="fas fa-power-off"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endauth
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
                            <span class="sub-val">SYNC: <span id="sync-val" style="color:var(--cyan)">100%</span></span>
                            <span class="sub-val">PROTO: <span style="color:var(--cyan)">HTTP/REST</span></span>
                        </div>
                    </div>
                </div>
                <div class="health-matrix">
                    <div class="matrix-item">
                        <div class="matrix-val" id="total-logs">0</div>
                        <div class="matrix-lab">TOTAL_LOGS</div>
                    </div>
                    <div class="matrix-item" id="last-fetch-tile">
                        <div class="matrix-val" style="color:var(--yellow); font-size: 0.8rem;" id="last-fetch">--:--:--
                        </div>
                        <div class="matrix-lab">LAST_FETCH &middot; AUTO 2s</div>
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
                <form id="reportForm" action="/export" method="GET">
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
                        <button type="button" class="cyber-btn" onclick="setExportFormat('excel')">
                            <i class="fas fa-file-excel"></i> EXCEL
                        </button>

                        <button type="button" class="cyber-btn pdf" onclick="setExportFormat('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </form>
            </div>

            @if(auth()->user()->isSuperAdmin())
            {{-- DATABASE MANAGEMENT: hanya SUPERADMIN --}}
            <div class="glass-card">
                <span class="card-title">DATABASE MANAGEMENT
                    <span style="margin-left:auto; font-family:'JetBrains Mono'; font-size:.5rem; padding: 2px 6px; border-radius:3px; background:rgba(255,0,85,.15); border:1px solid rgba(255,0,85,.3); color:var(--pink); letter-spacing:1px;">SUPERADMIN</span>
                </span>
                <div style="margin-bottom: 15px;">
                    <label class="label-caps"><i class="fas fa-download" style="margin-right:5px"></i> EXPORT
                        SQL</label>
                    <form action="/db/export" method="GET" style="margin-top: 8px;">
                        <div style="display:flex; gap:6px; margin-bottom: 8px;">
                            <input type="date" name="start_date" title="Tanggal mulai (opsional)">
                            <input type="date" name="end_date" title="Tanggal akhir (opsional)">
                        </div>
                        <div style="font-size:0.55rem; color:var(--text-dim); font-family:'JetBrains Mono'; margin-bottom:8px; letter-spacing:1px;">
                            KOSONGKAN = FULL BACKUP
                        </div>
                        <button type="submit" class="cyber-btn" style="width:100%;">
                            <i class="fas fa-database"></i> DOWNLOAD BACKUP
                        </button>
                    </form>
                </div>
                <div>
                    <label class="label-caps"><i class="fas fa-upload" style="margin-right:5px"></i> RESTORE SQL</label>
                    <form action="/db/import" method="POST" enctype="multipart/form-data"
                        style="margin-top: 8px;"
                        data-confirm="Restore akan <strong>MENGGANTI</strong> data tabel sensor_logs saat ini.<br>Aksi ini <strong>tidak dapat dibatalkan</strong>. Lanjutkan?"
                        data-confirm-type="danger"
                        data-confirm-title="RESTORE DATABASE">
                        @csrf
                        <input type="file" name="backup_file" accept=".sql" required style="margin-bottom: 10px;">
                        <div style="font-size:0.55rem; color:var(--text-dim); font-family:'JetBrains Mono'; margin-bottom:8px; letter-spacing:1px;">
                            MAX 64MB &middot; FILE .SQL HASIL EXPORT
                        </div>
                        <button type="submit" class="cyber-btn pdf" style="width:100%;">
                            <i class="fas fa-file-import"></i> UPLOAD & RESTORE
                        </button>
                    </form>
                </div>
            </div>
            @else
            {{-- ADMIN (non-superadmin): tampilkan hint bahwa DB management hanya untuk superadmin --}}
            <div class="glass-card" style="opacity:.7;">
                <span class="card-title">DATABASE MANAGEMENT
                    <span style="margin-left:auto; font-family:'JetBrains Mono'; font-size:.5rem; padding: 2px 6px; border-radius:3px; background:rgba(255,204,0,.12); border:1px solid rgba(255,204,0,.3); color:var(--yellow); letter-spacing:1px;">TERBATAS</span>
                </span>
                <div style="padding:16px 8px; text-align:center; font-family:'JetBrains Mono'; font-size:.65rem; color:var(--text-dim); line-height:1.6;">
                    <i class="fas fa-lock" style="font-size:1.3rem; color:var(--yellow); display:block; margin-bottom:8px; opacity:.7;"></i>
                    Fitur backup &amp; restore database<br>
                    hanya untuk <span style="color:var(--pink); font-weight:700;">SUPERADMIN</span>.<br>
                    <span style="opacity:.6;">Hubungi superadmin jika perlu backup.</span>
                </div>
            </div>
            @endif

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
            <span id="top" style="position:absolute; top:-80px;"></span>
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
                    <div class="val-large" id="f-val" style="color:var(--text-dim);">CLEAR</div>
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
            <div class="split-row split-row-cctv"
                style="display: grid; gap: 15px; margin-top: 15px; align-items: start;">
                <!-- CCTV PREVIEW (full width) -->
                <div id="cctv" class="glass-card" style="display: flex; flex-direction: column;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <span class="card-title"><i class="fas fa-video" style="margin-right: 5px;"></i> LIVE CCTV
                            SURVEILLANCE</span>
                        <div style="display:flex; gap:6px; margin-bottom: 12px;">
                            <span class="node-badge active"
                                style="border-color: var(--cyan); color: var(--cyan); background: rgba(0, 243, 255, 0.1);">1
                                CAM</span>
                            <span class="node-badge active" title="Stream auto-recover saat stall, refresh paksa tiap 5 menit"
                                style="border-color: var(--green); color: var(--green); background: rgba(10, 255, 0, 0.08);">AUTO
                                5m</span>
                        </div>
                    </div>
                    <div class="cctv-grid">
                        <div class="cctv-tile">
                            <div class="cctv-toolbar">
                                <span class="cctv-label">
                                    <span>CAM_02 &middot; 192.168.99.252</span>
                                    <span class="cctv-live" data-status>LIVE</span>
                                </span>
                                <div class="cctv-controls">
                                    <button type="button" title="Audio On/Off" data-act="mute">
                                        <i class="fas fa-volume-mute"></i>
                                    </button>
                                    <button type="button" title="Reload Stream" data-act="reload">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button type="button" title="Fullscreen" data-act="fullscreen">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="cctv-stage">
                                <span class="corner-tr"></span>
                                <span class="corner-bl"></span>
                                <span class="cctv-loading"><i class="fas fa-satellite-dish"
                                        style="display:block; font-size:1.5rem; margin-bottom:6px;"></i> LINK...</span>
                                <video class="cctv-video" data-hls="/cctv-hls/cctv2/index.m3u8?cookieCheck=1"
                                    muted autoplay playsinline></video>
                            </div>
                            <div class="cctv-meta">
                                <span>STATE: <span data-cam-state class="ok">CONNECTING</span></span>
                                <span>LAST_FRAME: <span data-cam-lastframe>--:--:--</span></span>
                            </div>
                        </div>
                    </div>

                    {{-- SYS CLOCK — cyber digital clock di bawah CCTV --}}
                    <div class="sys-clock" aria-label="System clock">
                        <div class="sc-main">
                            <div class="sc-time">
                                <span id="sc-hh">00</span><span class="colon">:</span><span id="sc-mm">00</span><span class="colon">:</span><span id="sc-ss">00</span><span class="ms" id="sc-ms">.000</span>
                            </div>
                            <div class="sc-aux">
                                <div class="sc-badges-inline">
                                    <span class="sc-badge">UTC+07</span>
                                    <span class="sc-badge">WIB</span>
                                    <span class="sc-badge sync" id="sc-sync">SYNC</span>
                                </div>
                                <div class="sc-day" id="sc-day">JUMAT</div>
                                <div class="sc-date-full" id="date">16 OKT 2026</div>
                                <div class="sc-aux-row">
                                    <span>WEEK<strong id="sc-week">42</strong></span>
                                    <span>DOY<strong id="sc-doy">290</strong></span>
                                    <span>EPOCH<strong id="sc-epoch">0</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="sc-progress" title="Progress hari dalam tahun ini">
                            <span class="sc-prog-label"><strong>YEAR_PROGRESS</strong></span>
                            <div class="sc-prog-bar"><div class="sc-prog-fill" id="sc-year-fill" style="width:0%"></div></div>
                            <span class="sc-prog-pct" id="sc-year-pct">0%</span>
                        </div>

                        {{-- Hidden tetap render agar JS lama tidak break --}}
                        <span id="clock" style="display:none"></span>
                    </div>
                </div>

                <!-- MASTER LOG STREAM -->
                <div id="logs" class="glass-card" style="height: 100%; display: flex; flex-direction: column;">
                    <span class="card-title">
                        MASTER LOG STREAM
                        <span style="margin-left:auto; font-family:'JetBrains Mono'; font-size:.55rem; color:var(--text-dim); letter-spacing:1px; opacity:.7;">
                            <span class="pulse-dot" style="width:6px;height:6px;display:inline-block;vertical-align:middle;margin-right:3px;"></span>
                            REAL-TIME
                        </span>
                    </span>
                    <div class="table-wrap log-stream-wrap" style="flex:1; min-height: 300px; max-height: 460px; overflow-y: auto; overflow-x: hidden;">
                        <table class="log-stream-table">
                            <thead style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th class="col-time">WAKTU</th>
                                    <th class="col-num" title="Suhu">°C</th>
                                    <th class="col-num" title="Kelembaban">%</th>
                                    <th class="col-num" title="Kualitas Udara">PPM</th>
                                    <th class="col-stat" title="Status">ST</th>
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

                    {{-- ====== SERVER CHECK REMINDER PANEL (inline di dalam Master Log card) ====== --}}
                    <div id="server-check-panel" class="scheck-panel scheck-panel--inline" hidden>
                        <div class="scheck-left">
                            <div class="scheck-icon">
                                <i class="fas fa-triangle-exclamation"></i>
                            </div>
                            <div class="scheck-text">
                                <div class="scheck-title">HIMBAUAN · CEK RUANG SERVER</div>
                                <div class="scheck-desc" id="scheck-desc">
                                    Belum terdeteksi aktivitas apapun di CCTV sejak pagi.
                                    Mohon lakukan pengecekan fisik ruang server.
                                </div>
                                <div class="scheck-meta">
                                    <span><i class="fas fa-clock"></i> <strong id="scheck-triggered">—</strong></span>
                                    <span><i class="fas fa-film"></i> Motion: <strong id="scheck-count">0</strong></span>
                                    <span><i class="fab fa-telegram"></i> Telegram terkirim</span>
                                </div>
                            </div>
                        </div>
                        <div class="scheck-actions">
                            <button type="button" id="scheck-ack-btn" class="scheck-btn ok">
                                <i class="fas fa-check-circle"></i> SUDAH DICEK
                            </button>
                        </div>
                    </div>

                    {{-- Confirmation banner saat SUDAH di-ack --}}
                    <div id="server-check-ack-banner" class="scheck-ack" hidden>
                        <i class="fas fa-shield-check"></i>
                        Server sudah dicek oleh <strong id="scheck-ack-by">—</strong>
                        · <strong id="scheck-ack-at">—</strong>
                    </div>
                </div>
            </div>
        </main>
    </div>

    @push('scripts')
    <script>
        // --- CORE LOGIC ---
        let isMuted = false;
        let startTime = Date.now();
        let charts = {}; window.charts = charts;

        function setExportFormat(format) {
            const form = document.getElementById('reportForm');
            form.action = (format === 'pdf') ? '/export/pdf' : '/export';
            form.submit();
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
                animation: false,                  // <- perf: no animation
                animations: { colors: false, x: false, y: false },
                transitions: { active: { animation: { duration: 0 } } },
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }    // tooltip mouseover juga GPU-cost
                },
                scales: {
                    x: { display: false },
                    y: {
                        display: true,
                        grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 9, family: 'JetBrains Mono' }, maxTicksLimit: 4 }
                    }
                },
                elements: { point: { radius: 0, hoverRadius: 0 } }
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
                const ctrl = new AbortController();
                const to = setTimeout(() => ctrl.abort(), 8000);   // hard timeout 8 dtk
                const resp = await fetch("/api/live-data?t=" + Date.now(), { signal: ctrl.signal });
                clearTimeout(to);
                const data = await resp.json();
                if (data.status === 'success') {
                    window.lastConfig = data.config;
                    updateCoreUI(data);
                    if (data.history && data.history.length > 0) {
                        updateCoreCharts(data.history);
                    }
                    updateCoreLogs(data.recent);
                    hideBootSplash();   // <- splash hilang setelah data pertama masuk
                }
            } catch (e) {
                console.error("Sync Error:", e);
                document.getElementById('conn-text').innerText = "CORE OFFLINE";
                document.getElementById('conn-text').style.color = "var(--pink)";
                const bs = document.getElementById('boot-status');
                if (bs) bs.innerText = 'SERVER LAMBAT — RETRYING…';
            }
        }

        function hideBootSplash() {
            const s = document.getElementById('boot-splash');
            if (!s || s.classList.contains('hide')) return;
            s.classList.add('hide');
            setTimeout(() => s.remove(), 400);
        }
        // Safety net: jangan biarkan splash menutupi UI > 15 dtk walau koneksi buruk
        setTimeout(hideBootSplash, 15000);

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

            // Last Fetch Display + flash sebagai signal AUTO REFRESH aktif
            const lastDate = new Date(latest.created_at);
            document.getElementById('last-fetch').innerText = lastDate.toLocaleTimeString('id-ID');
            const tile = document.getElementById('last-fetch-tile');
            if (tile) {
                tile.classList.remove('flash');
                void tile.offsetWidth;
                tile.classList.add('flash');
            }

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

                    // Cyber toast (subtle, non-blocking) — hanya saat transisi ke DANGER
                    if (window.__alarmWasCritical !== true) {
                        if (window.NocCyber) NocCyber.error(fullMsg, { title: 'ALARM AKTIF', duration: 6000 });
                        if (window.NocPwa)   NocPwa.alarmVibrate();
                    }

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
                window.__alarmWasCritical = true;
            } else {
                log.innerText = `> [${new Date().toLocaleTimeString()}] SYSTEM CHECK: OK`;
                log.style.color = "var(--text-dim)";
                overlay.style.display = 'none';
                // Transisi DANGER -> NORMAL: kabari user dgn toast hijau
                if (window.__alarmWasCritical === true && window.NocCyber) {
                    NocCyber.success('Kondisi kembali NORMAL', { title: 'STATUS AMAN', duration: 4500 });
                }
                window.__alarmWasCritical = false;
            }
        }

        const CHART_MAX_POINTS = 60;   // cap supaya chart tidak akumulasi & ringan

        function updateCoreCharts(history) {
            // potong ke N point terakhir
            const h = history.length > CHART_MAX_POINTS ? history.slice(-CHART_MAX_POINTS) : history;
            const labels = h.map(r => new Date(r.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));

            charts.temp.data.labels = labels;
            charts.temp.data.datasets[0].data = h.map(r => r.temp);
            charts.temp.update('none');

            charts.hum.data.labels = labels;
            charts.hum.data.datasets[0].data = h.map(r => r.hum);
            charts.hum.update('none');

            charts.smoke.data.labels = labels;
            charts.smoke.data.datasets[0].data = h.map(r => r.smoke || 0);
            charts.smoke.data.datasets[1].data = h.map(r => r.smoke1 || 0);
            charts.smoke.data.datasets[2].data = h.map(r => r.smoke2 || 0);
            charts.smoke.data.datasets[3].data = h.map(r => r.smoke3 || 0);
            charts.smoke.update('none');

            if (charts.fire) {
                charts.fire.data.labels = labels;
                charts.fire.data.datasets[0].data = h.map(r => (r.flame1 == 1) ? 1 : 0);
                charts.fire.data.datasets[1].data = h.map(r => (r.flame2 == 1) ? 1 : 0);
                charts.fire.update('none');
            }
        }

        function updateCoreLogs(recent) {
            const tbody = document.getElementById('logs-table-body');
            const tLimit = window.lastConfig?.temp_limit || 35;
            const sLimit = window.lastConfig?.smoke_limit || 1000;

            tbody.innerHTML = recent.map(log => {
                const t = parseFloat(log.temp);
                const s = parseInt(log.smoke, 10);
                const tempOver  = t > tLimit;
                const smokeOver = s > sLimit;
                const fire = !!log.fire;
                const danger  = tempOver || smokeOver || fire;
                const warn    = !danger && (t > tLimit * 0.9 || s > sLimit * 0.8);

                // Format waktu pendek — hh:mm:ss
                const dt = new Date(log.created_at);
                const hhmmss = dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });

                // Status: icon-only pill + tooltip
                let statusIcon, statusCls, statusLabel;
                if (danger) { statusIcon = 'fa-triangle-exclamation'; statusCls = 'err';  statusLabel = fire ? 'FIRE DETECTED' : (tempOver ? 'TEMP OVER' : 'SMOKE OVER'); }
                else if (warn) { statusIcon = 'fa-circle-exclamation'; statusCls = 'warn'; statusLabel = 'MENDEKATI AMBANG'; }
                else { statusIcon = 'fa-check'; statusCls = 'ok'; statusLabel = 'SECURE'; }

                return `
                <tr class="${danger ? 'row-danger' : ''}" title="${statusLabel} · Temp ${t.toFixed(1)}°C · Hum ${parseFloat(log.hum).toFixed(1)}% · AQS ${s} PPM">
                    <td class="log-time" style="color:var(--cyan); font-family:'JetBrains Mono';">${hhmmss}</td>
                    <td style="font-weight:700; color:${tempOver ? 'var(--pink)' : 'inherit'}">${t.toFixed(1)}</td>
                    <td>${parseFloat(log.hum).toFixed(1)}</td>
                    <td style="color:${smokeOver ? 'var(--pink)' : 'inherit'}">${s}</td>
                    <td><span class="log-status ${statusCls}" aria-label="${statusLabel}"><i class="fas ${statusIcon}"></i></span></td>
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

        // ===== SYS_CLOCK — cyber digital clock tick =====
        (function sysClockTick() {
            const $ = (id) => document.getElementById(id);
            const pad = (n, w = 2) => String(n).padStart(w, '0');

            // ISO week number
            function isoWeek(d) {
                const dt = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
                const dow = dt.getUTCDay() || 7;
                dt.setUTCDate(dt.getUTCDate() + 4 - dow);
                const yStart = new Date(Date.UTC(dt.getUTCFullYear(), 0, 1));
                return Math.ceil((((dt - yStart) / 86400000) + 1) / 7);
            }
            // Day of year (1-366)
            function dayOfYear(d) {
                const start = new Date(d.getFullYear(), 0, 0);
                return Math.floor((d - start) / 86400000);
            }
            function daysInYear(y) {
                return ((y % 4 === 0 && y % 100 !== 0) || y % 400 === 0) ? 366 : 365;
            }

            const dayNames = ['MINGGU','SENIN','SELASA','RABU','KAMIS','JUMAT','SABTU'];
            const monthNames = ['JAN','FEB','MAR','APR','MEI','JUN','JUL','AGU','SEP','OKT','NOV','DES'];

            function tick() {
                const now = new Date();
                const hh = pad(now.getHours());
                const mm = pad(now.getMinutes());
                const ss = pad(now.getSeconds());
                const ms = pad(now.getMilliseconds(), 3);

                if ($('sc-hh')) $('sc-hh').textContent = hh;
                if ($('sc-mm')) $('sc-mm').textContent = mm;
                if ($('sc-ss')) $('sc-ss').textContent = ss;
                if ($('sc-ms')) $('sc-ms').textContent = '.' + ms;

                // Legacy hidden #clock supaya script lain (kalau ada) tidak break
                const cl = $('clock'); if (cl) cl.textContent = `${hh}:${mm}:${ss}`;

                // Day & date
                if ($('sc-day'))  $('sc-day').textContent  = dayNames[now.getDay()];
                if ($('date'))    $('date').textContent    = `${now.getDate()} ${monthNames[now.getMonth()]} ${now.getFullYear()}`;

                // Aux: week, doy, epoch
                if ($('sc-week'))  $('sc-week').textContent  = pad(isoWeek(now));
                const doy = dayOfYear(now);
                if ($('sc-doy'))   $('sc-doy').textContent   = pad(doy, 3);
                if ($('sc-epoch')) $('sc-epoch').textContent = Math.floor(now.getTime() / 1000);

                // Year progress bar
                const diy = daysInYear(now.getFullYear());
                const pct = Math.min(100, (doy / diy) * 100);
                if ($('sc-year-fill')) $('sc-year-fill').style.width = pct.toFixed(2) + '%';
                if ($('sc-year-pct'))  $('sc-year-pct').textContent  = pct.toFixed(1) + '%';
            }

            tick();
            // Update tiap ~90ms supaya milis bergerak smooth tanpa over-render
            setInterval(tick, 90);
        })();

        window.onload = () => {
            initMasterCharts();
            syncCore();
            setInterval(syncCore, 2000);   // 2 dtk -> mendekati real-time
            if (window.Notification && Notification.permission !== "denied") Notification.requestPermission();

            // ===== CCTV via HLS (auto-recover + control) =====
            const CAM_STALL_TICKS    = 4;        // 4 x 2dtk = ~8 dtk stuck -> reattach
            const CAM_PERIODIC_MS    = 5 * 60 * 1000;   // refresh paksa tiap 5 menit
            const CAM_VISIBILITY_REC = 2000;     // delay reattach saat tab kembali visible

            function setCamStatus(video, state) {
                const tile = video.closest('.cctv-tile'); if (!tile) return;
                const live = tile.querySelector('[data-status]');
                const txt  = tile.querySelector('[data-cam-state]');
                if (state === 'live') {
                    live && (live.classList.remove('stalled'), live.textContent = 'LIVE');
                    txt  && (txt.className = 'ok', txt.textContent = 'STREAMING');
                } else if (state === 'stalled') {
                    live && (live.classList.add('stalled'), live.textContent = 'STALL');
                    txt  && (txt.className = 'warn', txt.textContent = 'RECONNECTING');
                } else if (state === 'connecting') {
                    live && (live.classList.add('stalled'), live.textContent = 'LINK');
                    txt  && (txt.className = 'warn', txt.textContent = 'CONNECTING');
                }
            }
            function fitStageToVideo(video) {
                if (!video.videoWidth || !video.videoHeight) return;
                const stage = video.closest('.cctv-stage'); if (!stage) return;
                const r = (video.videoWidth / video.videoHeight).toFixed(4);
                if (stage.dataset.ratio === r) return;
                stage.dataset.ratio = r;
                stage.style.aspectRatio = video.videoWidth + ' / ' + video.videoHeight;
            }
            function markFrameTime(video) {
                const tile = video.closest('.cctv-tile'); if (!tile) return;
                const el = tile.querySelector('[data-cam-lastframe]');
                if (el) el.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
            }

            function attachCam(video) {
                const url = video.dataset.hls;
                if (!url) return;
                if (video._hls) { try { video._hls.destroy(); } catch (e) {} video._hls = null; }
                setCamStatus(video, 'connecting');
                // bust HLS playlist cache to force fresh manifest
                const bustUrl = url + (url.includes('?') ? '&' : '?') + '_t=' + Date.now();

                if (window.Hls && Hls.isSupported()) {
                    const hls = new Hls({
                        lowLatencyMode: true,
                        liveSyncDurationCount: 1,
                        liveMaxLatencyDurationCount: 4,
                        backBufferLength: 5,
                        manifestLoadingMaxRetry: 8,
                        manifestLoadingRetryDelay: 1000,
                        levelLoadingMaxRetry: 8
                    });
                    hls.loadSource(bustUrl);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED, () => video.play().catch(() => {}));
                    hls.on(Hls.Events.FRAG_BUFFERED, () => { markFrameTime(video); setCamStatus(video, 'live'); fitStageToVideo(video); });
                    hls.on(Hls.Events.ERROR, (evt, data) => {
                        if (data && data.fatal) {
                            setCamStatus(video, 'stalled');
                            setTimeout(() => attachCam(video), 4000);
                        }
                    });
                    video._hls = hls;
                } else {
                    // Safari / iOS: HLS native
                    video.src = bustUrl;
                    video.play().catch(() => {});
                }
            }

            function watchStall(video) {
                let lastTime = 0, stuckTicks = 0;
                video._stallTimer && clearInterval(video._stallTimer);
                video._stallTimer = setInterval(() => {
                    if (document.hidden) return;
                    if (video.paused || video.readyState < 2) return;
                    if (Math.abs(video.currentTime - lastTime) < 0.05) {
                        stuckTicks++;
                        if (stuckTicks === 2) setCamStatus(video, 'stalled');
                        if (stuckTicks >= CAM_STALL_TICKS) {
                            stuckTicks = 0;
                            attachCam(video);
                        }
                    } else {
                        stuckTicks = 0;
                        markFrameTime(video);
                        setCamStatus(video, 'live');
                    }
                    lastTime = video.currentTime;
                }, 2000);

                // periodic forced refresh
                video._periodic && clearInterval(video._periodic);
                video._periodic = setInterval(() => attachCam(video), CAM_PERIODIC_MS);
            }

            function camToolbarBind(tile) {
                const video = tile.querySelector('.cctv-video');
                tile.querySelectorAll('[data-act]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const act = btn.dataset.act;
                        if (act === 'mute') {
                            video.muted = !video.muted;
                            btn.querySelector('i').className = video.muted
                                ? 'fas fa-volume-mute' : 'fas fa-volume-up';
                            if (!video.muted) video.play().catch(() => {});
                        } else if (act === 'reload') {
                            attachCam(video);
                        } else if (act === 'fullscreen') {
                            const stage = tile.querySelector('.cctv-stage');
                            const fn = stage.requestFullscreen
                                    || stage.webkitRequestFullscreen
                                    || stage.msRequestFullscreen;
                            if (document.fullscreenElement) {
                                (document.exitFullscreen || document.webkitExitFullscreen).call(document);
                            } else if (fn) fn.call(stage);
                        }
                    });
                });
            }

            // Saat tab kembali aktif setelah lama hidden -> force reattach (HLS sering mati)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) return;
                setTimeout(() => {
                    document.querySelectorAll('.cctv-video').forEach(attachCam);
                }, CAM_VISIBILITY_REC);
            });

            document.querySelectorAll('.cctv-tile').forEach(tile => {
                const v = tile.querySelector('.cctv-video');
                if (!v) return;
                v.addEventListener('loadedmetadata', () => fitStageToVideo(v));
                camToolbarBind(tile);
                attachCam(v);
                watchStall(v);
            });

            // (Hard-reload 1 jam dihapus -> kalau koneksi buruk saat reload, browser blank.
            //  Chart update('none') + cap 60 point + visibility re-attach HLS sudah cukup.)
        };

        // ===== SCALE CONTROLLER (font-size based) =====
        // Ubah root font-size supaya rem/em scale, TAPI viewport tetap sama
        // (media queries tetap valid + tidak konflik dgn browser zoom Ctrl+/-).
        (function () {
            const KEY = 'noc-ui-scale';
            const MIN = 0.8, MAX = 1.3, STEP = 0.05;
            const BASE_PX = 16; // baseline root font-size
            const lblEl = document.getElementById('scale-label');
            const applyScale = (s) => {
                s = Math.min(MAX, Math.max(MIN, Math.round(s * 100) / 100));
                document.documentElement.style.fontSize = (BASE_PX * s) + 'px';
                if (lblEl) lblEl.textContent = Math.round(s * 100) + '%';
                localStorage.setItem(KEY, s.toFixed(2));
                return s;
            };
            let current = parseFloat(localStorage.getItem(KEY) || '1') || 1;
            applyScale(current);
            const $ = id => document.getElementById(id);
            $('scale-up')    && $('scale-up').addEventListener('click',    () => { current = applyScale(current + STEP); });
            $('scale-down')  && $('scale-down').addEventListener('click',  () => { current = applyScale(current - STEP); });
            $('scale-reset') && $('scale-reset').addEventListener('click', () => {
                current = applyScale(1);
                if (window.NocCyber) NocCyber.info('Skala UI direset ke 100%');
            });

            // Keyboard shortcut: Ctrl/Cmd + Alt + [+/-/0] — HANYA UI scale, bukan browser zoom
            window.addEventListener('keydown', (e) => {
                if (!(e.ctrlKey || e.metaKey) || !e.altKey) return;
                if (e.key === '+' || e.key === '=') { e.preventDefault(); current = applyScale(current + STEP); }
                else if (e.key === '-')             { e.preventDefault(); current = applyScale(current - STEP); }
                else if (e.key === '0')             { e.preventDefault(); current = applyScale(1); }
            });

            // Pastikan chart.js resize kalau user browser-zoom (Ctrl+/-)
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (window.charts) {
                        Object.values(window.charts || {}).forEach(c => c && c.resize && c.resize());
                    }
                }, 120);
            });
        })();

        // ===== PULL-TO-REFRESH indicator (mobile) =====
        // Nunjukin toast saat user swipe-down di atas dashboard.
        (function () {
            if (!('ontouchstart' in window) || window.innerWidth > 768) return;
            let startY = null, pulled = 0;
            window.addEventListener('touchstart', e => {
                if (window.scrollY <= 0 && e.touches[0]) startY = e.touches[0].clientY;
            }, { passive: true });
            window.addEventListener('touchmove', e => {
                if (startY === null) return;
                pulled = e.touches[0].clientY - startY;
            }, { passive: true });
            window.addEventListener('touchend', () => {
                if (pulled > 90 && window.scrollY <= 0) {
                    if (window.NocCyber) NocCyber.info('Menyegarkan data...', { duration: 1500 });
                    setTimeout(() => location.reload(), 400);
                }
                startY = null; pulled = 0;
            });
        })();

        // ===== SERVER-CHECK REMINDER (polling flag himbauan) =====
        (function serverCheckWatcher() {
            const panel     = document.getElementById('server-check-panel');
            const ackBanner = document.getElementById('server-check-ack-banner');
            const btn       = document.getElementById('scheck-ack-btn');
            if (!panel || !btn) return;

            const $ = id => document.getElementById(id);
            const csrf = () => (document.querySelector('meta[name="csrf-token"]')?.content) || '';

            let lastWasActive = false;

            function fmtTime(iso) {
                if (!iso) return '—';
                try {
                    return new Date(iso).toLocaleTimeString('id-ID', { hour12: false }) +
                           ' · ' + new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
                } catch { return iso; }
            }

            async function poll() {
                try {
                    const r = await fetch('/api/server-check/status', { headers: { 'Accept': 'application/json' } });
                    if (!r.ok) return;
                    const data = await r.json();

                    // ---- PANEL himbauan ----
                    if (data.active) {
                        panel.hidden = false;
                        if (data.payload) {
                            $('scheck-triggered').textContent = fmtTime(data.payload.triggered_at);
                            $('scheck-count').textContent = String(data.payload.motion_count ?? 0);
                        }
                        // Trigger notif cyber + vibrate SEKALI saat baru muncul
                        if (!lastWasActive) {
                            if (window.NocCyber) NocCyber.warning(
                                'Himbauan: cek ruang server. Belum ada motion sejak pagi.',
                                { title: 'CEK SERVER', duration: 8000 }
                            );
                            if (window.NocPwa) NocPwa.alarmVibrate();
                        }
                    } else {
                        panel.hidden = true;
                    }
                    lastWasActive = !!data.active;

                    // ---- ACK banner (muncul kalau tadi ada dicek) ----
                    if (!data.active && data.ack_by && data.ack_at) {
                        ackBanner.hidden = false;
                        $('scheck-ack-by').textContent = data.ack_by;
                        $('scheck-ack-at').textContent = fmtTime(data.ack_at);
                    } else if (!data.ack_by) {
                        ackBanner.hidden = true;
                    }
                } catch (e) { /* silent */ }
            }

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> MEMPROSES...';
                try {
                    const r = await fetch('/api/server-check/ack', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf(),
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    if (r.ok) {
                        if (window.NocCyber) NocCyber.success('Terima kasih sudah mengecek server!', { title: 'ACK TERKIRIM' });
                        // Hide langsung + reset state biar tidak kedip saat next poll
                        panel.hidden = true;
                        lastWasActive = false;
                        await poll();
                        // Auto-hide ack banner setelah 20 detik supaya tidak numpuk
                        setTimeout(() => { if (ackBanner) ackBanner.hidden = true; }, 20_000);
                    } else {
                        const text = await r.text().catch(() => '');
                        console.warn('ACK fail HTTP ' + r.status, text.slice(0, 200));
                        if (window.NocCyber) NocCyber.error('Gagal ack (HTTP ' + r.status + '). Refresh page lalu coba lagi.');
                    }
                } catch (e) {
                    console.error('ACK error:', e);
                    if (window.NocCyber) NocCyber.error('Error: ' + e.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> SUDAH DICEK';
                }
            });

            // Poll pertama saat page load, lalu tiap 60 detik
            poll();
            setInterval(poll, 60_000);
        })();

    </script>
    @endpush
@endsection