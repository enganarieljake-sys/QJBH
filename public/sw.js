// Queen Jackie service worker: makes the app installable and shows a
// friendly page when offline. Pages with tenant data are never cached.
const CACHE = 'queen-jackie-v4';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png', '/images/queen-jackie-logo.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Pages: always go to the network; fall back to the offline page.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Build assets have a hash in the filename, so a cached copy never goes stale.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(caches.match(request).then((cached) => cached || fetchAndCache(request)));
        return;
    }

    // Images and icons keep their filename when replaced: show the cached copy
    // right away, then refresh it in the background for next time.
    if (url.pathname.startsWith('/icons/') || url.pathname.startsWith('/images/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fresh = fetchAndCache(request);
                if (cached) {
                    event.waitUntil(fresh.catch(() => {}));
                    return cached;
                }
                return fresh;
            })
        );
    }
});

// Web Push is additive to the existing offline/cache behavior above.
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch {
        data = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(self.registration.showNotification(data.title || 'Queen Jackie Boarding House', {
        body: data.body || 'You have a new update.',
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/icon-192.png',
        tag: data.tag || 'qjbh-update',
        timestamp: data.timestamp || Date.now(),
        data: { url: data.url || '/notifications' },
        renotify: true,
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/notifications', self.location.origin).href;

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async (clients) => {
        for (const client of clients) {
            if (new URL(client.url).origin === self.location.origin) {
                await client.focus();
                if ('navigate' in client) await client.navigate(target);
                return;
            }
        }
        return self.clients.openWindow(target);
    }));
});

function fetchAndCache(request) {
    return fetch(request).then((response) => {
        if (response.ok) {
            const copy = response.clone();
            caches.open(CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
    });
}
