<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center - {{ $mode === 'create' ? 'Tambah User' : 'Edit User' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #020408;
            --panel: rgba(10, 15, 25, 0.75);
            --border: rgba(0, 243, 255, 0.15);
            --cyan: #00f3ff;
            --pink: #ff0055;
            --green: #0aff00;
            --yellow: #ffcc00;
            --text-vibrant: #e6f1f8;
            --text-dim: #708090;
            --accent-glow: rgba(0, 243, 255, 0.3);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background-color: var(--bg-deep);
            color: var(--text-vibrant);
            font-family: 'Rajdhani', sans-serif;
            background-image:
                radial-gradient(circle at 5% 5%, rgba(0, 243, 255, 0.07) 0%, transparent 40%),
                radial-gradient(circle at 95% 95%, rgba(255, 0, 85, 0.07) 0%, transparent 40%),
                url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
            display: flex; flex-direction: column; align-items: center;
        }

        .grid-overlay {
            position: fixed; inset: 0;
            background-image: linear-gradient(var(--border) 1px, transparent 1px),
                              linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none; z-index: -1; opacity: 0.12;
            mask-image: radial-gradient(circle, black, transparent 80%);
        }

        /* TOPBAR */
        .topbar {
            width: 100%; display: flex; justify-content: space-between; align-items: center;
            padding: 16px 28px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(90deg, var(--panel), transparent);
            backdrop-filter: blur(8px);
        }

        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand img { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border); padding: 3px; background: rgba(255,0,85,0.1); filter: drop-shadow(0 0 4px var(--pink)); }
        .brand-name { font-family: 'Orbitron'; font-weight: 900; font-size: .85rem; letter-spacing: 3px; color: #fff; }
        .brand-sub { font-family: 'JetBrains Mono'; font-size: .55rem; color: var(--cyan); letter-spacing: 2px; opacity: .7; }

        .nav-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-family: 'JetBrains Mono'; font-size: .65rem; letter-spacing: 1px;
            color: var(--text-dim); text-decoration: none; padding: 6px 12px;
            border: 1px solid transparent; border-radius: 6px; transition: all .15s;
        }
        .nav-link:hover { color: var(--cyan); border-color: var(--border); background: rgba(0,243,255,.05); }

        /* FORM CARD */
        .form-wrap {
            width: 100%; max-width: 640px;
            padding: 32px 20px;
        }

        .page-title { font-family: 'Orbitron'; font-weight: 700; font-size: 1rem; letter-spacing: 3px; color: var(--cyan); margin-bottom: 4px; }
        .page-sub { font-family: 'JetBrains Mono'; font-size: .65rem; color: var(--text-dim); letter-spacing: 1px; margin-bottom: 24px; }

        .form-card {
            background: var(--panel); border: 1px solid var(--border);
            border-radius: 14px; padding: 28px 28px;
            backdrop-filter: blur(12px);
            position: relative;
        }
        .form-card::before {
            content: '';
            position: absolute; inset: 0;
            border-radius: 14px; padding: 1px;
            background: linear-gradient(135deg, var(--cyan), transparent 60%, var(--pink));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude;
            pointer-events: none; opacity: 0.25;
        }

        .section-label {
            font-family: 'JetBrains Mono'; font-size: .6rem; letter-spacing: 2px;
            color: var(--cyan); text-transform: uppercase; margin-bottom: 16px;
            padding-bottom: 8px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 8px;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .form-row.single { grid-template-columns: 1fr; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }

        label {
            font-family: 'JetBrains Mono'; font-size: .6rem; letter-spacing: 2px;
            color: var(--text-dim); text-transform: uppercase;
        }

        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: var(--cyan); font-size: .8rem; opacity: .6; }

        input[type=text], input[type=email], input[type=password], select {
            width: 100%; padding: 10px 12px 10px 36px;
            background: rgba(0,0,0,0.35); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text-vibrant);
            font-family: 'Rajdhani'; font-size: .95rem; outline: none;
            transition: all .15s;
        }
        select { padding-left: 36px; }
        input:focus, select:focus { border-color: var(--cyan); box-shadow: 0 0 10px rgba(0,243,255,.2); }

        .field-error {
            font-family: 'JetBrains Mono'; font-size: .6rem; color: var(--pink);
            letter-spacing: .5px; margin-top: 3px;
        }

        .hint-text {
            font-family: 'JetBrains Mono'; font-size: .58rem; color: var(--text-dim);
            letter-spacing: .5px; margin-top: 3px;
        }

        .divider { border: none; border-top: 1px solid var(--border); margin: 22px 0; }

        .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }

        .btn-save {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 24px;
            background: linear-gradient(135deg, rgba(0,243,255,.15), rgba(0,243,255,.04));
            border: 1px solid var(--cyan); color: var(--cyan);
            font-family: 'Orbitron'; font-weight: 700; font-size: .75rem; letter-spacing: 2px;
            border-radius: 8px; cursor: pointer; transition: all .15s;
        }
        .btn-save:hover { background: rgba(0,243,255,.22); box-shadow: 0 0 16px rgba(0,243,255,.35); }

        .btn-cancel {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 20px;
            background: transparent; border: 1px solid var(--border); color: var(--text-dim);
            font-family: 'JetBrains Mono'; font-size: .7rem; letter-spacing: 1px;
            border-radius: 8px; cursor: pointer; text-decoration: none; transition: all .15s;
        }
        .btn-cancel:hover { border-color: var(--pink); color: var(--pink); }

        .role-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .role-option { position: relative; }
        .role-option input[type=radio] { display: none; }
        .role-option label {
            display: flex; flex-direction: column; gap: 4px;
            padding: 12px 16px; border: 1px solid var(--border);
            border-radius: 8px; cursor: pointer; transition: all .15s;
            font-size: .75rem; letter-spacing: .5px;
        }
        .role-option label .role-name { font-family: 'Orbitron'; font-size: .75rem; color: var(--text-dim); }
        .role-option label .role-desc { font-family: 'JetBrains Mono'; font-size: .58rem; color: var(--text-dim); font-weight: 400; }
        .role-option input[type=radio]:checked + label { border-color: var(--cyan); background: rgba(0,243,255,.07); }
        .role-option input[type=radio]:checked + label .role-name { color: var(--cyan); }
        .role-option.superadmin input[type=radio]:checked + label { border-color: var(--pink); background: rgba(255,0,85,.07); }
        .role-option.superadmin input[type=radio]:checked + label .role-name { color: var(--pink); }

        @media (max-width: 520px) {
            .form-row { grid-template-columns: 1fr; }
            .role-selector { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <div class="grid-overlay"></div>

    <nav class="topbar">
        <a href="/" class="brand">
            <img src="{{ asset('favicon.png') }}" alt="Logo">
            <div>
                <div class="brand-name">NOC INKASA</div>
                <div class="brand-sub">COMMAND CENTER</div>
            </div>
        </a>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('users.index') }}" class="nav-link" style="color:var(--cyan); background:rgba(0,243,255,.08); border-color:rgba(0,243,255,.3);"><i class="fas fa-arrow-left"></i> Kembali</a>
            <a href="/" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="{{ route('users.index') }}" class="nav-link"><i class="fas fa-users-cog"></i> Users</a>
            <a href="{{ route('devices.index') }}" class="nav-link"><i class="fas fa-mobile-alt"></i> Devices</a>
            <a href="{{ route('profile.edit') }}" class="nav-link"><i class="fas fa-user-circle"></i> Profil</a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="nav-link" style="background:none; border:1px solid rgba(255,0,85,.3); color:var(--pink); cursor:pointer; font: inherit; letter-spacing:1px;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </nav>

    <div style="display:flex; align-items:center; gap:8px; font-family:'JetBrains Mono'; font-size:.62rem; color:var(--text-dim); letter-spacing:1px; padding:12px clamp(14px,2vw,28px) 0;">
        <a href="/" style="color:var(--cyan); text-decoration:none;"><i class="fas fa-home"></i></a>
        <span style="color:var(--border);">›</span>
        <a href="/" style="color:var(--cyan); text-decoration:none;">Dashboard</a>
        <span style="color:var(--border);">›</span>
        <a href="{{ route('users.index') }}" style="color:var(--cyan); text-decoration:none;">Manajemen User</a>
        <span style="color:var(--border);">›</span>
        <span>{{ $mode === 'create' ? 'Tambah User' : 'Edit User' }}</span>
    </div>

    <div class="form-wrap">
        <div class="page-title">
            <i class="fas fa-{{ $mode === 'create' ? 'user-plus' : 'user-edit' }}" style="margin-right:10px;font-size:.9rem"></i>
            {{ strtoupper($mode === 'create' ? 'Tambah User Baru' : 'Edit User') }}
        </div>
        <div class="page-sub">
            {{ $mode === 'create' ? 'BUAT AKUN PENGGUNA BARU · SUPERADMIN PANEL' : 'EDIT AKUN: ' . strtoupper($user->username) . ' · SUPERADMIN PANEL' }}
        </div>

        @if($errors->any())
            <div style="background:rgba(255,0,85,.07);border:1px solid rgba(255,0,85,.25);border-radius:8px;padding:12px 16px;margin-bottom:18px;font-family:'JetBrains Mono';font-size:.7rem;color:var(--pink);">
                <i class="fas fa-exclamation-triangle" style="margin-right:6px"></i>
                @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
            </div>
        @endif

        <div class="form-card">
            <form method="POST"
                action="{{ $mode === 'create' ? route('users.store') : route('users.update', $user) }}">
                @csrf
                @if($mode === 'edit') @method('PATCH') @endif

                {{-- IDENTITAS --}}
                <div class="section-label"><i class="fas fa-id-card"></i> IDENTITAS</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <div class="input-wrap">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name"
                                value="{{ old('name', $user?->name) }}" placeholder="Nama lengkap" required>
                        </div>
                        @error('name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrap">
                            <i class="fas fa-at"></i>
                            <input type="text" id="username" name="username"
                                value="{{ old('username', $user?->username) }}" placeholder="username_unik" required>
                        </div>
                        @error('username') <span class="field-error">{{ $message }}</span> @enderror
                        <span class="hint-text">Huruf kecil, angka, titik, underscore</span>
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label for="email">Email <span style="color:var(--text-dim);font-weight:400">(Opsional)</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email"
                                value="{{ old('email', $user?->email) }}" placeholder="email@domain.com">
                        </div>
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                        @if($mode === 'edit' && !$user->email)
                            <span class="hint-text" style="color:var(--pink)">⚠ Superadmin tanpa email tidak bisa menerima OTP</span>
                        @endif
                    </div>
                </div>

                <hr class="divider">

                {{-- ROLE --}}
                <div class="section-label"><i class="fas fa-shield-alt"></i> ROLE & AKSES</div>

                <div class="role-selector" style="margin-bottom:14px">
                    <div class="role-option superadmin">
                        <input type="radio" id="role_sa" name="role" value="superadmin"
                            {{ old('role', $user?->role ?? 'admin') === 'superadmin' ? 'checked' : '' }}>
                        <label for="role_sa">
                            <span class="role-name"><i class="fas fa-crown" style="margin-right:6px;font-size:.7rem"></i>SUPERADMIN</span>
                            <span class="role-desc">Akses penuh: user management, DB backup/restore, dan monitoring</span>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="role_admin" name="role" value="admin"
                            {{ old('role', $user?->role ?? 'admin') === 'admin' ? 'checked' : '' }}>
                        <label for="role_admin">
                            <span class="role-name"><i class="fas fa-user-shield" style="margin-right:6px;font-size:.7rem"></i>ADMIN</span>
                            <span class="role-desc">Akses dashboard, monitoring, dan export laporan</span>
                        </label>
                    </div>
                </div>
                @error('role') <span class="field-error">{{ $message }}</span> @enderror

                <hr class="divider">

                {{-- PASSWORD --}}
                <div class="section-label"><i class="fas fa-lock"></i>
                    {{ $mode === 'create' ? 'PASSWORD' : 'RESET PASSWORD (OPSIONAL)' }}
                </div>

                @if($mode === 'edit')
                    <p style="font-family:'JetBrains Mono';font-size:.65rem;color:var(--text-dim);margin-bottom:14px;letter-spacing:.5px">
                        Biarkan kosong jika tidak ingin mengganti password.
                    </p>
                @endif

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">{{ $mode === 'create' ? 'Password' : 'Password Baru' }}</label>
                        <div class="input-wrap">
                            <i class="fas fa-key"></i>
                            <input type="password" id="new_password" name="{{ $mode === 'create' ? 'password' : 'new_password' }}"
                                placeholder="••••••••"
                                {{ $mode === 'create' ? 'required' : '' }}>
                        </div>
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                        @error('new_password') <span class="field-error">{{ $message }}</span> @enderror
                        <span class="hint-text">Minimal 6 karakter</span>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirmation">Konfirmasi Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-key"></i>
                            <input type="password" id="new_password_confirmation"
                                name="{{ $mode === 'create' ? 'password_confirmation' : 'new_password_confirmation' }}"
                                placeholder="••••••••"
                                {{ $mode === 'create' ? 'required' : '' }}>
                        </div>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="form-actions">
                    <a href="{{ route('users.index') }}" class="btn-cancel">
                        <i class="fas fa-times"></i> BATAL
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-{{ $mode === 'create' ? 'user-plus' : 'save' }}"></i>
                        {{ $mode === 'create' ? 'BUAT USER' : 'SIMPAN' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
