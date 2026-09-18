<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Command Center - Edit Profile</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="theme-color" content="#020408">
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap"
        rel="stylesheet">
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
            --accent-glow: rgba(0, 243, 255, 0.3);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background-color: var(--bg-deep);
            color: var(--text-vibrant);
            font-family: 'Rajdhani', sans-serif;
            background-image:
                radial-gradient(circle at 5% 5%, rgba(0, 243, 255, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 95% 95%, rgba(255, 0, 85, 0.1) 0%, transparent 40%),
                url("https://www.transparenttextures.com/patterns/carbon-fibre.png");
        }
        .grid-overlay {
            position: fixed;
            inset: 0;
            background-image: linear-gradient(var(--border) 1px, transparent 1px),
                              linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: -1;
            opacity: 0.15;
            mask-image: radial-gradient(circle, black, transparent 80%);
        }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 30px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(90deg, var(--panel), transparent);
            backdrop-filter: blur(8px);
        }
        .top .brand {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .top h1 {
            font-family: 'Orbitron';
            font-weight: 900;
            letter-spacing: 3px;
            font-size: 1rem;
            margin: 0;
            color: #fff;
            text-shadow: 0 0 18px var(--accent-glow);
        }
        .top img {
            width: 36px; height: 36px;
            border: 1px solid var(--border);
            border-radius: 50%;
            padding: 4px;
            background: rgba(255, 0, 85, 0.1);
        }
        .top a, .top button {
            font-family: 'Orbitron';
            font-size: 0.7rem;
            letter-spacing: 2px;
            text-decoration: none;
            color: var(--cyan);
            background: rgba(0, 243, 255, 0.06);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: all .15s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .top a:hover, .top button:hover {
            background: rgba(0, 243, 255, 0.18);
            border-color: var(--cyan);
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.4);
        }
        .container {
            max-width: 680px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .glass-card {
            background: var(--panel);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .card-title {
            font-family: 'Orbitron';
            font-size: 0.7rem;
            color: var(--cyan);
            letter-spacing: 2px;
            margin-bottom: 14px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .card-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .label-caps {
            display: block;
            font-family: 'JetBrains Mono';
            font-size: 0.62rem;
            color: var(--text-dim);
            letter-spacing: 2px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        input[type=text], input[type=email], input[type=password] {
            width: 100%;
            padding: 10px 12px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-vibrant);
            font-family: 'Rajdhani';
            font-size: 0.95rem;
            outline: none;
        }
        input:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.2);
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 600px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        .input-group { margin-bottom: 14px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            font-family: 'Orbitron';
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 2px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid var(--cyan);
            background: rgba(0, 243, 255, 0.1);
            color: var(--cyan);
            transition: all .15s;
        }
        .btn:hover {
            background: rgba(0, 243, 255, 0.22);
            box-shadow: 0 0 14px rgba(0, 243, 255, 0.35);
        }
        .alert {
            font-family: 'JetBrains Mono';
            font-size: 0.7rem;
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 14px;
            border-left: 3px solid;
        }
        .alert.err { background: rgba(255, 0, 85, 0.08); border-color: var(--pink); color: var(--pink); }
        .alert.ok  { background: rgba(10, 255, 0, 0.08); border-color: var(--green); color: var(--green); }
        .meta-row {
            font-family: 'JetBrains Mono';
            font-size: 0.65rem;
            color: var(--text-dim);
            letter-spacing: 1px;
            margin-top: 4px;
        }
        .divider {
            height: 1px;
            background: var(--border);
            margin: 18px 0;
        }
        .avatar-row {
            display: flex;
            gap: 18px;
            align-items: center;
        }
        .avatar-preview {
            width: 96px; height: 96px;
            border-radius: 50%;
            border: 2px solid var(--border);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(0, 243, 255, 0.15), rgba(0, 243, 255, 0.03));
            box-shadow: 0 0 14px rgba(0, 243, 255, 0.15);
            flex-shrink: 0;
        }
        .avatar-preview img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .avatar-initial {
            font-family: 'Orbitron';
            font-weight: 900;
            font-size: 2.2rem;
            color: var(--cyan);
            text-shadow: 0 0 12px var(--accent-glow);
        }
        @media (max-width: 600px) {
            .avatar-row { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body>
    <div class="grid-overlay"></div>

    <div class="top">
        <div class="brand">
            <img src="{{ asset('favicon.png') }}" alt="Logo">
            <h1>NOC COMMAND &middot; PROFILE</h1>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="/" style="color:var(--cyan); background:rgba(0,243,255,.08); border:1px solid rgba(0,243,255,.3); padding:8px 14px; border-radius:6px; text-decoration:none; font-family:'JetBrains Mono'; font-size:.65rem; letter-spacing:1px;"><i class="fas fa-arrow-left"></i> KEMBALI</a>
            <a href="/" style="color:var(--text-dim); border:1px solid transparent; padding:8px 14px; border-radius:6px; text-decoration:none; font-family:'JetBrains Mono'; font-size:.65rem; letter-spacing:1px;"><i class="fas fa-tachometer-alt"></i> DASHBOARD</a>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('users.index') }}" style="color:var(--text-dim); border:1px solid transparent; padding:8px 14px; border-radius:6px; text-decoration:none; font-family:'JetBrains Mono'; font-size:.65rem; letter-spacing:1px;"><i class="fas fa-users-cog"></i> USERS</a>
            @endif
            <a href="{{ route('devices.index') }}" style="color:var(--text-dim); border:1px solid transparent; padding:8px 14px; border-radius:6px; text-decoration:none; font-family:'JetBrains Mono'; font-size:.65rem; letter-spacing:1px;"><i class="fas fa-mobile-alt"></i> DEVICES</a>
            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="background:transparent; color:var(--pink); border:1px solid rgba(255,0,85,.3); padding:8px 14px; border-radius:6px; cursor:pointer; font-family:'JetBrains Mono'; font-size:.65rem; letter-spacing:1px;"><i class="fas fa-power-off"></i> LOGOUT</button>
            </form>
        </div>
    </div>

    <div class="container">

        @if ($errors->any())
            <div class="alert err">
                <i class="fas fa-exclamation-triangle"></i>
                @foreach ($errors->all() as $e) {{ $e }} @endforeach
            </div>
        @endif
        @if (session('success'))
            <div class="alert ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" autocomplete="off" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="glass-card">
                <div class="card-title"><i class="fas fa-camera"></i> FOTO PROFIL</div>

                <div class="avatar-row">
                    <div class="avatar-preview" id="avatar-preview">
                        @if ($user->photoUrl())
                            <img src="{{ $user->photoUrl() }}" alt="avatar">
                        @else
                            <span class="avatar-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    <div style="flex:1;">
                        <label class="label-caps" for="photo">Upload foto (jpg/png/webp, maks 4MB)</label>
                        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                               onchange="previewAvatar(event)" style="margin-top:6px;">
                        @if ($user->photoUrl())
                            <label style="display:inline-flex; gap:6px; margin-top:10px; cursor:pointer; font-family:'JetBrains Mono'; font-size:0.65rem; color:var(--pink); letter-spacing:1px;">
                                <input type="checkbox" name="remove_photo" value="1"> HAPUS FOTO SAAT INI
                            </label>
                        @endif
                        <div class="meta-row" style="margin-top:8px;">Rasio kotak (1:1) paling rapi.</div>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <div class="card-title"><i class="fas fa-id-card"></i> IDENTITY</div>

                <div class="grid-2">
                    <div class="input-group">
                        <label class="label-caps" for="name">Nama Lengkap</label>
                        <input id="name" name="name" type="text" required
                               value="{{ old('name', $user->name) }}">
                    </div>
                    <div class="input-group">
                        <label class="label-caps" for="username">Username</label>
                        <input id="username" name="username" type="text" required
                               pattern="[a-z0-9_.]+"
                               value="{{ old('username', $user->username) }}">
                        <div class="meta-row">huruf kecil, angka, "_" atau "."</div>
                    </div>
                </div>

                <div class="input-group">
                    <label class="label-caps" for="email">Email (opsional)</label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email', $user->email) }}">
                </div>
            </div>

            <div class="glass-card">
                <div class="card-title"><i class="fas fa-key"></i> UBAH PASSWORD <span style="font-size:.55rem; color:var(--text-dim); margin-left:6px;">(KOSONGKAN KALAU TIDAK INGIN GANTI)</span></div>

                <div class="input-group">
                    <label class="label-caps" for="current_password">Password Saat Ini</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password">
                </div>

                <div class="grid-2">
                    <div class="input-group">
                        <label class="label-caps" for="new_password">Password Baru</label>
                        <input id="new_password" name="new_password" type="password" autocomplete="new-password">
                    </div>
                    <div class="input-group">
                        <label class="label-caps" for="new_password_confirmation">Konfirmasi Password Baru</label>
                        <input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <a href="/" style="text-decoration:none;">
                    <button type="button" class="btn" style="border-color:var(--text-dim); color:var(--text-dim); background:transparent;">
                        <i class="fas fa-times"></i> CANCEL
                    </button>
                </a>
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> SIMPAN
                </button>
            </div>
        </form>
    </div>

    <script>
        function previewAvatar(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const box = document.getElementById('avatar-preview');
                box.innerHTML = '<img src="' + ev.target.result + '" alt="preview">';
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>

</html>
