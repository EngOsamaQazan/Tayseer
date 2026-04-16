/**
 * نظام تيسير — Service Worker (PWA)
 * يخزّن هيكل التطبيق مؤقتاً لتجربة تعمل بدون اتصال.
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

// عند التثبيت: تخزين ملفات الهيكل الأساسي
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(SHELL_URLS);
    })
  );
  self.skipWaiting();
});

// عند التفعيل: حذف النسخ القديمة من الكاش
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

// عند جلب الطلبات: الشبكة أولاً لـ API/HTML، الكاش أولاً للملفات الثابتة
self.addEventListener('fetch', function(event) {
  var req = event.request;
  var url = new URL(req.url);

  // تجاهل الطلبات غير GET
  if (req.method !== 'GET') return;
  // تجاهل البروتوكولات غير المدعومة (مثل chrome-extension://)
  if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

  if (req.url.includes('/api/') || req.headers.get('accept').includes('text/html')) {
    // الشبكة أولاً: جلب من السيرفر، وتخزين نسخة في الكاش
    event.respondWith(
      fetch(req).then(function(res) {
        if (res.ok) {
          var clone = res.clone();
          caches.open(CACHE_NAME).then(function(c) { c.put(req, clone); });
        }
        return res;
      }).catch(function() {
        // في حال عدم الاتصال: إرجاع النسخة المخزّنة
        return caches.match(req);
      })
    );
  } else {
    // الكاش أولاً: إرجاع النسخة المخزّنة، وإلا جلب من الشبكة
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
