/* Mall Intelligence Center — Service Worker
 * Strategi konservatif agar tidak mengganggu app dinamis (CI4):
 *  - Navigasi (HTML)  : selalu network; HTML tidak di-cache (data per-user/sensitif),
 *                       fallback ke halaman offline hanya saat tidak ada koneksi.
 *  - Aset statis lokal: stale-while-revalidate (css/js/img/font milik origin sendiri).
 *  - Selain GET        : selalu lewat jaringan, tidak pernah di-cache.
 */
const VERSION      = 'mic-v2.21.0';   // samakan dengan versi rilis agar cache lama otomatis dibersihkan
const STATIC_CACHE = `${VERSION}-static`;

// Path relatif terhadap lokasi sw.js (folder public/).
const OFFLINE_URL = 'offline.html';
const PRECACHE = [
  OFFLINE_URL,
  'img/icon-192.png',
  'img/mic-logo-sm.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') self.skipWaiting();
});

function isStaticAsset(url) {
  return /\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf)$/i.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Hanya tangani GET same-origin. Sisanya (POST/login/logout/CDN) lewat normal.
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Navigasi halaman → selalu network, JANGAN cache HTML (data per-user & sensitif).
  // Saat offline, tampilkan halaman offline — bukan salinan halaman lama.
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  // Aset statis lokal → stale-while-revalidate.
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.open(STATIC_CACHE).then((cache) =>
        cache.match(req).then((cached) => {
          const network = fetch(req)
            .then((res) => { if (res && res.status === 200) cache.put(req, res.clone()); return res; })
            .catch(() => cached);
          return cached || network;
        })
      )
    );
  }
});
