/* SOV PWA Service Worker (seguro para apps con sesión)
 * - Cachea SOLO assets estáticos (CSS/JS/img/fonts)
 * - Navegación: network-first para evitar pantallas desactualizadas con sesión
 */

const CACHE_NAME = 'sov-static-v1';
const STATIC_SEEDS = [
  '/sov/',
  '/sov/manifest.json',
  '/sov/public/pwa/icon-192.png',
  '/sov/public/pwa/icon-512.png',
  '/sov/public/pwa/icon-512-maskable.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(STATIC_SEEDS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((k) => (k !== CACHE_NAME ? caches.delete(k) : null))))
      .then(() => self.clients.claim())
  );
});

function isStaticAsset(request) {
  try {
    const url = new URL(request.url);
    if (!url.pathname.startsWith('/sov/')) return false;
    return /\.(css|js|png|jpg|jpeg|webp|svg|ico|woff|woff2|ttf)$/i.test(url.pathname);
  } catch (e) {
    return false;
  }
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);
  if (!url.pathname.startsWith('/sov/')) return;

  // Cache-first: assets estáticos
  if (isStaticAsset(req)) {
    event.respondWith(
      caches.match(req).then((cached) => {
        if (cached) return cached;
        return fetch(req).then((resp) => {
          const copy = resp.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy)).catch(() => {});
          return resp;
        });
      })
    );
    return;
  }

  // Network-first para navegación (evita stale UI con sesión)
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('/sov/'))
    );
  }
});
