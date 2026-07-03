/* Neurix POS — Service Worker v1.0
   Propósito: habilitar la instalación como PWA.
   Estrategia: network-first para no interferir con páginas PHP dinámicas.
   Solo cachea assets estáticos (iconos, manifest). */

const CACHE = 'neurix-pos-v1.1';
const STATIC_ASSETS = [
    './themes/default/assets/images/icon.png',
    './manifest.json'
];

self.addEventListener('install', function(e) {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE).then(function(cache) {
            return cache.addAll(STATIC_ASSETS);
        })
    );
});

self.addEventListener('activate', function(e) {
    e.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(
                keys.filter(function(k) { return k !== CACHE; })
                    .map(function(k) { return caches.delete(k); })
            );
        }).then(function() { return self.clients.claim(); })
    );
});

self.addEventListener('fetch', function(e) {
    var req = e.request;

    /* Solo intercepta GET; deja pasar POST (formularios PHP). */
    if (req.method !== 'GET') return;

    /* Imágenes y fuentes (inmutables): cache-first */
    if (req.url.match(/\.(png|jpg|jpeg|gif|svg|ico|webp|woff2?)(\?.*)?$/)) {
        e.respondWith(
            caches.match(req).then(function(cached) {
                return cached || fetch(req).then(function(res) {
                    var clone = res.clone();
                    caches.open(CACHE).then(function(c) { c.put(req, clone); });
                    return res;
                });
            })
        );
        return;
    }

    /* CSS/JS: network-first — cache-first dejaba a los usuarios con bundles
       viejos tras cada build; el caché queda solo como fallback offline */
    if (req.url.match(/\.(css|js)(\?.*)?$/)) {
        e.respondWith(
            fetch(req).then(function(res) {
                var clone = res.clone();
                caches.open(CACHE).then(function(c) { c.put(req, clone); });
                return res;
            }).catch(function() {
                return caches.match(req);
            })
        );
        return;
    }

    /* Todo lo demás: network-first (páginas PHP, AJAX, etc.) */
    e.respondWith(
        fetch(req).catch(function() {
            return caches.match(req);
        })
    );
});
