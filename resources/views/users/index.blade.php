@extends('layouts.app')

@section('title', 'NOC Command Center - Manajemen User')

@push('styles')
<style>
    /* NOC topbar — sekarang tampil (dulunya display:none, dihapus) */
    .noc-topbar {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px clamp(14px, 2vw, 28px);
        border-bottom: 1px solid var(--border);
        background: linear-gradient(90deg, rgba(10,15,25,.75), transparent);
        backdrop-filter: blur(10px);
        position: sticky; top: 0; z-index: 100;
        gap: 12px; flex-wrap: wrap;
        margin-bottom: 8px;
    }
    .noc-topbar .brand-link {
        display: flex; align-items: center; gap: 12px;
        text-decoration: none;
        min-width: 0;
    }
    .noc-topbar .brand-link img {
        width: 34px; height: 34px; border-radius: 50%;
        border: 1px solid var(--border); padding: 3px;
        background: rgba(255,0,85,.1);
        filter: drop-shadow(0 0 4px var(--pink));
    }
    .noc-topbar .brand-txt { line-height: 1.1; min-width: 0; }
    .noc-topbar .brand-name {
        font-family: 'Orbitron'; font-weight: 900; font-size: .82rem;
        letter-spacing: 3px; color: #fff;
    }
    .noc-topbar .brand-sub {
        font-family: 'JetBrains Mono'; font-size: .52rem;
        color: var(--cyan); letter-spacing: 2px; opacity: .7;
    }
    .noc-topbar .nav-group {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .noc-nav {
        display: inline-flex; align-items: center; gap: 6px;
        font-family: 'JetBrains Mono'; font-size: .65rem;
        color: var(--text-dim); text-decoration: none;
        padding: 8px 14px; border-radius: 6px;
        border: 1px solid transparent;
        transition: all .15s;
    }
    .noc-nav i { font-size: .7rem; }
    .noc-nav:hover { color: var(--cyan); border-color: var(--border); background: rgba(0,243,255,.04); }
    .noc-nav.active {
        color: var(--cyan); background: rgba(0,243,255,.07);
        border-color: rgba(0,243,255,.25);
    }
    .noc-nav.danger { color: var(--pink); border-color: rgba(255,0,85,.3); }
    .noc-nav.danger:hover { background: rgba(255,0,85,.08); border-color: var(--pink); }
    .noc-nav.back {
        color: var(--cyan);
        background: rgba(0,243,255,.08);
        border-color: rgba(0,243,255,.3);
    }
    .noc-nav.back:hover { background: rgba(0,243,255,.15); box-shadow: 0 0 12px rgba(0,243,255,.2); }
    .noc-nav.logout-form { padding: 0; }
    .noc-nav.logout-form button {
        background: none; border: 0; cursor: pointer;
        color: inherit; font: inherit; letter-spacing: inherit;
        padding: 8px 14px; display: inline-flex; align-items: center; gap: 6px;
    }

    /* Breadcrumb */
    .breadcrumb {
        display: flex; align-items: center; gap: 8px;
        font-family: 'JetBrains Mono'; font-size: .62rem;
        color: var(--text-dim); letter-spacing: 1px;
        margin: 0 auto 18px; max-width: 1100px;
        padding: 0 clamp(14px, 2vw, 28px);
    }
    .breadcrumb a { color: var(--cyan); text-decoration: none; transition: opacity .15s; }
    .breadcrumb a:hover { opacity: .7; }
    .breadcrumb .sep { color: var(--border); }

    @media (max-width: 640px) {
        .noc-topbar .brand-sub { display: none; }
        .noc-nav { padding: 7px 10px; font-size: .6rem; }
        .noc-nav span { display: none; }
        .noc-nav i { font-size: .85rem; }
    }

    .main { max-width: 1100px; margin: 0 auto; width: 100%; padding: 0 clamp(14px, 2vw, 28px); }

    .page-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        margin-bottom: 24px; flex-wrap: wrap; gap: 14px;
    }

    .page-title { font-family: 'Orbitron'; font-weight: 700; font-size: 1.1rem; letter-spacing: 3px; color: var(--cyan); }
    .page-sub { font-family: 'JetBrains Mono'; font-size: 0.65rem; color: var(--text-dim); letter-spacing: 1px; margin-top: 4px; }

    .btn-primary {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px;
        background: linear-gradient(135deg, rgba(0,243,255,0.15), rgba(0,243,255,0.04));
        border: 1px solid var(--cyan);
        color: var(--cyan);
        font-family: 'Orbitron'; font-weight: 700; font-size: 0.7rem; letter-spacing: 2px;
        border-radius: 8px; cursor: pointer; text-decoration: none;
        transition: all 0.15s;
    }
    .btn-primary:hover { background: rgba(0,243,255,0.22); box-shadow: 0 0 16px rgba(0,243,255,0.35); }

    /* ── FILTER BAR ── */
    .filter-bar {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .search-wrap { position: relative; flex: 1; min-width: 200px; }
    .search-wrap i { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: var(--cyan); font-size: 0.8rem; opacity: 0.6; }
    .search-input {
        width: 100%; padding: 9px 12px 9px 36px;
        background: rgba(0,0,0,0.35); border: 1px solid var(--border);
        border-radius: 8px; color: var(--text-vibrant);
        font-family: 'Rajdhani'; font-size: 0.9rem; outline: none;
        transition: all 0.15s;
    }
    .search-input:focus { border-color: var(--cyan); box-shadow: 0 0 10px rgba(0,243,255,0.2); }

    .select-filter {
        padding: 9px 14px;
        background: rgba(0,0,0,0.35); border: 1px solid var(--border);
        border-radius: 8px; color: var(--text-vibrant);
        font-family: 'JetBrains Mono'; font-size: 0.7rem; outline: none;
        cursor: pointer; transition: all 0.15s;
    }
    .select-filter:focus { border-color: var(--cyan); }

    .btn-filter {
        padding: 9px 18px;
        background: rgba(0,243,255,0.08); border: 1px solid var(--border);
        border-radius: 8px; color: var(--cyan);
        font-family: 'JetBrains Mono'; font-size: 0.7rem; cursor: pointer;
        transition: all 0.15s;
    }
    .btn-filter:hover { background: rgba(0,243,255,0.15); }

    /* ── TABLE ── */
    .table-card {
        background: var(--panel); border: 1px solid var(--border);
        border-radius: 12px; overflow: hidden;
        backdrop-filter: blur(12px);
    }

    table { width: 100%; border-collapse: collapse; }

    thead th {
        padding: 13px 16px;
        text-align: left;
        font-family: 'JetBrains Mono'; font-size: 0.6rem; letter-spacing: 2px;
        color: var(--text-dim); text-transform: uppercase;
        border-bottom: 1px solid var(--border);
        background: rgba(0,0,0,0.2);
    }

    tbody tr {
        border-bottom: 1px solid rgba(0,243,255,0.05);
        transition: background 0.12s;
    }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: rgba(0,243,255,0.04); }

    td { padding: 13px 16px; font-size: 0.95rem; vertical-align: middle; }

    .user-cell { display: flex; align-items: center; gap: 12px; }

    .avatar {
        width: 38px; height: 38px; border-radius: 50%;
        border: 1px solid var(--border); object-fit: cover;
        flex-shrink: 0;
    }

    .avatar-initials {
        width: 38px; height: 38px; border-radius: 50%;
        background: linear-gradient(135deg, rgba(0,243,255,0.2), rgba(255,0,85,0.2));
        border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Orbitron'; font-size: 0.75rem; font-weight: 700;
        color: var(--cyan); flex-shrink: 0;
    }

    .user-name { font-weight: 600; }
    .user-username { font-family: 'JetBrains Mono'; font-size: 0.65rem; color: var(--text-dim); }

    .role-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px;
        font-family: 'JetBrains Mono'; font-size: 0.6rem; font-weight: 700; letter-spacing: 1px;
    }
    .role-badge.superadmin {
        background: rgba(255, 0, 85, 0.12); border: 1px solid rgba(255,0,85,0.3);
        color: var(--pink);
    }
    .role-badge.admin {
        background: rgba(0, 243, 255, 0.1); border: 1px solid rgba(0,243,255,0.25);
        color: var(--cyan);
    }

    .email-text { font-family: 'JetBrains Mono'; font-size: 0.65rem; color: var(--text-dim); }
    .no-email { font-family: 'JetBrains Mono'; font-size: 0.6rem; color: rgba(112,128,144,0.5); font-style: italic; }

    .actions { display: flex; align-items: center; gap: 8px; }

    .btn-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 6px;
        border: 1px solid; cursor: pointer;
        transition: all 0.15s; text-decoration: none;
        font-size: 0.75rem;
    }
    .btn-icon.edit { border-color: rgba(255,204,0,0.3); color: var(--yellow); background: transparent; }
    .btn-icon.edit:hover { background: rgba(255,204,0,0.1); border-color: var(--yellow); box-shadow: 0 0 8px rgba(255,204,0,0.3); }
    .btn-icon.del { border-color: rgba(255,0,85,0.3); color: var(--pink); background: transparent; }
    .btn-icon.del:hover { background: rgba(255,0,85,0.1); border-color: var(--pink); box-shadow: 0 0 8px rgba(255,0,85,0.3); }

    /* ── ALERT ── */
    .alert-banner {
        font-family: 'JetBrains Mono'; font-size: 0.72rem;
        padding: 10px 14px; border-radius: 8px; margin-bottom: 18px;
        border-left: 3px solid; display: flex; align-items: center; gap: 8px;
    }
    .alert-banner.ok  { background: rgba(10,255,0,0.07); border-color: var(--green); color: var(--green); }
    .alert-banner.err { background: rgba(255,0,85,0.07); border-color: var(--pink); color: var(--pink); }

    /* ── PAGINATION ── */
    .pagination-wrap {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 16px;
        border-top: 1px solid var(--border);
        font-family: 'JetBrains Mono'; font-size: 0.65rem; color: var(--text-dim);
    }

    .pagination { display: flex; gap: 4px; }
    .pagination a, .pagination span {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 6px;
        font-family: 'JetBrains Mono'; font-size: 0.65rem;
        border: 1px solid var(--border); text-decoration: none;
        color: var(--text-dim); transition: all 0.12s;
    }
    .pagination a:hover { color: var(--cyan); border-color: var(--cyan); background: rgba(0,243,255,0.07); }
    .pagination span.active { background: rgba(0,243,255,0.15); border-color: var(--cyan); color: var(--cyan); }
    .pagination span.dots { border: none; cursor: default; }

    .empty-state {
        text-align: center; padding: 50px 20px;
        font-family: 'JetBrains Mono'; font-size: 0.75rem; color: var(--text-dim);
        letter-spacing: 1px;
    }
    .empty-state i { font-size: 2.5rem; color: var(--border); display: block; margin-bottom: 12px; }

    /* ── DELETE MODAL ── */
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(2,4,8,0.85); z-index: 999;
        align-items: center; justify-content: center;
        backdrop-filter: blur(4px);
    }
    .modal-overlay.show { display: flex; }

    .modal-box {
        background: #080d18; border: 1px solid rgba(255,0,85,0.35);
        border-radius: 12px; padding: 30px 28px; max-width: 380px; width: 90%;
        box-shadow: 0 0 40px rgba(255,0,85,0.15);
    }
    .modal-title { font-family: 'Orbitron'; font-size: 0.9rem; color: var(--pink); letter-spacing: 2px; margin-bottom: 12px; }
    .modal-body { font-size: 0.9rem; color: var(--text-dim); margin-bottom: 22px; line-height: 1.5; }
    .modal-body strong { color: var(--text-vibrant); }
    .modal-actions { display: flex; gap: 10px; }
    .modal-btn {
        flex: 1; padding: 10px; border-radius: 7px; border: 1px solid;
        font-family: 'JetBrains Mono'; font-size: 0.7rem; letter-spacing: 1px;
        cursor: pointer; transition: all 0.15s;
    }
    .modal-btn.cancel { border-color: var(--border); color: var(--text-dim); background: transparent; }
    .modal-btn.cancel:hover { border-color: var(--cyan); color: var(--cyan); }
    .modal-btn.confirm { border-color: var(--pink); color: var(--pink); background: rgba(255,0,85,0.08); }
    .modal-btn.confirm:hover { background: rgba(255,0,85,0.18); box-shadow: 0 0 10px rgba(255,0,85,0.3); }

    /* ── RESPONSIVE: table -> card di mobile ── */
    @media (max-width: 780px) {
        .filter-bar { gap: 8px; }
        .search-wrap { flex: 1 1 100%; min-width: 0; }
        .select-filter, .btn-filter { flex: 1; }
        thead { display: none; }
        table, tbody, tr, td { display: block; width: 100%; }
        tr {
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 10px;
            padding: 10px 12px;
        }
        td {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(0,243,255,0.05) !important;
            font-size: .82rem !important;
        }
        td:last-child { border-bottom: none !important; }
        td::before {
            content: attr(data-label);
            font-family: 'JetBrains Mono';
            font-size: .58rem;
            color: var(--text-dim);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        td .user-cell { justify-content: flex-end; }
        .pagination-wrap { flex-direction: column; gap: 10px; align-items: stretch; }
        .pagination { justify-content: center; }
    }
</style>
@endpush

@section('topbar')
<nav class="noc-topbar" aria-label="Main navigation">
    <a href="/" class="brand-link">
        <img src="{{ asset('favicon.png') }}" alt="Logo">
        <div class="brand-txt">
            <div class="brand-name">NOC INKASA</div>
            <div class="brand-sub">COMMAND CENTER</div>
        </div>
    </a>
    <div class="nav-group">
        <a href="/" class="noc-nav back" title="Kembali ke Dashboard">
            <i class="fas fa-arrow-left"></i> <span>Kembali</span>
        </a>
        <a href="/" class="noc-nav"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="{{ route('users.index') }}" class="noc-nav active"><i class="fas fa-users-cog"></i> <span>Users</span></a>
        <a href="{{ route('devices.index') }}" class="noc-nav"><i class="fas fa-mobile-alt"></i> <span>Devices</span></a>
        <a href="{{ route('profile.edit') }}" class="noc-nav"><i class="fas fa-user-circle"></i> <span>Profil</span></a>
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
    <span>Manajemen User</span>
</div>
@endsection

@section('content')
<div class="main">
    <div class="page-header">
        <div>
            <div class="page-title"><i class="fas fa-users-cog" style="margin-right:10px;font-size:.9rem"></i>MANAJEMEN USER</div>
            <div class="page-sub">SUPERADMIN PANEL · KELOLA AKUN PENGGUNA SISTEM</div>
        </div>
        <a href="{{ route('users.create') }}" class="btn-primary">
            <i class="fas fa-user-plus"></i> TAMBAH USER
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert-banner ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-banner err"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
    @endif
    @if($errors->has('delete'))
        <div class="alert-banner err"><i class="fas fa-exclamation-triangle"></i> {{ $errors->first('delete') }}</div>
    @endif

    {{-- Filter --}}
    <form method="GET" action="{{ route('users.index') }}" class="filter-bar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="search-input" placeholder="Cari nama, username, email..."
                value="{{ request('search') }}">
        </div>
        <select name="role" class="select-filter">
            <option value="">Semua Role</option>
            <option value="superadmin" @selected(request('role') === 'superadmin')>Superadmin</option>
            <option value="admin" @selected(request('role') === 'admin')>Admin</option>
        </select>
        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
        @if(request('search') || request('role'))
            <a href="{{ route('users.index') }}" class="btn-filter" style="text-decoration:none; color:var(--pink); border-color:rgba(255,0,85,0.3);">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Bergabung</th>
                    <th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td data-label="#" style="font-family:'JetBrains Mono';font-size:.7rem;color:var(--text-dim)">
                            {{ $u->id }}
                        </td>
                        <td data-label="User">
                            <div class="user-cell">
                                @if($u->photoUrl())
                                    <img src="{{ $u->photoUrl() }}" alt="" class="avatar">
                                @else
                                    <div class="avatar-initials">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="user-name">{{ $u->name }}</div>
                                    <div class="user-username">{{ '@' . $u->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Email">
                            @if($u->email)
                                <span class="email-text">{{ $u->email }}</span>
                            @else
                                <span class="no-email">—</span>
                            @endif
                        </td>
                        <td data-label="Role">
                            <span class="role-badge {{ $u->role }}">
                                @if($u->role === 'superadmin')
                                    <i class="fas fa-crown" style="font-size:.55rem"></i> SUPERADMIN
                                @else
                                    <i class="fas fa-user-shield" style="font-size:.55rem"></i> ADMIN
                                @endif
                            </span>
                        </td>
                        <td data-label="Bergabung" style="font-family:'JetBrains Mono';font-size:.65rem;color:var(--text-dim)">
                            {{ $u->created_at->format('d M Y') }}
                        </td>
                        <td data-label="Aksi">
                            <div class="actions" style="justify-content:flex-end">
                                <a href="{{ route('users.edit', $u) }}" class="btn-icon edit" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                @if($u->id !== auth()->id())
                                    <button type="button" class="btn-icon del" title="Hapus"
                                        onclick="confirmDelete({{ $u->id }}, '{{ addslashes($u->name) }}')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                @else
                                    <span class="btn-icon del" title="Tidak bisa hapus diri sendiri"
                                        style="opacity:.3;cursor:not-allowed">
                                        <i class="fas fa-trash-alt"></i>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" data-label="">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                Tidak ada user ditemukan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="pagination-wrap">
                <span>Menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} user</span>
                <div class="pagination">
                    {{-- Previous --}}
                    @if($users->onFirstPage())
                        <span style="opacity:.3"><i class="fas fa-chevron-left" style="font-size:.6rem"></i></span>
                    @else
                        <a href="{{ $users->previousPageUrl() }}"><i class="fas fa-chevron-left" style="font-size:.6rem"></i></a>
                    @endif

                    {{-- Pages --}}
                    @foreach($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                        @if($page == $users->currentPage())
                            <span class="active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($users->hasMorePages())
                        <a href="{{ $users->nextPageUrl() }}"><i class="fas fa-chevron-right" style="font-size:.6rem"></i></a>
                    @else
                        <span style="opacity:.3"><i class="fas fa-chevron-right" style="font-size:.6rem"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Hidden delete form (dipakai oleh NocCyber.danger) --}}
<form id="deleteForm" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    // Konfirmasi hapus user pakai NocCyber (bukan modal manual + browser confirm).
    function confirmDelete(id, name) {
        const form = document.getElementById('deleteForm');
        form.action = '/users/' + id;
        if (window.NocCyber) {
            NocCyber.danger(
                'Anda akan menghapus user <strong>' + name.replace(/[<>]/g, '') + '</strong>.<br>Tindakan ini <strong>tidak dapat dibatalkan</strong>.',
                { title: 'HAPUS USER', htmlBody: true, confirmText: 'YA, HAPUS', cancelText: 'BATAL' }
            ).then(ok => { if (ok) form.submit(); });
        } else {
            if (confirm('Hapus user ' + name + '?')) form.submit();
        }
    }
</script>
@endpush
