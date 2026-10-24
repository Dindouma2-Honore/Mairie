/* Mairie360 — service worker : l'application continue de s'ouvrir sans connexion. */
const VERSION = 'm360-v2';
const SHELL = VERSION + '-shell';
const PAGES = VERSION + '-pages';
const SHELL_URLS = ['/css/app.css', '/js/app.js', '/manifest.webmanifest', '/icons/icon.svg', '/hors-ligne'];
// Jamais mis en cache : jetons, synchronisation, pages publiques et d'authentification.
const NEVER = [/^\/csrf/, /^\/sync$/, /^\/offline\//, /^\/connexion/, /^\/deconnexion/, /^\/verifier\//];

self.addEventListener('install', (e) => {
  e.waitUntil((async () => {
    const c = await caches.open(SHELL);
    await Promise.allSettled(SHELL_URLS.map((u) => c.add(u)));
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (e) => {
  e.waitUntil((async () => {
    for (const k of await caches.keys()) if (!k.startsWith(VERSION)) await caches.delete(k);
    await self.clients.claim();
  })());
});

const cacheable = (res) => res.ok && !res.redirected && (res.headers.get('content-type') || '').includes('text/html');

async function networkFirst(request) {
  const cache = await caches.open(PAGES);
  const ctl = new AbortController(); const timer = setTimeout(() => ctl.abort(), 8000);
  try {
    const res = await fetch(request, { signal: ctl.signal });
    if (cacheable(res)) cache.put(request, res.clone());
    return res;
  } catch (_) {
    const url = new URL(request.url);
    const hit = (await cache.match(request)) || (await cache.match(url.pathname));
    if (hit) return hit;
    return (await caches.match('/hors-ligne')) || Response.error();
  } finally { clearTimeout(timer); }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(SHELL);
  const hit = await cache.match(request);
  const fresh = fetch(request).then((res) => { if (res.ok) cache.put(request, res.clone()); return res; }).catch(() => null);
  return hit || (await fresh) || Response.error();
}

self.addEventListener('fetch', (e) => {
  const r = e.request; const url = new URL(r.url);
  if (r.method !== 'GET' || url.origin !== location.origin || NEVER.some((p) => p.test(url.pathname))) return;
  if (r.mode === 'navigate') { e.respondWith(networkFirst(r)); return; }
  if (/^\/(css|js|icons)\//.test(url.pathname) || url.pathname === '/manifest.webmanifest') e.respondWith(staleWhileRevalidate(r));
});

self.addEventListener('message', (e) => {
  const d = e.data || {};
  if (d.type === 'clear') e.waitUntil(caches.delete(PAGES)); // déconnexion : on ne garde aucune page de l'utilisateur
  if (d.type === 'prime' && Array.isArray(d.urls)) {
    e.waitUntil((async () => {
      const cache = await caches.open(PAGES);
      await Promise.allSettled(d.urls.map(async (u) => {
        const res = await fetch(u, { credentials: 'same-origin' });
        if (cacheable(res)) await cache.put(u, res);
      }));
    })());
  }
});
