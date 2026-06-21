self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = event.notification.data?.url || '/notifications';
    event.waitUntil(self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(clients => {
        const existing = clients.find(client => client.url === url);
        return existing ? existing.focus() : self.clients.openWindow(url);
    }));
});
