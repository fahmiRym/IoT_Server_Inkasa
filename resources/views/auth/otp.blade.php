@extends('layouts.app')

@section('title', 'NOC Command Center - Verifikasi OTP')

@push('styles')
<style>
    body {
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .topbar { display: none; }

    /* Override main dari layout: buat flex-center biar otp-card ada di tengah layar */
    main {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 100vh;
    }

    .otp-card {
        width: 100%;
        max-width: 440px;
        margin: 0 auto;
        background: var(--panel);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 32px 30px 28px;
        position: relative;
        box-shadow: 0 0 40px rgba(0, 243, 255, 0.08);
    }

    .otp-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 14px;
        padding: 1px;
        background: linear-gradient(135deg, var(--cyan), transparent 60%, var(--pink));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
                mask-composite: exclude;
        pointer-events: none;
        opacity: 0.4;
    }

    .brand-line {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
    }

    .brand-line img {
        width: 42px;
        height: 42px;
        border: 1px solid var(--border);
        border-radius: 50%;
        padding: 4px;
        background: rgba(255, 0, 85, 0.1);
        filter: drop-shadow(0 0 5px var(--pink));
        animation: pulse-img 3s infinite alternate;
    }
    
    @keyframes pulse-img {
        0% { filter: drop-shadow(0 0 5px var(--pink)); }
        100% { filter: drop-shadow(0 0 15px var(--cyan)); }
    }

    .brand-line h1 {
        font-family: 'Orbitron';
        font-weight: 900;
        font-size: 1.1rem;
        letter-spacing: 3px;
        margin: 0;
        color: #fff;
        text-shadow: 0 0 18px var(--accent-glow);
    }

    .brand-line small {
        font-family: 'JetBrains Mono';
        font-size: 0.6rem;
        color: var(--cyan);
        letter-spacing: 2px;
        opacity: 0.7;
    }

    .section-title {
        font-family: 'Orbitron';
        font-weight: 700;
        letter-spacing: 2px;
        font-size: 0.9rem;
        margin: 0 0 6px;
        color: var(--cyan);
        text-align: center;
    }

    .email-hint {
        font-family: 'JetBrains Mono';
        font-size: 0.7rem;
        color: var(--text-dim);
        letter-spacing: 1px;
        margin-bottom: 22px;
        text-align: center;
    }

    .email-hint span { color: var(--cyan); font-weight: bold; }

    /* OTP 6 kotak */
    .otp-inputs {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-bottom: 18px;
    }

    /* penting: override padding global input[type=text] dari noc-theme.css
       (defaultnya padding-left:42px + padding-right:14px, bikin angka kepental keluar box) */
    .otp-inputs input.otp-digit {
        width: 52px !important;
        height: 62px;
        padding: 0 !important;
        background: rgba(0, 0, 0, 0.4);
        border: 2px solid var(--border);
        border-radius: 10px;
        color: var(--cyan);
        font-family: 'JetBrains Mono', monospace;
        font-size: 1.6rem;
        font-weight: 700;
        text-align: center;
        outline: none;
        transition: all 0.2s;
        caret-color: var(--cyan);
    }

    .otp-inputs input.otp-digit:focus {
        border-color: var(--cyan);
        box-shadow: 0 0 16px rgba(0, 243, 255, 0.3);
        background: rgba(0, 243, 255, 0.05);
        transform: translateY(-2px);
    }

    .otp-inputs input.otp-digit.filled {
        border-color: rgba(0, 243, 255, 0.5);
        color: #fff;
    }

    /* Timer */
    .timer-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .timer-label {
        font-family: 'JetBrains Mono';
        font-size: 0.65rem;
        color: var(--text-dim);
        letter-spacing: 1px;
    }

    #countdown {
        font-family: 'JetBrains Mono';
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--green);
        letter-spacing: 2px;
    }

    #countdown.expired { color: var(--pink); text-shadow: 0 0 8px var(--pink); }

    .btn-back {
        display: block;
        text-align: center;
        font-family: 'JetBrains Mono';
        font-size: 0.6rem;
        color: var(--text-dim);
        letter-spacing: 1px;
        text-decoration: none;
        margin-top: 16px;
        transition: color 0.15s;
    }

    .btn-back:hover { color: var(--pink); }

    .shield-icon {
        display: flex;
        justify-content: center;
        margin-bottom: 18px;
    }

    .shield-icon i {
        font-size: 2.5rem;
        color: var(--cyan);
        filter: drop-shadow(0 0 10px rgba(0, 243, 255, 0.5));
        animation: shield-pulse 2s ease-in-out infinite;
    }

    @keyframes shield-pulse {
        0%, 100% { filter: drop-shadow(0 0 8px rgba(0, 243, 255, 0.4)); transform: scale(1); }
        50% { filter: drop-shadow(0 0 20px rgba(0, 243, 255, 0.8)); transform: scale(1.05); }
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 1; }
    }

    .live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--green);
        box-shadow: 0 0 6px var(--green);
        display: inline-block;
        margin-right: 4px;
        animation: pulse-dot 1.4s infinite;
    }

    .footer-meta {
        margin-top: 18px;
        text-align: center;
        font-family: 'JetBrains Mono';
        font-size: 0.6rem;
        color: var(--text-dim);
        letter-spacing: 2px;
        opacity: 0.7;
    }
</style>
@endpush

@section('content')
<div class="otp-card">
    <div class="brand-line">
        <img src="{{ asset('favicon.png') }}" alt="Logo">
        <div>
            <h1>NOC COMMAND INKASA</h1>
            <small><span class="live-dot"></span>VERIFIKASI 2-FAKTOR · OTP</small>
        </div>
    </div>

    <div class="shield-icon">
        <i class="fas fa-shield-alt"></i>
    </div>

    <h2 class="section-title">KODE VERIFIKASI</h2>
    <p class="email-hint">
        Kode OTP 6-digit telah dikirim ke<br>
        <span>{{ $maskedEmail }}</span>
    </p>

    {{-- Form OTP --}}
    <form method="POST" action="{{ route('otp.verify') }}" id="otpForm" autocomplete="off">
        @csrf
        <input type="hidden" name="otp" id="otpHidden">

        <div class="otp-inputs" id="otpBoxes">
            <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" id="d0" autofocus>
            <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" id="d1">
            <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" id="d2">
            <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" id="d3">
            <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" id="d4">
            <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" id="d5">
        </div>

        <div class="timer-bar">
            <span class="timer-label">⏱ SISA WAKTU</span>
            <span id="countdown">--:--</span>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-2" id="btnVerify">
            <i class="fas fa-check-circle"></i> VERIFIKASI
        </button>
    </form>

    {{-- Paste OTP from clipboard (mobile helper) --}}
    <button type="button" id="btnPaste" class="btn btn-outline w-100 mb-2"
        style="border-color: rgba(0,243,255,.3); color: var(--cyan);">
        <i class="fas fa-paste"></i> TEMPEL OTP DARI CLIPBOARD
    </button>

    {{-- Resend --}}
    <form method="POST" action="{{ route('otp.resend') }}" id="resendForm">
        @csrf
        <button type="submit" id="btnResend" class="btn btn-outline w-100" style="border-color: rgba(255, 204, 0, 0.3); color: var(--yellow);">
            <i class="fas fa-redo"></i> <span id="resendLabel">KIRIM ULANG OTP</span>
        </button>
    </form>

    <a href="/login" class="btn-back">← Kembali ke Login</a>

    <div class="footer-meta">
        NOC INKASA · INTERNAL USE ONLY
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── OTP Input: auto-advance & backspace ──
    const digits = document.querySelectorAll('.otp-digit');
    const otpHidden = document.getElementById('otpHidden');
    const btnVerify = document.getElementById('btnVerify');

    digits.forEach((input, i) => {
        input.addEventListener('input', (e) => {
            const val = e.target.value.replace(/\D/g, '');
            e.target.value = val;
            if (val.length === 1 && i < digits.length - 1) {
                digits[i + 1].focus();
            }
            syncOtp();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && i > 0) {
                digits[i - 1].focus();
                digits[i - 1].value = '';
                syncOtp();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            paste.split('').forEach((ch, idx) => {
                if (digits[idx]) digits[idx].value = ch;
            });
            if (digits[paste.length - 1]) digits[paste.length - 1].focus();
            syncOtp();
        });
    });

    function syncOtp() {
        const code = Array.from(digits).map(d => d.value).join('');
        otpHidden.value = code;
        digits.forEach(d => {
            d.classList.toggle('filled', d.value !== '');
        });
    }

    // ── Countdown Timer ──
    let seconds = {{ $secondsLeft }};
    const countdown = document.getElementById('countdown');

    function formatTime(s) {
        const m = Math.floor(s / 60).toString().padStart(2, '0');
        const sec = (s % 60).toString().padStart(2, '0');
        return `${m}:${sec}`;
    }

    function tick() {
        countdown.textContent = formatTime(seconds);
        if (seconds <= 0) {
            countdown.textContent = 'EXPIRED';
            countdown.classList.add('expired');
            btnVerify.disabled = true;
            btnVerify.style.opacity = '0.4';
            btnVerify.style.cursor = 'not-allowed';
            clearInterval(timer);
        } else {
            seconds--;
        }
    }

    tick();
    const timer = setInterval(tick, 1000);

    // ── PASTE FROM CLIPBOARD ──
    document.getElementById('btnPaste').addEventListener('click', async () => {
        try {
            const text = await navigator.clipboard.readText();
            const code = (text || '').replace(/\D/g, '').slice(0, 6);
            if (!code) {
                if (window.NocCyber) NocCyber.warning('Clipboard kosong atau tidak ada angka.');
                return;
            }
            code.split('').forEach((ch, idx) => {
                if (digits[idx]) digits[idx].value = ch;
            });
            syncOtp();
            if (window.NocCyber) NocCyber.success(`Kode ${code} berhasil ditempel.`, { duration: 2000 });
            // Auto submit kalau 6 digit lengkap
            if (code.length === 6) setTimeout(() => document.getElementById('otpForm').submit(), 300);
        } catch (e) {
            if (window.NocCyber) NocCyber.error('Browser menolak akses clipboard. Coba tempel manual di kotak.');
        }
    });

    // ── RESEND COOLDOWN (30 detik) ──
    const btnResend = document.getElementById('btnResend');
    const lblResend = document.getElementById('resendLabel');
    const RESEND_KEY = 'noc-otp-resend-until';
    function updateResend() {
        const until = parseInt(localStorage.getItem(RESEND_KEY) || '0', 10);
        const now = Date.now();
        if (until > now) {
            const s = Math.ceil((until - now) / 1000);
            btnResend.disabled = true;
            btnResend.style.opacity = '.5';
            btnResend.style.cursor = 'not-allowed';
            lblResend.textContent = `TUNGGU ${s}s`;
            setTimeout(updateResend, 500);
        } else {
            btnResend.disabled = false;
            btnResend.style.opacity = '1';
            btnResend.style.cursor = 'pointer';
            lblResend.textContent = 'KIRIM ULANG OTP';
        }
    }
    document.getElementById('resendForm').addEventListener('submit', () => {
        localStorage.setItem(RESEND_KEY, (Date.now() + 30_000).toString());
    });
    updateResend();

    // ── AUTO-SUBMIT saat 6 digit terisi ──
    digits[digits.length - 1].addEventListener('input', (e) => {
        setTimeout(() => {
            const code = Array.from(digits).map(d => d.value).join('');
            if (code.length === 6 && /^\d{6}$/.test(code)) {
                document.getElementById('otpForm').submit();
            }
        }, 60);
    });

    // ── WebOTP API (Android Chrome: auto-fill dari SMS) ──
    if ('OTPCredential' in window) {
        const ac = new AbortController();
        setTimeout(() => ac.abort(), 3 * 60 * 1000);
        navigator.credentials.get({
            otp: { transport: ['sms'] },
            signal: ac.signal,
        }).then(otp => {
            if (otp && otp.code) {
                otp.code.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
                syncOtp();
                document.getElementById('otpForm').submit();
            }
        }).catch(() => {});
    }
</script>
@endpush
