@extends('layouts.app')

@section('title', 'NOC Command Center - Sign In')

@push('styles')
<style>
    body {
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .topbar { display: none; } /* Hide topbar on login */

    /* Override main dari layout: buat flex-center biar login-card ada di tengah layar */
    main {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 100vh;
    }

    .login-card {
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
        background: var(--panel);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 30px 28px 26px;
        position: relative;
        box-shadow: 0 0 40px rgba(0, 243, 255, 0.08);
        transition: transform 0.3s;
    }
    .login-card:hover { transform: translateY(-5px); }
    .login-card::before {
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
        margin-bottom: 25px;
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
        font-size: 1.15rem;
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
    h2 {
        font-family: 'Orbitron';
        font-weight: 700;
        letter-spacing: 2px;
        font-size: 0.95rem;
        margin: 4px 0 18px;
        color: var(--cyan);
    }
    
    .row-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.7rem;
        color: var(--text-dim);
        margin-bottom: 20px;
    }
    .row-flex label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        font-family: 'JetBrains Mono';
        letter-spacing: 1px;
    }
    .row-flex .hint { font-family: 'JetBrains Mono'; }
    
    .footer-meta {
        margin-top: 18px;
        text-align: center;
        font-family: 'JetBrains Mono';
        font-size: 0.6rem;
        color: var(--text-dim);
        letter-spacing: 2px;
        opacity: 0.7;
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
</style>
@endpush

@section('content')
<div class="login-card">
    <div class="brand-line">
        <img src="{{ asset('favicon.png') }}" alt="Logo">
        <div>
            <h1>NOC COMMAND INKASA</h1>
            <small><span class="live-dot"></span>SECURE ACCESS &middot; v2.2</small>
        </div>
    </div>

    <h2>SIGN IN</h2>

    <form method="POST" action="{{ route('login') }}" autocomplete="off" id="loginForm">
        @csrf
        <div class="form-group">
            <label for="username">Username / Email</label>
            <div class="input-wrap">
                <i class="fas fa-user"></i>
                <input id="username" name="username" type="text" required autofocus
                       value="{{ old('username') }}" placeholder="admin_noc">
            </div>
            @error('username') <span class="field-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input id="password" name="password" type="password" required placeholder="••••••••••">
            </div>
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="row-flex">
            <label><input type="checkbox" name="remember" value="1"> REMEMBER ME</label>
            <span class="hint">AUTH: SESSION</span>
        </div>

        <button class="btn btn-primary w-100" type="submit" id="btn-submit">
            <i class="fas fa-bolt"></i> AUTHORIZE
        </button>
    </form>

    <div class="footer-meta">
        NOC INKASA &middot; INTERNAL USE ONLY
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        let btn = document.getElementById('btn-submit');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> AUTHORIZING...';
        btn.style.opacity = '0.7';
        btn.style.pointerEvents = 'none';
    });
</script>
@endpush
