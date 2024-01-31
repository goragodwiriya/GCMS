(function() {
  'use strict';
  const cacheName = '1.2.2',
    offlinePage = '/',
    filesToCache = [
      '/',
      '/index.php/css/view/index',
      '/index.php/js/view/index',
      '/skin/fonts/icomoon.ttf',
      '/skin/fonts/icomoon.eot#iefix',
      '/skin/fonts/icomoon.woff',
      '/skin/fonts/icomoon.svg#icomoon',
      '/ckeditor/ckeditor.js'
    ];

  self.addEventListener('install', function(e) {
    e.waitUntil(
      caches.open(cacheName).then(
        function(cache) {
          filesToCache.map(function(url) {
            return cache.add(url);
          });
        }
      )
    );
  });

  self.addEventListener('fetch', function(e) {
    if (!e.request.url.match(/^(http|https):\/\//i)) {
      return;
    }
    if (new URL(e.request.url).origin !== location.origin) {
      return;
    }
    if (e.request.method !== 'GET') {
      e.respondWith(fetch(e.request).catch(function() {
        return caches.match(offlinePage);
      }));
      return;
    }
    if (e.request.mode === 'navigate' && navigator.onLine) {
      e.respondWith(fetch(e.request).then(
        function(response) {
          return caches.open(cacheName).then(
            function(cache) {
              cache.put(e.request, response.clone());
              return response;
            }
          );
        }
      ));
      return;
    }
    e.respondWith(
      caches.match(e.request).then(
        function(response) {
          return response || fetch(e.request).then(
            function(response) {
              return caches.open(cacheName).then(
                function(cache) {
                  cache.put(e.request, response.clone());
                  return response;
                }
              );
            }
          );
        }
      ).catch(
        function() {
          return caches.match(offlinePage);
        }
      )
    );
  });
}(this));
