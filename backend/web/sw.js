/**
 * نظام تيسير — Service Worker (PWA)
 * يخزّن هيكل التطبيق مؤقتاً لتجربة تعمل بدون اتصال.
 */
var CACHE_NAME = 'tayseer-v2';
var SHELL_URLS = [
  '/css/tayseer-vuexy.css',
  '/css/tayseer-themes.css',
  '/css/tayseer-gridview-responsive.css',
  '/js/tayseer-responsive.js',
  '/images/favicon.png'
];

self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return Promise.all(
        SHELL_URLS.map(function(url) {
          return cache.add(url).catch(function(err) {
            console.warn('[SW] Failed to cache', url, err);
          });
        })
      );
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
    }).then(function() {
      return self.clients.claim();
    })
  );
});

function isStaticAsset(url) {
  return /\.(css|js|mjs|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|eot)(\?|$)/i.test(url.pathname);
}

function safePutInCache(req, res) {
  if (!res || !res.ok || res.type === 'opaqueredirect' || res.redirected) return;
  try {
    var clone = res.clone();
    caches.open(CACHE_NAME).then(function(c) {
      c.put(req, clone).catch(function() { /* ignore */ });
    });
  } catch (e) { /* ignore */ }
}

self.addEventListener('fetch', function(event) {
  var req = event.request;
  var url;
  try { url = new URL(req.url); } catch (e) { return; }

  if (req.method !== 'GET') return;
  if (url.protocol !== 'http:' && url.protocol !== 'https:') return;
  if (url.origin !== self.location.origin) return;

  // Never intercept navigations — let the browser handle them directly.
  // This avoids breaking redirects (e.g. login flow) and auth cookies.
  if (req.mode === 'navigate' || req.destination === 'document') return;

  var accept = req.headers.get('accept') || '';
  var isApi = url.pathname.indexOf('/api/') !== -1;
  var isHtml = accept.indexOf('text/html') !== -1;

  if (isApi || isHtml) {
    event.respondWith(
      fetch(req).then(function(res) {
        safePutInCache(req, res);
        return res;
      }).catch(function() {
        return caches.match(req).then(function(cached) {
          return cached || Response.error();
        });
      })
    );
    return;
  }

  if (!isStaticAsset(url)) return;

  event.respondWith(
    caches.match(req).then(function(cached) {
      if (cached) return cached;
      return fetch(req).then(function(res) {
        safePutInCache(req, res);
        return res;
      }).catch(function() {
        return Response.error();
      });
    })
  );
});
