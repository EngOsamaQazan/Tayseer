/**
 * Tayseer ERP — Service Worker (PWA)
 * Caches app shell for offline-first experience.
 */
var CACHE_NAME = 'tayseer-v1';
var SHELL_URLS = [
  '/',
  '/css/tayseer-vuexy.css',
  '/css/tayseer-themes.css',
  '/css/tayseer-gridview-responsive.css',
  '/js/tayseer-responsive.js',
  '/images/favicon.png'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(SHELL_URLS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(names) {
      return Promise.all(
        names.filter(function(n) { return n !== CACHE_NAME; })
             .map(function(n) { return caches.delete(n); })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', function(event) {
  var req = event.request;

  if (req.method !== 'GET') return;

  // Network-first for API/HTML, cache-first for static assets
  if (req.url.includes('/api/') || req.headers.get('accept').includes('text/html')) {
    event.respondWith(
      fetch(req).then(function(res) {
        if (res.ok) {
          var clone = res.clone();
          caches.open(CACHE_NAME).then(function(c) { c.put(req, clone); });
        }
        return res;
      }).catch(function() {
        return caches.match(req);
      })
    );
  } else {
    event.respondWith(
      caches.match(req).then(function(cached) {
        return cached || fetch(req).then(function(res) {
          if (res.ok) {
            var clone = res.clone();
            caches.open(CACHE_NAME).then(function(c) { c.put(req, clone); });
          }
          return res;
        });
      })
    );
  }
});
