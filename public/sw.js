const CACHE = 'noc-v1';
const SHELL = ['/', '/manifest.webmanifest', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).catch(() => {}));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  // Data sensor/CCTV harus selalu fresh -> jangan di-cache.
  if (url.pathname.startsWith('/api/')) return;

  // Navigasi halaman: network-first, fallback ke cache saat offline.
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

  // Aset statis: cache-first.
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
