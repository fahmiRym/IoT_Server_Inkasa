<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center — 419 Session Expired</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="theme-color" content="#020408">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-deep:#020408; --panel:rgba(10,15,25,0.7); --border:rgba(0,243,255,0.15); --cyan:#00f3ff; --pink:#ff0055; --yellow:#ffcc00; --text-vibrant:#e6f1f8; --text-dim:#708090; }
        * { box-sizing: border-box; }
        html, body { margin:0; padding:0; min-height:100vh; }
        body { background:var(--bg-deep); color:var(--text-vibrant); font-family:'Rajdhani',sans-serif;
            background-image: radial-gradient(circle at 10% 10%, rgba(0,243,255,0.12) 0%, transparent 40%),
                              radial-gradient(circle at 90% 90%, rgba(255,204,0,0.08) 0%, transparent 40%),
                              url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
            display:flex; align-items:center; justify-content:center; padding:24px; overflow-x:hidden;
        }
        .grid-overlay { position:fixed; inset:0; background-image: linear-gradient(var(--border) 1px, transparent 1px), linear-gradient(90deg, var(--border) 1px, transparent 1px); background-size:50px 50px; pointer-events:none; z-index:0; opacity:0.2; mask-image: radial-gradient(circle, black, transparent 80%); }
        .maint-card { position:relative; z-index:2; width:100%; max-width:560px; background:var(--panel); backdrop-filter:blur(20px); border:1px solid var(--border); border-radius:16px; padding:40px 36px; text-align:center; box-shadow:0 0 40px rgba(0,243,255,0.08); }
        .brand-line { display:flex; align-items:center; justify-content:center; gap:10px; font-family:'Orbitron',sans-serif; font-size:13px; letter-spacing:3px; color:var(--cyan); margin-bottom:22px; text-transform:uppercase; }
        .brand-line .dot { width:8px; height:8px; border-radius:50%; background:var(--yellow); box-shadow:0 0 10px var(--yellow); animation:pulse 1.2s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{opacity:1; transform:scale(1)} 50%{opacity:.5; transform:scale(.85)} }
        .icon-wrap { width:100px; height:100px; margin:0 auto 14px; border-radius:50%; background:radial-gradient(circle, rgba(0,243,255,0.15), transparent 70%); display:flex; align-items:center; justify-content:center; border:2px solid var(--cyan); box-shadow:0 0 30px rgba(0,243,255,0.35); }
        .icon-wrap i { font-size:42px; color:var(--cyan); filter:drop-shadow(0 0 8px var(--cyan)); }
        h1 { font-family:'Orbitron',sans-serif; font-size:22px; font-weight:700; letter-spacing:3px; margin:6px 0; color:var(--text-vibrant); text-transform:uppercase; }
        .subtitle { color:var(--text-dim); font-size:13px; letter-spacing:1px; text-transform:uppercase; margin-bottom:22px; }
        .message-box { background:rgba(0,0,0,0.35); border:1px solid var(--border); border-left:3px solid var(--cyan); border-radius:8px; padding:14px 18px; text-align:left; font-family:'JetBrains Mono',monospace; font-size:13px; margin-bottom:22px; line-height:1.7; }
        .message-box .label { color:var(--cyan); font-weight:700; }
        .btn-home { display:inline-flex; align-items:center; gap:8px; padding:12px 26px; border-radius:10px; font-family:'Orbitron',sans-serif; font-size:13px; font-weight:700; letter-spacing:2px; text-transform:uppercase; text-decoration:none; color:var(--bg-deep); background:linear-gradient(90deg,var(--cyan),#4dfff5); box-shadow:0 0 20px rgba(0,243,255,0.4); }
        .footer-note { color:var(--text-dim); font-size:12px; letter-spacing:0.5px; margin-top:18px; }
        .footer-note .contact { color:var(--cyan); font-weight:600; }
    </style>
</head>
<body>
    <div class="grid-overlay"></div>
    <div class="maint-card">
        <div class="brand-line">
            <span class="dot"></span>
            <span>NOC Command Center · Inkasa</span>
            <span class="dot"></span>
        </div>
        <div class="icon-wrap"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <h1>SESSION EXPIRED</h1>
        <div class="subtitle">// PAGE_EXPIRED</div>
        <div class="message-box">
            <div><span class="label">STATUS_</span> 419 — SESSION_TIMEOUT</div>
            <div><span class="label">ACTION_</span> Silakan refresh & login kembali.</div>
        </div>
        <a class="btn-home" href="{{ route('login') }}">
            <i class="fa-solid fa-right-to-bracket"></i> LOGIN LAGI
        </a>
        <div class="footer-note">Butuh bantuan? <span class="contact">edp@inkalum.com</span></div>
    </div>
</body>
</html>
