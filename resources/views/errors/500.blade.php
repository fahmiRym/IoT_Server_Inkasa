<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center — 500 Server Error</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="theme-color" content="#020408">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #020408;
            --panel: rgba(10, 15, 25, 0.7);
            --border: rgba(255, 0, 85, 0.20);
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
            background-color: var(--bg-deep); color: var(--text-vibrant);
            font-family: 'Rajdhani', sans-serif;
            background-image:
                radial-gradient(circle at 10% 10%, rgba(255, 0, 85, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(0, 243, 255, 0.08) 0%, transparent 40%),
                url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
            display: flex; align-items: center; justify-content: center;
            padding: 24px; overflow-x: hidden;
        }
        .grid-overlay {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none; z-index: 0; opacity: 0.2;
            mask-image: radial-gradient(circle, black, transparent 80%);
        }
        .scanline {
            position: fixed; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--pink), transparent);
            box-shadow: 0 0 12px var(--pink);
            animation: scan 3s linear infinite;
            z-index: 1; pointer-events: none;
        }
        @keyframes scan { 0% { transform: translateY(0); } 100% { transform: translateY(100vh); } }

        .maint-card {
            position: relative; z-index: 2;
            width: 100%; max-width: 660px;
            background: var(--panel);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border); border-radius: 16px;
            padding: 40px 36px; text-align: center;
            box-shadow: 0 0 40px rgba(255, 0, 85, 0.14), inset 0 0 20px rgba(255, 0, 85, 0.04);
        }
        .maint-card::before {
            content: ''; position: absolute; inset: 0; border-radius: 16px; padding: 1px;
            background: linear-gradient(135deg, var(--pink), transparent 60%, var(--yellow));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude;
            pointer-events: none; opacity: 0.6;
        }
        .brand-line {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            font-family: 'Orbitron', sans-serif; font-size: 13px; letter-spacing: 3px;
            color: var(--pink); margin-bottom: 22px; text-transform: uppercase;
        }
        .brand-line .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--pink); box-shadow: 0 0 12px var(--pink);
            animation: pulse 1s ease-in-out infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(0.75); } }

        .icon-wrap {
            width: 110px; height: 110px; margin: 0 auto 14px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 0, 85, 0.18), transparent 70%);
            display: flex; align-items: center; justify-content: center;
            border: 2px solid var(--pink);
            box-shadow: 0 0 30px rgba(255, 0, 85, 0.45), inset 0 0 20px rgba(255, 0, 85, 0.1);
            animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) infinite;
        }
        .icon-wrap i {
            font-size: 46px; color: var(--pink);
            filter: drop-shadow(0 0 8px var(--pink));
        }
        @keyframes shake {
            0%, 100% { transform: translate(0, 0) rotate(0); }
            25% { transform: translate(-1px, 1px) rotate(-1deg); }
            75% { transform: translate(1px, -1px) rotate(1deg); }
        }

        .code-huge {
            font-family: 'Orbitron', sans-serif;
            font-size: 68px; font-weight: 900; letter-spacing: 6px;
            background: linear-gradient(90deg, var(--pink), var(--yellow));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin: 4px 0 2px;
        }
        h1 {
            font-family: 'Orbitron', sans-serif; font-size: 22px; font-weight: 700;
            letter-spacing: 3px; margin: 0 0 6px;
            color: var(--text-vibrant); text-transform: uppercase;
        }
        .subtitle {
            color: var(--text-dim); font-size: 13px; letter-spacing: 1px;
            text-transform: uppercase; margin-bottom: 22px;
        }
        .message-box {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border); border-left: 3px solid var(--pink);
            border-radius: 8px; padding: 16px 20px; text-align: left;
            font-family: 'JetBrains Mono', monospace; font-size: 13px;
            color: var(--text-vibrant); margin-bottom: 22px; line-height: 1.7;
        }
        .message-box .label { color: var(--cyan); font-weight: 700; }
        .message-box .val { color: var(--pink); }
        .actions {
            display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: 10px; cursor: pointer;
            font-family: 'Orbitron', sans-serif; font-size: 12px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase; text-decoration: none;
            border: none;
        }
        .btn.primary {
            color: var(--bg-deep); background: linear-gradient(90deg, var(--cyan), #4dfff5);
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
        }
        .btn.ghost {
            color: var(--text-vibrant); background: transparent;
            border: 1px solid var(--border);
        }
        .btn:hover { transform: translateY(-2px); }
        .footer-note { color: var(--text-dim); font-size: 12px; letter-spacing: 0.5px; margin-top: 20px; }
        .footer-note .contact { color: var(--cyan); font-weight: 600; }
        @media (max-width: 480px) {
            .maint-card { padding: 30px 22px; }
            .code-huge { font-size: 52px; }
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
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="code-huge">500</div>
        <h1>CORE MALFUNCTION</h1>
        <div class="subtitle">// INTERNAL_SERVER_ERROR</div>

        <div class="message-box">
            <div><span class="label">STATUS_</span> <span class="val">500 — SERVER_FAULT</span></div>
            <div><span class="label">TRACE__</span> <span class="val">Log dicatat, tim NOC diberitahu</span></div>
            <div><span class="label">ACTION_</span> Coba refresh sebentar lagi.</div>
            <div><span class="label">TIME___</span> {{ now()->format('Y-m-d H:i:s') }}</div>
        </div>

        <div class="actions">
            <a class="btn primary" href="{{ url('/') }}">
                <i class="fa-solid fa-arrow-left"></i> DASHBOARD
            </a>
            <a class="btn ghost" href="javascript:location.reload()">
                <i class="fa-solid fa-rotate"></i> RELOAD
            </a>
        </div>

        <div class="footer-note">
            Butuh bantuan? Hubungi tim NOC: <span class="contact">edp@inkalum.com</span>
        </div>
    </div>
</body>
</html>
