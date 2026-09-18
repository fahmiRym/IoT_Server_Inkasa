/*!
 * NOC PWA — installable app helpers.
 * Fitur:
 *  - Auto-detect PWA-installable + tampilkan banner install (dismissible)
 *  - Online/Offline detection dgn cyber toast
 *  - Standalone mode detection (untuk hide UI web tertentu)
 *  - Update SW notification saat ada versi baru
 *  - Wake-lock saat monitoring (opsional, hemat baterai)
 *  - Vibrate on alarm (mobile)
 * Depend: NocCyber (noc-cyber.js)
 * -----------------------------------------------------------------
 */
(function (w, d) {
    'use strict';
    if (!w.NocCyber) { console.warn('NocPwa: NocCyber not loaded'); }

    const N = w.NocCyber || { toast: () => {}, success: () => {}, error: () => {}, info: () => {}, warning: () => {}, confirm: () => Promise.resolve(false) };

    // ── STANDALONE DETECTION ──
    const isStandalone = () =>
        w.matchMedia && w.matchMedia('(display-mode: standalone)').matches
        || w.matchMedia && w.matchMedia('(display-mode: window-controls-overlay)').matches
        || w.navigator.standalone === true;

    if (isStandalone()) d.documentElement.classList.add('is-pwa');

    // ── INSTALL PROMPT (Android Chrome) ──
    let deferredPrompt = null;
    const INSTALL_DISMISS_KEY = 'noc-pwa-dismissed';

    w.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Jangan spam: kalau user pernah dismiss dalam 7 hari terakhir, skip
        const dismissed = parseInt(localStorage.getItem(INSTALL_DISMISS_KEY) || '0', 10);
        if (dismissed && (Date.now() - dismissed) < 7 * 24 * 3600 * 1000) return;

        // Jangan tampilkan kalau sudah standalone
        if (isStandalone()) return;

        // Tampilkan banner subtle setelah 5 detik agar user sudah lihat konten dulu
        setTimeout(showInstallBanner, 5000);
    });

    w.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        localStorage.removeItem(INSTALL_DISMISS_KEY);
        N.success('NOC Command Center berhasil dipasang di device Anda!', { title: 'APP TERPASANG', duration: 5000 });
        dismissInstallBanner();
    });

    function showInstallBanner() {
        if (!deferredPrompt || d.getElementById('noc-install-banner')) return;

        const el = d.createElement('div');
        el.id = 'noc-install-banner';
        el.innerHTML = `
            <div class="npi-content">
                <div class="npi-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                <div class="npi-text">
                    <strong>Pasang sebagai App</strong>
                    <span>Akses cepat NOC dari home screen · offline-ready</span>
                </div>
                <div class="npi-actions">
                    <button class="npi-btn npi-cancel" type="button" aria-label="Nanti saja">Nanti</button>
                    <button class="npi-btn npi-ok" type="button"><i class="fa-solid fa-download"></i> Pasang</button>
                </div>
            </div>
        `;
        d.body.appendChild(el);

        // Slide-in
        requestAnimationFrame(() => el.classList.add('visible'));

        el.querySelector('.npi-cancel').addEventListener('click', () => {
            localStorage.setItem(INSTALL_DISMISS_KEY, Date.now().toString());
            dismissInstallBanner();
        });
        el.querySelector('.npi-ok').addEventListener('click', async () => {
            if (!deferredPrompt) return dismissInstallBanner();
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice.catch(() => ({ outcome: 'dismissed' }));
            if (outcome === 'accepted') N.info('Menyiapkan aplikasi...', { duration: 2500 });
            else localStorage.setItem(INSTALL_DISMISS_KEY, Date.now().toString());
            deferredPrompt = null;
            dismissInstallBanner();
        });
    }
    function dismissInstallBanner() {
        const el = d.getElementById('noc-install-banner');
        if (!el) return;
        el.classList.add('leaving');
        setTimeout(() => el.remove(), 260);
    }

    // ── iOS install hint (Safari tidak support beforeinstallprompt) ──
    (function iosHint() {
        const ua = w.navigator.userAgent;
        const isIOS = /iPhone|iPad|iPod/i.test(ua) && !w.MSStream;
        const isSafari = /Safari/i.test(ua) && !/CriOS|FxiOS|EdgiOS/i.test(ua);
        if (!isIOS || !isSafari || isStandalone()) return;
        const dismissed = parseInt(localStorage.getItem(INSTALL_DISMISS_KEY) || '0', 10);
        if (dismissed && (Date.now() - dismissed) < 7 * 24 * 3600 * 1000) return;
        setTimeout(() => {
            N.info('Untuk pasang: tap <i class="fa-solid fa-arrow-up-from-bracket"></i> lalu "Add to Home Screen"',
                { title: 'PASANG DI iOS', duration: 8000, htmlBody: true });
            localStorage.setItem(INSTALL_DISMISS_KEY, Date.now().toString());
        }, 5000);
    })();

    // ── OFFLINE / ONLINE DETECTION ──
    let wasOffline = !navigator.onLine;
    w.addEventListener('online', () => {
        if (!wasOffline) return;
        wasOffline = false;
        N.success('Koneksi kembali online — data akan disinkronkan', { title: 'ONLINE', duration: 3500 });
    });
    w.addEventListener('offline', () => {
        wasOffline = true;
        N.warning('Anda offline — data terakhir yang di-cache masih ditampilkan', { title: 'OFFLINE MODE', duration: 5000 });
    });

    // ── SW UPDATE NOTIFICATION ──
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            // SW baru take control — bisa refresh untuk load asset baru
            if (!w.__nocSwUpdatePrompted) {
                w.__nocSwUpdatePrompted = true;
                N.confirm('Versi baru NOC tersedia. Reload sekarang?', {
                    title: 'UPDATE TERSEDIA', confirmText: 'RELOAD', cancelText: 'NANTI',
                }).then(ok => { if (ok) w.location.reload(); });
            }
        });
        navigator.serviceWorker.getRegistration().then(reg => {
            if (!reg) return;
            reg.addEventListener('updatefound', () => {
                const nw = reg.installing;
                if (!nw) return;
                nw.addEventListener('statechange', () => {
                    if (nw.state === 'installed' && navigator.serviceWorker.controller) {
                        // SW baru siap tapi belum aktif — user akan diprompt via controllerchange
                    }
                });
            });
        }).catch(() => {});
    }

    // ── VIBRATE ON ALARM (mobile) ──
    // Public API: NocPwa.alarmVibrate()
    function alarmVibrate() {
        try {
            if ('vibrate' in navigator) navigator.vibrate([200, 100, 200, 100, 400]);
        } catch (e) {}
    }

    // ── WAKE LOCK (jaga layar nyala saat monitoring) ──
    let wakeLock = null;
    async function requestWakeLock() {
        if (!('wakeLock' in navigator)) return false;
        try {
            wakeLock = await navigator.wakeLock.request('screen');
            wakeLock.addEventListener('release', () => { wakeLock = null; });
            return true;
        } catch (e) { return false; }
    }
    function releaseWakeLock() {
        if (wakeLock) { wakeLock.release().catch(() => {}); wakeLock = null; }
    }
    d.addEventListener('visibilitychange', () => {
        if (d.visibilityState === 'visible' && wakeLock === null && d.documentElement.classList.contains('noc-monitor-on')) {
            requestWakeLock();
        }
    });

    // ── SHARE ── (Web Share API — untuk share PDF/link ke WA dsb)
    async function shareUrl(title, text, url) {
        if (!navigator.share) {
            // Fallback: copy ke clipboard
            try { await navigator.clipboard.writeText(url); N.info('URL disalin ke clipboard'); }
            catch { N.error('Browser tidak mendukung share'); }
            return;
        }
        try {
            await navigator.share({ title, text, url });
        } catch (e) {
            if (e.name !== 'AbortError') N.error('Gagal share: ' + e.message);
        }
    }

    // ── STYLE INJECTION ──
    if (!d.getElementById('noc-pwa-css')) {
        const style = d.createElement('style');
        style.id = 'noc-pwa-css';
        style.textContent = `
        #noc-install-banner{
            position:fixed;bottom:max(16px,env(safe-area-inset-bottom));left:16px;right:16px;z-index:9998;
            background:rgba(10,15,25,.94);backdrop-filter:blur(16px);
            border:1px solid var(--border,rgba(0,243,255,.25));border-left:3px solid var(--cyan,#00f3ff);
            border-radius:12px;padding:12px 14px;color:var(--text-vibrant,#e6f1f8);
            font-family:'Rajdhani',sans-serif;
            box-shadow:0 12px 32px rgba(0,0,0,.6),0 0 20px rgba(0,243,255,.1);
            transform:translateY(120%);transition:transform .28s cubic-bezier(.2,.9,.3,1.2);
            max-width:520px;margin:0 auto;
        }
        #noc-install-banner.visible{transform:translateY(0)}
        #noc-install-banner.leaving{transform:translateY(120%);opacity:0}
        #noc-install-banner .npi-content{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
        #noc-install-banner .npi-icon{
            width:38px;height:38px;flex-shrink:0;border-radius:8px;
            background:linear-gradient(135deg,rgba(0,243,255,.2),rgba(0,243,255,.05));
            border:1px solid var(--cyan,#00f3ff);display:flex;align-items:center;justify-content:center;
            color:var(--cyan,#00f3ff);font-size:1.1rem;
        }
        #noc-install-banner .npi-text{flex:1;min-width:0;line-height:1.3}
        #noc-install-banner .npi-text strong{
            display:block;font-family:'Orbitron',sans-serif;font-size:.72rem;letter-spacing:1.5px;
            text-transform:uppercase;color:var(--cyan,#00f3ff);margin-bottom:2px;
        }
        #noc-install-banner .npi-text span{font-size:.75rem;color:var(--text-dim,#a0aec0)}
        #noc-install-banner .npi-actions{display:flex;gap:6px;margin-left:auto}
        #noc-install-banner .npi-btn{
            padding:8px 14px;border-radius:6px;cursor:pointer;font-family:'Orbitron',sans-serif;
            font-weight:700;font-size:.65rem;letter-spacing:1.5px;text-transform:uppercase;border:1px solid;
            display:inline-flex;align-items:center;gap:6px;transition:all .15s;
        }
        #noc-install-banner .npi-cancel{background:transparent;border-color:var(--border,rgba(0,243,255,.2));color:var(--text-dim,#708090)}
        #noc-install-banner .npi-cancel:hover{border-color:var(--cyan,#00f3ff);color:var(--cyan,#00f3ff)}
        #noc-install-banner .npi-ok{background:linear-gradient(135deg,rgba(0,243,255,.25),rgba(0,243,255,.08));border-color:var(--cyan,#00f3ff);color:var(--cyan,#00f3ff)}
        #noc-install-banner .npi-ok:hover{background:rgba(0,243,255,.35);box-shadow:0 0 12px rgba(0,243,255,.4)}
        @media (max-width:480px){
            #noc-install-banner .npi-text span{display:none}
            #noc-install-banner .npi-btn{padding:7px 10px;font-size:.6rem}
        }

        /* Show-only-in-PWA / show-only-in-web helpers */
        .is-pwa .only-web{display:none!important}
        html:not(.is-pwa) .only-pwa{display:none!important}
        `;
        d.head.appendChild(style);
    }

    // ── PUBLIC API ──
    w.NocPwa = {
        isStandalone,
        alarmVibrate,
        requestWakeLock,
        releaseWakeLock,
        share: shareUrl,
        dismissInstallBanner,
    };
})(window, document);
