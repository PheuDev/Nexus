/**
 * NEXUS — Service Worker
 * Cache les assets statiques pour un accès hors ligne partiel.
 */
const CACHE_NAME  = 'nexus-v1';
const CACHE_ASSETS = [
    '/nexus/public/assets/css/style.css',
    '/nexus/public/assets/js/app.js',
    '/nexus/public/assets/js/chart.umd.min.js',
];

// Installation : mise en cache des assets statiques
self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(CACHE_ASSETS))
    );
    self.skipWaiting();
});

// Activation : suppression des anciens caches
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch : stale-while-revalidate pour les assets, network-first pour les pages PHP
self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);

    // Assets statiques : cache en priorité
    if (e.request.url.includes('/assets/')) {
        e.respondWith(
            caches.match(e.request).then(cached => {
                const fresh = fetch(e.request).then(res => {
                    caches.open(CACHE_NAME).then(c => c.put(e.request, res.clone()));
                    return res;
                });
                return cached || fresh;
            })
        );
        return;
    }

});
