@extends('layouts.app')

@section('title', 'NOC Command Center - Device & Sessions')

@push('styles')
<style>
    /* Topbar reuse .noc-topbar dari users/index (inject di sini) */
    .noc-topbar {
        width: 100%; display: flex; justify-content: space-between; align-items: center;
        padding: 12px clamp(14px, 2vw, 28px);
        border-bottom: 1px solid var(--border);
        background: linear-gradient(90deg, rgba(10,15,25,.75), transparent);
        backdrop-filter: blur(10px);
        position: sticky; top: 0; z-index: 100;
        gap: 12px; flex-wrap: wrap; margin-bottom: 8px;
    }
    .noc-topbar .brand-link { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .noc-topbar .brand-link img { width:34px; height:34px; border-radius:50%; border:1px solid var(--border); padding:3px; background:rgba(255,0,85,.1); filter:drop-shadow(0 0 4px var(--pink)); }
    .noc-topbar .brand-name { font-family:'Orbitron'; font-weight:900; font-size:.82rem; letter-spacing:3px; color:#fff; }
    .noc-topbar .brand-sub { font-family:'JetBrains Mono'; font-size:.52rem; color:var(--cyan); letter-spacing:2px; opacity:.7; }
    .noc-topbar .nav-group { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .noc-nav { display:inline-flex; align-items:center; gap:6px; font-family:'JetBrains Mono'; font-size:.65rem; color:var(--text-dim); text-decoration:none; padding:8px 14px; border-radius:6px; border:1px solid transparent; transition:all .15s; }
    .noc-nav i { font-size:.7rem; }
    .noc-nav:hover { color:var(--cyan); border-color:var(--border); background:rgba(0,243,255,.04); }
    .noc-nav.active { color:var(--cyan); background:rgba(0,243,255,.07); border-color:rgba(0,243,255,.25); }
    .noc-nav.back { color:var(--cyan); background:rgba(0,243,255,.08); border-color:rgba(0,243,255,.3); }
    .noc-nav.back:hover { background:rgba(0,243,255,.15); box-shadow:0 0 12px rgba(0,243,255,.2); }
    .noc-nav.danger { color:var(--pink); border-color:rgba(255,0,85,.3); }
    .noc-nav.danger:hover { background:rgba(255,0,85,.08); border-color:var(--pink); }
    .noc-nav.logout-form { padding:0; }
    .noc-nav.logout-form button { background:none; border:0; cursor:pointer; color:inherit; font:inherit; letter-spacing:inherit; padding:8px 14px; display:inline-flex; align-items:center; gap:6px; }

    .breadcrumb { display:flex; align-items:center; gap:8px; font-family:'JetBrains Mono'; font-size:.62rem; color:var(--text-dim); letter-spacing:1px; margin: 0 auto 18px; max-width:960px; padding:0 clamp(14px,2vw,28px); }
    .breadcrumb a { color:var(--cyan); text-decoration:none; }
    .breadcrumb .sep { color:var(--border); }

    .main { max-width: 960px; margin: 0 auto; width: 100%; padding: 0 clamp(14px, 2vw, 28px) 40px; }

    .page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; margin-bottom:24px; }
    .page-title { font-family:'Orbitron'; font-weight:700; font-size:1.1rem; letter-spacing:3px; color:var(--cyan); }
    .page-sub { font-family:'JetBrains Mono'; font-size:.65rem; color:var(--text-dim); letter-spacing:1px; margin-top:4px; }

    .btn-danger-ghost {
        display:inline-flex; align-items:center; gap:8px;
        padding:9px 16px;
        background:rgba(255,0,85,.08);
        border:1px solid rgba(255,0,85,.3); color:var(--pink);
        font-family:'Orbitron'; font-weight:700; font-size:.68rem; letter-spacing:2px;
        border-radius:8px; cursor:pointer; text-decoration:none;
        transition:all .15s;
    }
    .btn-danger-ghost:hover { background:rgba(255,0,85,.2); box-shadow:0 0 12px rgba(255,0,85,.3); }
    .btn-danger-ghost:disabled { opacity:.4; cursor:not-allowed; }

    /* Alert banners */
    .alert-banner {
        font-family:'JetBrains Mono'; font-size:.72rem;
        padding:10px 14px; border-radius:8px; margin-bottom:18px;
        border-left:3px solid; display:flex; align-items:center; gap:8px;
    }
    .alert-banner.ok  { background:rgba(10,255,0,.07); border-color:var(--green); color:var(--green); }
    .alert-banner.err { background:rgba(255,0,85,.07); border-color:var(--pink); color:var(--pink); }

    /* Sessions grid */
    .sessions-list { display:grid; gap:10px; }

    .session-card {
        background: linear-gradient(135deg, rgba(255,255,255,.02), rgba(255,255,255,.005)), rgba(10,15,25,.55);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 14px 16px;
        display: grid;
        grid-template-columns: 44px 1fr auto;
        gap: 14px;
        align-items: center;
        transition: border-color .15s, box-shadow .15s;
    }
    .session-card:hover { border-color: rgba(0,243,255,.35); }
    .session-card.current { border-color: rgba(10,255,0,.4); box-shadow: 0 0 14px rgba(10,255,0,.1); }

    .session-icon {
        width: 44px; height: 44px; border-radius: 10px;
        background: linear-gradient(135deg, rgba(0,243,255,.15), rgba(0,243,255,.03));
        border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        color: var(--cyan); font-size: 1.15rem;
    }
    .session-card.current .session-icon { background: linear-gradient(135deg, rgba(10,255,0,.2), rgba(10,255,0,.05)); border-color: var(--green); color: var(--green); }

    .session-info { min-width: 0; }
    .session-title {
        font-family: 'Rajdhani', sans-serif;
        font-size: .95rem; font-weight: 600; color: var(--text-vibrant);
        line-height: 1.2;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .session-title .badge-cur {
        background: rgba(10,255,0,.15); border: 1px solid rgba(10,255,0,.4);
        color: var(--green); font-family:'JetBrains Mono'; font-size:.55rem;
        padding: 2px 8px; border-radius: 10px; letter-spacing: 1px;
    }
    .session-meta {
        font-family: 'JetBrains Mono'; font-size:.62rem;
        color: var(--text-dim); letter-spacing:.5px;
        margin-top: 4px; display: flex; gap: 10px; flex-wrap: wrap;
    }
    .session-meta i { color: var(--cyan); opacity: .7; margin-right: 3px; }

    .session-actions { display: flex; gap: 8px; }
    .btn-signout {
        background: transparent;
        border: 1px solid rgba(255,0,85,.3); color: var(--pink);
        padding: 7px 12px; border-radius: 6px; cursor: pointer;
        font-family: 'JetBrains Mono'; font-size: .62rem; letter-spacing: 1px;
        transition: all .15s; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-signout:hover { background: rgba(255,0,85,.12); border-color: var(--pink); }

    .empty-state, .unsupported {
        text-align: center; padding: 40px 20px;
        background: rgba(0,0,0,.25);
        border: 1px dashed var(--border);
        border-radius: 12px;
        font-family: 'JetBrains Mono';
        color: var(--text-dim); font-size: .78rem; line-height: 1.6;
    }
    .empty-state i, .unsupported i {
        font-size: 2.2rem; display: block; margin-bottom: 12px;
        color: var(--yellow); opacity: .8;
    }

    /* Web Push toggle card */
    .push-card {
        background: linear-gradient(135deg, rgba(0,243,255,.05), rgba(0,243,255,.01)), rgba(10,15,25,.55);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 20px;
        display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    }
    .push-card .pi {
        width: 44px; height: 44px; border-radius: 10px;
        background: linear-gradient(135deg, rgba(255,204,0,.2), rgba(255,204,0,.03));
        border: 1px solid rgba(255,204,0,.4); color: var(--yellow);
        display: flex; align-items: center; justify-content: center; font-size: 1.15rem;
    }
    .push-card .pt { flex: 1; min-width: 220px; }
    .push-card .pt strong { font-family:'Orbitron'; font-size:.75rem; letter-spacing:1.5px; color:var(--text-vibrant); text-transform:uppercase; display:block; margin-bottom:4px; }
    .push-card .pt span { font-family:'JetBrains Mono'; font-size:.68rem; color:var(--text-dim); line-height:1.4; }
    .push-card .pa button {
        background: linear-gradient(135deg, rgba(0,243,255,.2), rgba(0,243,255,.05));
        border: 1px solid var(--cyan); color: var(--cyan);
        padding: 10px 18px; border-radius: 8px; cursor: pointer;
        font-family: 'Orbitron'; font-weight: 700; font-size: .68rem; letter-spacing: 2px;
        transition: all .15s;
    }
    .push-card .pa button:hover { background: rgba(0,243,255,.3); box-shadow: 0 0 14px rgba(0,243,255,.3); }
    .push-card .pa button.subscribed { background: rgba(10,255,0,.15); border-color: var(--green); color: var(--green); }

    @media (max-width: 640px) {
        .noc-topbar .brand-sub { display: none; }
        .noc-nav { padding: 7px 10px; font-size: .6rem; }
        .noc-nav span { display: none; }
        .noc-nav i { font-size: .85rem; }
        .session-card { grid-template-columns: 40px 1fr; }
        .session-actions { grid-column: 1 / -1; justify-content: flex-end; }
    }
</style>
@endpush

@section('topbar')
<nav class="noc-topbar">
    <a href="/" class="brand-link">
        <img src="{{ asset('favicon.png') }}" alt="Logo">
        <div><div class="brand-name">NOC INKASA</div><div class="brand-sub">COMMAND CENTER</div></div>
    </a>
    <div class="nav-group">
        <a href="/" class="noc-nav back"><i class="fas fa-arrow-left"></i> <span>Kembali</span></a>
        <a href="/" class="noc-nav"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('users.index') }}" class="noc-nav"><i class="fas fa-users-cog"></i> <span>Users</span></a>
        @endif
        <a href="{{ route('profile.edit') }}" class="noc-nav"><i class="fas fa-user-circle"></i> <span>Profil</span></a>
        <a href="{{ route('devices.index') }}" class="noc-nav active"><i class="fas fa-mobile-alt"></i> <span>Devices</span></a>
        <form method="POST" action="{{ route('logout') }}" class="noc-nav logout-form danger" style="margin:0">
            @csrf
            <button type="submit"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></button>
        </form>
    </div>
</nav>
<div class="breadcrumb">
    <a href="/"><i class="fas fa-home"></i></a>
    <span class="sep">›</span>
    <a href="/">Dashboard</a>
    <span class="sep">›</span>
    <span>Perangkat & Sesi</span>
</div>
@endsection

@section('content')
<div class="main">
    <div class="page-header">
        <div>
            <div class="page-title"><i class="fas fa-shield-halved" style="margin-right:10px;font-size:.9rem"></i>PERANGKAT &amp; SESI AKTIF</div>
            <div class="page-sub">KELOLA DEVICE YANG SAAT INI LOGIN KE AKUN ANDA</div>
        </div>
        @if(!$unsupported && $sessions->count() > 1)
        <form method="POST" action="{{ route('devices.destroyOthers') }}" style="margin:0"
              data-confirm="Semua device lain akan di-logout. Hanya device yang sedang Anda pakai yang tetap login."
              data-confirm-type="danger"
              data-confirm-title="LOGOUT SEMUA DEVICE LAIN">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger-ghost">
                <i class="fas fa-power-off"></i> LOGOUT DEVICE LAIN ({{ $sessions->count() - 1 }})
            </button>
        </form>
        @endif
    </div>

    {{-- Web Push Toggle --}}
    <div class="push-card">
        <div class="pi"><i class="fas fa-bell"></i></div>
        <div class="pt">
            <strong>Notifikasi Alarm ke Device Ini</strong>
            <span>Aktifkan untuk terima notifikasi push instan (bahkan saat browser tidak dibuka) tiap kali sensor melewati ambang batas.</span>
        </div>
        <div class="pa">
            <button type="button" id="btn-push-toggle" data-state="loading">
                <i class="fas fa-spinner fa-spin"></i> MEMERIKSA...
            </button>
        </div>
    </div>

    @if($unsupported)
        <div class="unsupported">
            <i class="fas fa-triangle-exclamation"></i>
            Fitur ini butuh <code>SESSION_DRIVER=database</code>.<br>
            Update <code>.env</code> lalu jalankan <code>php artisan session:table &amp;&amp; php artisan migrate</code>.
        </div>
    @elseif($sessions->isEmpty())
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            Tidak ada session aktif ter-record.<br>
            <em>Coba logout &amp; login ulang.</em>
        </div>
    @else
        <div class="sessions-list">
            @foreach($sessions as $s)
                <div class="session-card @if($s->is_current)current @endif">
                    <div class="session-icon"><i class="fas {{ $s->device['icon'] }}"></i></div>
                    <div class="session-info">
                        <div class="session-title">
                            {{ $s->device['device'] }} &middot; {{ $s->device['browser'] }}
                            @if($s->is_current)
                                <span class="badge-cur"><i class="fas fa-circle" style="font-size:.4rem"></i> AKTIF SEKARANG</span>
                            @endif
                        </div>
                        <div class="session-meta">
                            <span><i class="fas fa-network-wired"></i>{{ $s->ip_address ?? '-' }}</span>
                            <span><i class="fas fa-clock"></i>{{ $s->last_seen->diffForHumans() }}</span>
                            <span><i class="fas fa-calendar"></i>{{ $s->last_seen->format('d M Y H:i') }}</span>
                        </div>
                    </div>
                    <div class="session-actions">
                        @if(!$s->is_current)
                            <form method="POST" action="{{ route('devices.destroy', $s->id) }}" style="margin:0"
                                  data-confirm="Logout device ini? User akan diminta login ulang jika mereka aktif kembali."
                                  data-confirm-type="danger"
                                  data-confirm-title="LOGOUT DEVICE">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-signout">
                                    <i class="fas fa-sign-out-alt"></i> LOGOUT
                                </button>
                            </form>
                        @else
                            <span class="btn-signout" style="opacity:.4;cursor:default;background:rgba(10,255,0,.05);border-color:rgba(10,255,0,.2);color:var(--green);">
                                <i class="fas fa-check"></i> DEVICE INI
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ===== WEB PUSH TOGGLE =====
    const btn = document.getElementById('btn-push-toggle');
    if (!btn) return;

    function setBtn(state, label, icon = '') {
        btn.dataset.state = state;
        btn.classList.toggle('subscribed', state === 'on');
        btn.innerHTML = (icon ? `<i class="fas ${icon}"></i> ` : '') + label;
    }

    // Feature detection
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        setBtn('unsupported', 'BROWSER TIDAK MENDUKUNG', 'fa-ban');
        btn.disabled = true;
        return;
    }

    async function refresh() {
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) setBtn('on',  'AKTIF · KLIK UNTUK MATIKAN', 'fa-check-circle');
            else     setBtn('off', 'AKTIFKAN NOTIFIKASI', 'fa-bell');
        } catch (e) {
            setBtn('off', 'AKTIFKAN NOTIFIKASI', 'fa-bell');
        }
    }

    async function subscribe() {
        setBtn('busy', 'MEMPROSES...', 'fa-spinner fa-spin');
        try {
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') {
                if (window.NocCyber) NocCyber.warning('Izin notifikasi ditolak. Aktifkan lewat pengaturan browser.');
                return refresh();
            }
            // Get VAPID key
            const vapidResp = await fetch('{{ route('push.vapid') }}');
            const { publicKey } = await vapidResp.json();
            if (!publicKey) {
                if (window.NocCyber) NocCyber.error('VAPID key belum di-set di server (.env VAPID_PUBLIC_KEY)');
                return refresh();
            }
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlB64ToUint8Array(publicKey),
            });
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const resp = await fetch('{{ route('push.subscribe') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(sub),
            });
            if (resp.ok) {
                if (window.NocCyber) NocCyber.success('Notifikasi alarm ke device ini aktif!', { title: 'PUSH AKTIF' });
            } else {
                if (window.NocCyber) NocCyber.error('Gagal daftar ke server.');
            }
        } catch (e) {
            console.error(e);
            if (window.NocCyber) NocCyber.error('Error: ' + e.message);
        }
        refresh();
    }

    async function unsubscribe() {
        setBtn('busy', 'MEMPROSES...', 'fa-spinner fa-spin');
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                await fetch('{{ route('push.unsubscribe') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ endpoint: sub.endpoint }),
                });
                await sub.unsubscribe();
                if (window.NocCyber) NocCyber.info('Notifikasi push di-nonaktifkan.');
            }
        } catch (e) {
            if (window.NocCyber) NocCyber.error('Gagal unsubscribe.');
        }
        refresh();
    }

    btn.addEventListener('click', () => {
        if (btn.dataset.state === 'on') return unsubscribe();
        if (btn.dataset.state === 'off') return subscribe();
    });

    function urlB64ToUint8Array(base64) {
        const padding = '='.repeat((4 - base64.length % 4) % 4);
        const b64 = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(b64);
        const out = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
        return out;
    }

    refresh();
})();
</script>
@endpush
