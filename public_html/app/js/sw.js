// A "No-op" service worker is enough to make the app installable
self.addEventListener('fetch', (event) => {
    // This is a minimal service worker that doesn't intercept requests
    // but satisfies the PWA requirement for installability.
});
