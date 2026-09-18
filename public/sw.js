const CACHE = 'noc-v5';   // <- bump versi -> SW lama akan diganti
const SHELL = ['/', '/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

// Host CDN yg layak di-cache (Chart.js, hls.js, FontAwesome, Google Fonts)
const CDN_HOSTS = new Set([
  'cdn.jsdelivr.net',
  'cdnjs.cloudflare.com',
  'fonts.googleapis.com',
  'fonts.gstatic.com',
]);

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).catch(() => {}));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  // 1) Data sensor live + HLS chunk -> SELALU fresh, jangan disentuh
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/cctv-hls/')) {
    return;
  }

  // 2) CDN external (Chart.js, hls.js, FontAwesome, GoogleFonts) -> cache-first
  if (CDN_HOSTS.has(url.host)) {
    e.respondWith(
      caches.match(req).then((m) =>
        m ||
        fetch(req)
          .then((r) => {
            if (r.ok) {
              const cp = r.clone();
              caches.open(CACHE).then((c) => c.put(req, cp));
            }
            return r;
          })
          .catch(() => m)
      )
    );
    return;
  }

  // 3) Navigasi halaman -> network-first, fallback cache (offline OK)
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req)
        .then((r) => {
          const cp = r.clone();
          caches.open(CACHE).then((c) => c.put(req, cp));
          return r;
        })
        .catch(() => caches.match(req).then((m) => m || caches.match('/')))
    );
    return;
  }

  // 4) Aset statis same-origin -> cache-first
  e.respondWith(
    caches.match(req).then((m) =>
      m ||
      fetch(req)
        .then((r) => {
          if (r.ok && url.origin === location.origin) {
            const cp = r.clone();
            caches.open(CACHE).then((c) => c.put(req, cp));
          }
          return r;
        })
        .catch(() => m)
    )
  );
});

// ── PUSH NOTIFICATION HANDLER ──
self.addEventListener('push', (event) => {
  let data = { title: 'NOC ALERT', body: 'Ada notifikasi baru', tag: 'noc-alert' };
  if (event.data) {
    try { data = { ...data, ...event.data.json() }; }
    catch { data.body = event.data.text(); }
  }
  const options = {
    body: data.body,
    tag:  data.tag || 'noc-alert',
    icon: data.icon || '/icons/icon-192.png',
    badge: '/icons/icon-192.png',
    vibrate: [200, 100, 200, 100, 400],
    requireInteraction: data.severity === 'danger',
    data: { url: data.url || '/', ts: Date.now() },
    actions: [
      { action: 'open',    title: 'Buka Dashboard' },
      { action: 'dismiss', title: 'Tutup' },
    ],
  };
  event.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  if (event.action === 'dismiss') return;
  const url = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((wins) => {
      // Fokus tab NOC yg sudah terbuka
      for (const w of wins) {
        if (w.url.includes(location.origin) && 'focus' in w) return w.focus();
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});

self.addEventListener('pushsubscriptionchange', (event) => {
  // Browser rotate subscription — client-side akan re-subscribe saat page open lagi
  event.waitUntil(
    self.registration.pushManager.getSubscription().then((sub) => sub || null)
  );
});
