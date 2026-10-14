const CACHE_NAME = 'cmb-pwa-shell-v2';
const OFFLINE_PAGE = './offline.html';
const SHELL_FILES = [
  './offline.html',
  './assets/cmb.png',
  './assets/website_banner.png',
  './assets/website_image.png',
  './assets/favicon.svg',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png',
  './assets/css/custom.css',
  './assets/js/app.js',
  './assets/js/shop.js',
  './assets/js/product.js',
  './assets/js/checkout.js',
  './assets/js/success.js',
  './assets/js/pwa.js',
  './assets/js/theme.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(SHELL_FILES))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key.startsWith('cmb-pwa-') && key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== self.location.origin) return;
  if (/\/api\/|\/admin\//i.test(url.pathname) || /\.php$/i.test(url.pathname)) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_PAGE)));
    return;
  }

  const isImage = request.destination === 'image' && (/\/assets\//i.test(url.pathname) || /\/uploads\//i.test(url.pathname));
  const isShellAsset = /\/assets\/(css|js)\//i.test(url.pathname);
  if (!isImage && !isShellAsset) return;

  if (isImage) {
    event.respondWith(
      caches.match(request).then(cached => cached || fetch(request).then(response => {
        if (response.ok) caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
        return response;
      }))
    );
    return;
  }

  event.respondWith(
    fetch(request).then(response => {
      if (response.ok) caches.open(CACHE_NAME).then(cache => cache.put(request, response.clone()));
      return response;
    }).catch(() => caches.match(request, { ignoreSearch: true }))
  );
});