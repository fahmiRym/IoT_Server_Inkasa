<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center — 429 Too Many Requests</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="theme-color" content="#020408">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-deep:#020408; --panel:rgba(10,15,25,0.7); --border:rgba(255,204,0,0.20); --cyan:#00f3ff; --pink:#ff0055; --yellow:#ffcc00; --text-vibrant:#e6f1f8; --text-dim:#708090; }
        * { box-sizing:border-box; }
        html, body { margin:0; padding:0; min-height:100vh; }
        body { background:var(--bg-deep); color:var(--text-vibrant); font-family:'Rajdhani',sans-serif;
            background-image: radial-gradient(circle at 10% 10%, rgba(255,204,0,0.12) 0%, transparent 40%),
                              radial-gradient(circle at 90% 90%, rgba(0,243,255,0.08) 0%, transparent 40%),
                              url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
            display:flex; align-items:center; justify-content:center; padding:24px;
        }
        .maint-card { position:relative; z-index:2; width:100%; max-width:560px; background:var(--panel); backdrop-filter:blur(20px); border:1px solid var(--border); border-radius:16px; padding:36px 32px; text-align:center; }
        .icon-wrap { width:90px; height:90px; margin:0 auto 14px; border-radius:50%; background:radial-gradient(circle, rgba(255,204,0,0.18), transparent 70%); display:flex; align-items:center; justify-content:center; border:2px solid var(--yellow); box-shadow:0 0 30px rgba(255,204,0,0.35); }
        .icon-wrap i { font-size:38px; color:var(--yellow); filter:drop-shadow(0 0 8px var(--yellow)); }
        h1 { font-family:'Orbitron',sans-serif; font-size:22px; letter-spacing:3px; margin:6px 0; text-transform:uppercase; }
        .subtitle { color:var(--text-dim); font-size:13px; letter-spacing:1px; text-transform:uppercase; margin-bottom:20px; }
        .message-box { background:rgba(0,0,0,0.35); border-left:3px solid var(--yellow); border-radius:8px; padding:14px 18px; text-align:left; font-family:'JetBrains Mono',monospace; font-size:13px; margin-bottom:20px; line-height:1.7; }
        .label { color:var(--cyan); font-weight:700; }
        .btn-home { display:inline-flex; align-items:center; gap:8px; padding:12px 26px; border-radius:10px; font-family:'Orbitron',sans-serif; font-size:13px; font-weight:700; letter-spacing:2px; text-transform:uppercase; text-decoration:none; color:var(--bg-deep); background:linear-gradient(90deg,var(--cyan),#4dfff5); }
        .footer-note { color:var(--text-dim); font-size:12px; margin-top:14px; }
        .footer-note .contact { color:var(--cyan); font-weight:600; }
    </style>
</head>
<body>
    <div class="maint-card">
        <div class="icon-wrap"><i class="fa-solid fa-gauge-high"></i></div>
        <h1>THROTTLED</h1>
        <div class="subtitle">// 429 TOO_MANY_REQUESTS</div>
        <div class="message-box">
            <div><span class="label">STATUS_</span> Rate limit tercapai.</div>
            <div><span class="label">ACTION_</span> Tunggu beberapa saat sebelum mencoba lagi.</div>
        </div>
        <a class="btn-home" href="{{ url('/') }}"><i class="fa-solid fa-arrow-left"></i> DASHBOARD</a>
        <div class="footer-note">Butuh bantuan? <span class="contact">edp@inkalum.com</span></div>
    </div>
</body>
</html>
