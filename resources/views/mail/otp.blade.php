<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP NOC INKASA</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #04080f;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #e0eaf8;
            padding: 30px 16px;
        }
        .wrapper {
            max-width: 520px;
            margin: 0 auto;
        }
        .card {
            background: linear-gradient(135deg, #0a0f1e, #0d1627);
            border: 1px solid rgba(0,243,255,0.2);
            border-radius: 14px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, rgba(0,243,255,0.1), rgba(255,0,85,0.08));
            border-bottom: 1px solid rgba(0,243,255,0.15);
            padding: 28px 32px;
            text-align: center;
        }
        .header .logo-text {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 6px;
            color: #fff;
            text-transform: uppercase;
        }
        .header .sub {
            font-size: 11px;
            letter-spacing: 3px;
            color: #00f3ff;
            margin-top: 4px;
            opacity: 0.8;
        }
        .body {
            padding: 36px 32px;
        }
        .greeting {
            font-size: 15px;
            color: #b0c4de;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .otp-label {
            font-size: 11px;
            letter-spacing: 3px;
            color: #708090;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .otp-box {
            display: block;
            background: rgba(0,0,0,0.4);
            border: 2px solid rgba(0,243,255,0.35);
            border-radius: 10px;
            text-align: center;
            padding: 20px;
            margin-bottom: 24px;
        }
        .otp-code {
            font-size: 44px;
            font-weight: 900;
            letter-spacing: 14px;
            color: #00f3ff;
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 18px rgba(0,243,255,0.5);
        }
        .expire-note {
            font-size: 12px;
            color: #ff6680;
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: 1px;
        }
        .expire-note strong { color: #ff0055; }
        .warning-box {
            background: rgba(255,0,85,0.06);
            border: 1px solid rgba(255,0,85,0.2);
            border-radius: 8px;
            padding: 14px 16px;
            font-size: 12px;
            color: #ff8099;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .warning-box strong { color: #ff0055; }
        .divider {
            border: none;
            border-top: 1px solid rgba(0,243,255,0.08);
            margin: 0 0 20px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            letter-spacing: 2px;
            color: #3a4a5a;
            padding: 0 32px 28px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="logo-text">NOC ROOM INKASA</div>
                <div class="sub">⚡ SECURE ACCESS VERIFICATION</div>
            </div>
            <div class="body">
                <p class="greeting">
                    Halo, <strong>{{ $userName }}</strong>.<br>
                    Berikut adalah kode OTP Anda untuk masuk ke <strong>NOC Command Center</strong>.
                    Jangan bagikan kode ini kepada siapa pun.
                </p>

                <p class="otp-label">Kode Verifikasi OTP</p>
                <div class="otp-box">
                    <span class="otp-code">{{ $otpCode }}</span>
                </div>

                <p class="expire-note">
                    ⏱ Kode berlaku selama <strong>5 menit</strong> sejak email ini dikirim.
                </p>

                <div class="warning-box">
                    <strong>⚠ PERINGATAN KEAMANAN:</strong><br>
                    Tim NOC Inkasa tidak pernah meminta kode OTP Anda melalui telepon, chat, atau media lain.
                    Jika Anda tidak merasa melakukan login, segera hubungi administrator sistem.
                </div>
            </div>
            <hr class="divider">
            <div class="footer">
                NOC INKASA · INTERNAL USE ONLY · {{ date('Y') }}<br>
                Email ini dikirim otomatis, jangan balas.
            </div>
        </div>
    </div>
</body>
</html>
