<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'NOC Command Center')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- External Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- NOC Global Theme -->
    <link rel="stylesheet" href="{{ asset('css/noc-theme.css') }}?v=4">

    <!-- NOC Cyber UI (toast/alert/confirm/prompt/loading) -->
    <script defer src="{{ asset('js/noc-cyber.js') }}?v=3"></script>

    <!-- NOC PWA helpers (install prompt, offline detect, vibrate, wake-lock) -->
    <script defer src="{{ asset('js/noc-pwa.js') }}?v=1"></script>

    <!-- SweetAlert2 (legacy compat, opsional) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('head')
    @stack('styles')
</head>
<body>
    <div class="grid-overlay"></div>
    <div class="scanline"></div>

    @yield('topbar')

    <main class="w-100" style="flex: 1; padding: clamp(12px, 2vw, 24px);">
        @yield('content')
    </main>

    <!-- Global Notification Flash: pakai NocCyber toast -->
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            @if(session('success'))
                if (window.NocCyber) NocCyber.success(@json(session('success')));
            @endif
            @if(session('error'))
                if (window.NocCyber) NocCyber.error(@json(session('error')));
            @endif
            @if(session('warning'))
                if (window.NocCyber) NocCyber.warning(@json(session('warning')));
            @endif
            @if(session('info'))
                if (window.NocCyber) NocCyber.info(@json(session('info')));
            @endif
        });

        // Legacy SweetAlert compat (kalau ada page yg masih pakai)
        window.cyberToast = {
            fire: function (opts) {
                if (!window.NocCyber) return;
                const map = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
                NocCyber.toast(opts.title || '', { type: map[opts.icon] || 'info' });
            }
        };
    </script>

    @stack('scripts')
</body>
</html>
