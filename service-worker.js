const CACHE_NAME = 'vilela-links-cleanup-v7';

self.addEventListener('install', (event) => {
    // Force immediate activation
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Delete ALL caches associated with this scope
    event.waitUntil(
        caches.keys().then((keyList) => {
            return Promise.all(keyList.map((key) => {
                console.log('Service Worker: Removing old cache', key);
                return caches.delete(key);
            }));
        })
    );
    // Take control of all clients immediately
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    // Network Only - By-pass cache completely
    // This ensures we never serve stale content
    event.respondWith(fetch(event.request));
});
