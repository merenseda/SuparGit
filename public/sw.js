const CACHE_NAME = 'gitpush-panel-v1';
const urlsToCache = [
  '/',
  '/dashboard',
  '/push',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(urlsToCache))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(cacheNames.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n)))
    )
  );
  self.clients.claim();
});

// Push notification alma (Arka plan bildirimleri)
self.addEventListener('push', (event) => {
  let data = { title: 'GitPush Panel', body: 'Yeni bir push işlemi yapıldı.', icon: '/icon-192x192.png' };
  if (event.data) {
    try { 
      data = event.data.json(); 
    } catch(e) { 
      data.body = event.data.text(); 
    }
  }
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'GitPush Panel', {
      body: data.body,
      icon: data.icon || '/icon-192x192.png',
      badge: '/icon-192x192.png',
      data: data.data || {},
      vibrate: [200, 100, 200],
    })
  );
});

// Bildirime tıklanması
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/push/logs';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (let client of windowClients) {
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});
