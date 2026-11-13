/**
 * sw.js - Service Worker for Now.js / GCMS
 *
 * Handles caching strategies, offline support, push notifications,
 * and message-based configuration from ServiceWorkerManager.
 */

'use strict';

// ─── State ───────────────────────────────────────────────────────────────────

let config = {
  cacheName: 'now-js-cache-v1',
  precacheUrls: ['/', '/index.html'],
  cachePatterns: ['\\.js$', '\\.css$', '\\.html$', '\\.json$', '\\.png$', '\\.webp$', '\\.jpe?g$', '\\.svg$', '\\.woff2?$', '\\.ttf$'],
  networkFirstPatterns: [],
  // API responses are per-session (CSRF tokens, auth state): replaying one
  // from cache hands the page a token the server no longer knows -> 419.
  excludeFromCachePatterns: ['/api/'],
  strategies: {},
  push: {
    enabled: false,
    publicKey: null,
    userVisibleOnly: true
  }
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

function toRegExp(source) {
  try {
    return new RegExp(source);
  } catch (e) {
    return null;
  }
}

function matchesPatterns(url, patterns) {
  return patterns.some(p => {
    const re = typeof p === 'string' ? toRegExp(p) : p;
    return re && re.test(url);
  });
}

function getStrategy(url) {
  for (const [pattern, strategy] of Object.entries(config.strategies)) {
    const re = toRegExp(pattern);
    if (re && re.test(url)) return strategy;
  }
  // Network-first patterns
  if (matchesPatterns(url, config.networkFirstPatterns)) return 'network-first';
  // Cacheable patterns → cache-first
  if (matchesPatterns(url, config.cachePatterns)) return 'cache-first';
  // Default: network-first
  return 'network-first';
}

function log(message, level = 'log') {
  // Forward log messages to all clients
  self.clients.matchAll().then(clients => {
    clients.forEach(client => client.postMessage({type: 'LOG', payload: {message, level}}));
  });
}

// ─── Install ─────────────────────────────────────────────────────────────────

self.addEventListener('install', event => {
  log('Service Worker installing');
  event.waitUntil(
    caches.open(config.cacheName).then(cache => {
      return cache.addAll(config.precacheUrls).catch(err => {
        log(`Precache error: ${err.message}`, 'warn');
      });
    }).then(() => {
      log('Service Worker installed');
    })
  );
});

// ─── Activate ────────────────────────────────────────────────────────────────

self.addEventListener('activate', event => {
  log('Service Worker activating');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => name !== config.cacheName)
          .map(name => {
            log(`Deleting old cache: ${name}`);
            return caches.delete(name);
          })
      );
    }).then(() => {
      log('Service Worker activated');
      self.clients.matchAll({includeUncontrolled: true}).then(clients => {
        clients.forEach(client => client.postMessage({type: 'OFFLINE_READY'}));
      });
      return self.clients.claim();
    })
  );
});

// ─── Fetch ───────────────────────────────────────────────────────────────────

self.addEventListener('fetch', event => {
  const {request} = event;
  const url = request.url;

  // Only handle GET requests
  if (request.method !== 'GET') return;

  // PageNavigator asks for the JSON form of a page at the page's own URL — never
  // cache it, or an offline/cached navigation could be answered with JSON
  if (request.headers.get('X-Partial-Page')) return;

  // Skip non-http(s) requests
  if (!url.startsWith('http')) return;

  // Skip excluded patterns
  if (matchesPatterns(url, config.excludeFromCachePatterns)) return;

  const strategy = getStrategy(url);

  if (strategy === 'cache-first') {
    event.respondWith(cacheFirst(request));
  } else if (strategy === 'stale-while-revalidate') {
    event.respondWith(staleWhileRevalidate(request));
  } else {
    // network-first (default)
    event.respondWith(networkFirst(request));
  }
});

// ─── Strategies ──────────────────────────────────────────────────────────────

async function cacheFirst(request) {
  try {
    const cached = await caches.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response && response.ok) {
      const cache = await caches.open(config.cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    const cached = await caches.match(request);
    if (cached) return cached;
    return offlineFallback(request);
  }
}

async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response && response.ok) {
      const cache = await caches.open(config.cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    const cached = await caches.match(request);
    if (cached) return cached;
    return offlineFallback(request);
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(config.cacheName);
  const cached = await cache.match(request);
  const networkPromise = fetch(request).then(response => {
    if (response && response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  }).catch(() => null);
  return cached || networkPromise || offlineFallback(request);
}

function offlineFallback(request) {
  if (request.headers.get('accept')?.includes('text/html')) {
    return caches.match('/') || new Response('Offline', {status: 503, statusText: 'Service Unavailable'});
  }
  return new Response('Offline', {status: 503, statusText: 'Service Unavailable'});
}

// ─── Push Notifications ──────────────────────────────────────────────────────

self.addEventListener('push', event => {
  if (!config.push.enabled) return;
  let data = {};
  try {
    data = event.data?.json() || {};
  } catch (e) {
    data = {title: 'Notification', body: event.data?.text() || ''};
  }
  const title = data.title || 'Notification';
  const options = {
    body: data.body || '',
    icon: data.icon || '/images/icon-192.png',
    badge: data.badge || '/images/badge-72.png',
    data: data.data || {},
    tag: data.tag || 'default'
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const url = event.notification.data?.url || '/';
  event.waitUntil(
    self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(clients => {
      for (const client of clients) {
        if (client.url === url && 'focus' in client) return client.focus();
      }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});

// ─── Message Handling ────────────────────────────────────────────────────────

self.addEventListener('message', event => {
  const {type, payload} = event.data || {};
  if (!type) return;

  switch (type) {

    case 'UPDATE_CONFIG': {
      if (!payload) break;
      config = {...config, ...payload};
      event.source?.postMessage({type: 'CONFIG_UPDATED'});
      log('Config updated');
      break;
    }

    case 'SKIP_WAITING': {
      self.skipWaiting();
      break;
    }

    case 'CACHE_URLS': {
      const urls = payload?.urls;
      if (!Array.isArray(urls)) break;
      event.waitUntil(
        caches.open(config.cacheName).then(cache => {
          return Promise.all(
            urls.map(url =>
              fetch(url)
                .then(resp => {
                  if (resp && resp.ok) cache.put(url, resp);
                })
                .catch(err => log(`Failed to cache ${url}: ${err.message}`, 'warn'))
            )
          );
        }).then(() => {
          event.source?.postMessage({type: 'CACHE_UPDATED', payload: {cacheName: config.cacheName}});
        })
      );
      break;
    }

    case 'CLEAR_CACHE': {
      event.waitUntil(
        caches.delete(config.cacheName).then(() => {
          log('Cache cleared');
          event.source?.postMessage({type: 'CACHE_UPDATED', payload: {cacheName: config.cacheName}});
        })
      );
      break;
    }

    default:
      log(`Unknown message type: ${type}`, 'warn');
  }
});
