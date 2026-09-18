<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center — 403 Forbidden</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="theme-color" content="#020408">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-deep:#020408; --panel:rgba(10,15,25,0.7); --border:rgba(255,204,0,0.20); --cyan:#00f3ff; --pink:#ff0055; --yellow:#ffcc00; --text-vibrant:#e6f1f8; --text-dim:#708090; }
        * { box-sizing: border-box; }
        html, body { margin:0; padding:0; min-height:100vh; }
        body { background:var(--bg-deep); color:var(--text-vibrant); font-family:'Rajdhani',sans-serif;
            background-image: radial-gradient(circle at 10% 10%, rgba(255,204,0,0.10) 0%, transparent 40%),
                              radial-gradient(circle at 90% 90%, rgba(255,0,85,0.10) 0%, transparent 40%),
                              url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
            display:flex; align-items:center; justify-content:center; padding:24px; overflow-x:hidden;
        }
        .grid-overlay { position:fixed; inset:0; background-image: linear-gradient(var(--border) 1px, transparent 1px), linear-gradient(90deg, var(--border) 1px, transparent 1px); background-size:50px 50px; pointer-events:none; z-index:0; opacity:0.2; mask-image: radial-gradient(circle, black, transparent 80%); }
        .scanline { position:fixed; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,transparent,var(--yellow),transparent); box-shadow:0 0 12px var(--yellow); animation:scan 4s linear infinite; z-index:1; pointer-events:none; }
        @keyframes scan { 0%{transform:translateY(0)} 100%{transform:translateY(100vh)} }
        .maint-card { position:relative; z-index:2; width:100%; max-width:620px; background:var(--panel); backdrop-filter:blur(20px); border:1px solid var(--border); border-radius:16px; padding:40px 36px; text-align:center; box-shadow:0 0 40px rgba(255,204,0,0.12), inset 0 0 20px rgba(255,204,0,0.03); }
        .maint-card::before { content:''; position:absolute; inset:0; border-radius:16px; padding:1px; background:linear-gradient(135deg,var(--yellow),transparent 60%,var(--pink)); -webkit-mask:linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite:xor; mask-composite:exclude; pointer-events:none; opacity:0.5; }
        .brand-line { display:flex; align-items:center; justify-content:center; gap:10px; font-family:'Orbitron',sans-serif; font-size:13px; letter-spacing:3px; color:var(--yellow); margin-bottom:22px; text-transform:uppercase; }
        .brand-line .dot { width:8px; height:8px; border-radius:50%; background:var(--yellow); box-shadow:0 0 10px var(--yellow); animation:pulse 1.2s ease-in-out infinite; }
        @keyframes pulse { 0%,100%{opacity:1; transform:scale(1)} 50%{opacity:.5; transform:scale(.85)} }
        .icon-wrap { width:110px; height:110px; margin:0 auto 14px; border-radius:50%; background:radial-gradient(circle, rgba(255,204,0,0.15), transparent 70%); display:flex; align-items:center; justify-content:center; border:2px solid var(--yellow); box-shadow:0 0 30px rgba(255,204,0,0.35), inset 0 0 20px rgba(255,204,0,0.1); }
        .icon-wrap i { font-size:46px; color:var(--yellow); filter:drop-shadow(0 0 8px var(--yellow)); }
        .code-huge { font-family:'Orbitron',sans-serif; font-size:68px; font-weight:900; letter-spacing:6px; background:linear-gradient(90deg,var(--yellow),var(--pink)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; margin:4px 0 2px; }
        h1 { font-family:'Orbitron',sans-serif; font-size:22px; font-weight:700; letter-spacing:3px; margin:0 0 6px; color:var(--text-vibrant); text-transform:uppercase; }
        .subtitle { color:var(--text-dim); font-size:13px; letter-spacing:1px; text-transform:uppercase; margin-bottom:22px; }
        .message-box { background:rgba(0,0,0,0.4); border:1px solid var(--border); border-left:3px solid var(--yellow); border-radius:8px; padding:16px 20px; text-align:left; font-family:'JetBrains Mono',monospace; font-size:13px; color:var(--text-vibrant); margin-bottom:22px; line-height:1.7; }
        .message-box .label { color:var(--cyan); font-weight:700; }
        .message-box .val { color:var(--yellow); }
        .btn-home { display:inline-flex; align-items:center; gap:8px; padding:12px 26px; border-radius:10px; font-family:'Orbitron',sans-serif; font-size:13px; font-weight:700; letter-spacing:2px; text-transform:uppercase; text-decoration:none; color:var(--bg-deep); background:linear-gradient(90deg,var(--cyan),#4dfff5); box-shadow:0 0 20px rgba(0,243,255,0.4); }
        .btn-home:hover { transform:translateY(-2px); box-shadow:0 0 30px rgba(0,243,255,0.6); }
        .footer-note { color:var(--text-dim); font-size:12px; letter-spacing:0.5px; margin-top:20px; }
        .footer-note .contact { color:var(--cyan); font-weight:600; }
        @media (max-width:480px) { .maint-card { padding:30px 22px; } .code-huge { font-size:52px; } .icon-wrap { width:90px; height:90px; } .icon-wrap i { font-size:38px; } }
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
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <div class="code-huge">403</div>
        <h1>ACCESS DENIED</h1>
        <div class="subtitle">// FORBIDDEN_RESOURCE</div>

        <div class="message-box">
            <div><span class="label">STATUS_</span> <span class="val">403 — FORBIDDEN</span></div>
            <div><span class="label">USER___</span> <span class="val">{{ auth()->user()->username ?? 'GUEST' }}</span></div>
            <div><span class="label">REASON_</span> Role Anda tidak diizinkan mengakses resource ini.</div>
            <div><span class="label">HINT___</span> Hubungi <em>superadmin</em> jika butuh akses tambahan.</div>
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
