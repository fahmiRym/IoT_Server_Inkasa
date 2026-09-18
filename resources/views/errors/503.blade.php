<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center — Sedang Maintenance</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="theme-color" content="#020408">
    @if(!empty($__env->yieldContent('retry_after')) || isset($exception))
        {{-- Auto refresh tiap 30 detik biar user tau kapan kelar --}}
        <meta http-equiv="refresh" content="30">
    @else
        <meta http-equiv="refresh" content="30">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; min-height: 100vh; }
        body {
            background-color: var(--bg-deep);
            color: var(--text-vibrant);
            font-family: 'Rajdhani', sans-serif;
            background-image:
                radial-gradient(circle at 10% 10%, rgba(0, 243, 255, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(255, 0, 85, 0.10) 0%, transparent 40%),
                url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
        }

        /* GRID BACKDROP */
        .grid-overlay {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
            opacity: 0.2;
            mask-image: radial-gradient(circle, black, transparent 80%);
        }

        /* SCANLINE */
        .scanline {
            position: fixed;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            box-shadow: 0 0 12px var(--cyan);
            animation: scan 4s linear infinite;
            z-index: 1;
            pointer-events: none;
        }
        @keyframes scan {
            0% { transform: translateY(0); }
            100% { transform: translateY(100vh); }
        }

        .maint-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 640px;
            background: var(--panel);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 36px;
            text-align: center;
            box-shadow:
                0 0 40px rgba(0, 243, 255, 0.08),
                inset 0 0 20px rgba(0, 243, 255, 0.03);
        }
        .maint-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            padding: 1px;
            background: linear-gradient(135deg, var(--cyan), transparent 60%, var(--pink));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            pointer-events: none;
            opacity: 0.5;
        }

        .brand-line {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            letter-spacing: 3px;
            color: var(--cyan);
            margin-bottom: 24px;
            text-transform: uppercase;
        }
        .brand-line .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--yellow);
            box-shadow: 0 0 10px var(--yellow);
            animation: pulse 1.2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }

        .icon-wrap {
            width: 110px; height: 110px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 204, 0, 0.15), transparent 70%);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--yellow);
            box-shadow: 0 0 30px rgba(255, 204, 0, 0.35), inset 0 0 20px rgba(255, 204, 0, 0.1);
            animation: floaty 3s ease-in-out infinite;
        }
        .icon-wrap i {
            font-size: 46px;
            color: var(--yellow);
            filter: drop-shadow(0 0 8px var(--yellow));
        }
        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 30px;
            font-weight: 900;
            letter-spacing: 2px;
            margin: 0 0 8px;
            background: linear-gradient(90deg, var(--cyan), var(--yellow));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            color: var(--text-dim);
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .message-box {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border);
            border-left: 3px solid var(--yellow);
            border-radius: 8px;
            padding: 16px 20px;
            text-align: left;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--text-vibrant);
            margin-bottom: 24px;
            line-height: 1.7;
        }
        .message-box .label {
            color: var(--cyan);
            font-weight: 700;
        }
        .message-box .val {
            color: var(--yellow);
        }

        .status-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 22px;
        }
        .status-cell {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 8px;
        }
        .status-cell .k {
            font-family: 'Orbitron', sans-serif;
            font-size: 9px;
            letter-spacing: 1.5px;
            color: var(--text-dim);
            margin-bottom: 6px;
        }
        .status-cell .v {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
        }
        .v.on   { color: var(--green); text-shadow: 0 0 6px var(--green); }
        .v.warn { color: var(--yellow); text-shadow: 0 0 6px var(--yellow); }
        .v.off  { color: var(--pink); text-shadow: 0 0 6px var(--pink); }

        .footer-note {
            color: var(--text-dim);
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .footer-note .contact {
            color: var(--cyan);
            font-weight: 600;
        }

        .countdown {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--text-dim);
            margin-top: 14px;
        }
        .countdown .num {
            color: var(--cyan);
            font-weight: 700;
        }

        @media (max-width: 480px) {
            .maint-card { padding: 30px 22px; }
            h1 { font-size: 22px; }
            .status-row { grid-template-columns: 1fr; }
            .icon-wrap { width: 90px; height: 90px; }
            .icon-wrap i { font-size: 38px; }
        }
    </style>
</head>
<body>
    <div class="grid-overlay"></div>
    <div class="scanline"></div>

    <div class="maint-card">
        <div class="brand-line">
            <span class="dot"></span>
            <span>NOC Command Center · Inkasa</span>
            <span class="dot"></span>
        </div>

        <div class="icon-wrap">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <h1>SYSTEM MAINTENANCE</h1>
        <div class="subtitle">// KALIBRASI SENSOR SEDANG BERLANGSUNG</div>

        <div class="message-box">
            <div><span class="label">STATUS_</span> <span class="val">MAINTENANCE_MODE</span></div>
            <div><span class="label">REASON_</span> <span class="val">Kalibrasi &amp; peningkatan sistem</span></div>
            <div><span class="label">ETA____</span> <span class="val">Beberapa saat lagi</span></div>
            <div><span class="label">ACTION_</span> Halaman akan reload otomatis tiap <span class="val">30 detik</span></div>
        </div>

        <div class="status-row">
            <div class="status-cell">
                <div class="k">DASHBOARD</div>
                <div class="v off">OFFLINE</div>
            </div>
            <div class="status-cell">
                <div class="k">SENSOR API</div>
                <div class="v warn">PAUSED</div>
            </div>
            <div class="status-cell">
                <div class="k">CCTV STREAM</div>
                <div class="v on">STANDBY</div>
            </div>
        </div>

        <div class="footer-note">
            Untuk informasi lebih lanjut, hubungi tim NOC:
            <span class="contact">edp@inkalum.com</span>
        </div>
        <div class="countdown">
            <i class="fa-solid fa-rotate"></i>
            Reload otomatis dalam <span class="num" id="cd">30</span>s
        </div>
    </div>

    <script>
        (function(){
            var el = document.getElementById('cd');
            var s = 30;
            setInterval(function(){
                s--;
                if (s < 0) s = 0;
                if (el) el.textContent = s;
            }, 1000);
        })();
    </script>
</body>
</html>
