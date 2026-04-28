const CACHE_NAME = 'radius-isp-v1';
const ASSETS_TO_CACHE = [
    '/client',
    '/offline.html',
    // We can't cache the dynamically generated css/js easily without build tools injection,
    // so we will rely on network-first strategy for most things.
];

// Install Event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(ASSETS_TO_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
});

// Fetch Event
self.addEventListener('fetch', event => {
    // We only handle GET requests
    if (event.request.method !== 'GET') return;

    // Network First Strategy
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // If it's a valid response, clone it and put it in cache
                if (response && response.status === 200 && response.type === 'basic') {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                }
                return response;
            })
            .catch(() => {
                // If network fails, try to return from cache
                return caches.match(event.request)
                    .then(response => {
                        if (response) {
                            return response;
                        }
                        // If not in cache and it's a navigation request, show offline page (if we had one)
                        // Or just return a simple offline message
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline.html') || new Response('Anda sedang offline. Silakan periksa koneksi internet Anda.', {
                                headers: { 'Content-Type': 'text/html' }
                            });
                        }
                    });
            })
    );
});
