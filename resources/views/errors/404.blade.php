<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center — 404 Not Found</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="theme-color" content="#020408">
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
            animation: scan 4s linear infinite;
            z-index: 1; pointer-events: none;
        }
        @keyframes scan { 0% { transform: translateY(0); } 100% { transform: translateY(100vh); } }

        .maint-card {
            position: relative; z-index: 2;
            width: 100%; max-width: 640px;
            background: var(--panel);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 36px;
            text-align: center;
            box-shadow: 0 0 40px rgba(255, 0, 85, 0.08), inset 0 0 20px rgba(0, 243, 255, 0.03);
        }
        .maint-card::before {
            content: ''; position: absolute; inset: 0; border-radius: 16px; padding: 1px;
            background: linear-gradient(135deg, var(--pink), transparent 60%, var(--cyan));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude;
            pointer-events: none; opacity: 0.5;
        }

        .brand-line {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            font-family: 'Orbitron', sans-serif; font-size: 13px; letter-spacing: 3px;
            color: var(--cyan); margin-bottom: 24px; text-transform: uppercase;
        }
        .brand-line .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--pink); box-shadow: 0 0 10px var(--pink);
            animation: pulse 1.2s ease-in-out infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.85); } }

        .glitch {
            font-family: 'Orbitron', sans-serif;
            font-size: 90px; font-weight: 900; letter-spacing: 6px;
            background: linear-gradient(90deg, var(--pink), var(--cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative; display: inline-block;
            text-shadow: 0 0 30px rgba(255, 0, 85, 0.3);
        }
        .glitch::before, .glitch::after {
            content: '404'; position: absolute; top: 0; left: 0; width: 100%;
            background: transparent; -webkit-text-fill-color: initial;
            opacity: 0.7;
        }
        .glitch::before { color: var(--cyan); animation: glitch-1 2.5s infinite; clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%); }
        .glitch::after { color: var(--pink); animation: glitch-2 2.5s infinite; clip-path: polygon(0 55%, 100% 55%, 100% 100%, 0 100%); }
        @keyframes glitch-1 {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 1px); }
            40% { transform: translate(2px, -1px); }
            60% { transform: translate(-1px, 2px); }
            80% { transform: translate(1px, -2px); }
        }
        @keyframes glitch-2 {
            0%, 100% { transform: translate(0); }
            25% { transform: translate(2px, -1px); }
            50% { transform: translate(-2px, 1px); }
            75% { transform: translate(1px, 2px); }
        }

        h1 {
            font-family: 'Orbitron', sans-serif; font-size: 22px; font-weight: 700;
            letter-spacing: 3px; margin: 6px 0 6px;
            color: var(--text-vibrant); text-transform: uppercase;
        }
        .subtitle {
            color: var(--text-dim); font-size: 13px; letter-spacing: 1px;
            text-transform: uppercase; margin-bottom: 24px;
        }
        .message-box {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border); border-left: 3px solid var(--pink);
            border-radius: 8px; padding: 16px 20px; text-align: left;
            font-family: 'JetBrains Mono', monospace; font-size: 13px;
            color: var(--text-vibrant); margin-bottom: 24px; line-height: 1.7;
        }
        .message-box .label { color: var(--cyan); font-weight: 700; }
        .message-box .val { color: var(--pink); }
        .btn-home {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 26px; border-radius: 10px;
            font-family: 'Orbitron', sans-serif; font-size: 13px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase; text-decoration: none;
            color: var(--bg-deep); background: linear-gradient(90deg, var(--cyan), #4dfff5);
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-home:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(0, 243, 255, 0.6); }
        .footer-note { color: var(--text-dim); font-size: 12px; letter-spacing: 0.5px; margin-top: 18px; }
        .footer-note .contact { color: var(--cyan); font-weight: 600; }
        @media (max-width: 480px) {
            .maint-card { padding: 30px 22px; }
            .glitch { font-size: 64px; letter-spacing: 4px; }
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

        <div class="glitch">404</div>
        <h1>SIGNAL LOST</h1>
        <div class="subtitle">// PAGE_NOT_FOUND</div>

        <div class="message-box">
            <div><span class="label">STATUS_</span> <span class="val">404 — NOT_FOUND</span></div>
            <div><span class="label">PATH___</span> <span class="val">{{ request()->path() }}</span></div>
            <div><span class="label">REASON_</span> Resource tidak tersedia di server ini.</div>
            <div><span class="label">HINT___</span> Cek kembali URL atau kembali ke dashboard.</div>
        </div>

        <a class="btn-home" href="{{ url('/') }}">
            <i class="fa-solid fa-arrow-left"></i>
            KEMBALI KE DASHBOARD
        </a>

        <div class="footer-note">
            Butuh bantuan? Hubungi tim NOC: <span class="contact">edp@inkalum.com</span>
        </div>
    </div>
</body>
</html>
