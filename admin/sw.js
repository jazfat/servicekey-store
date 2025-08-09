// admin/sw.js - Service Worker para el Panel de Administración

const CACHE_NAME = 'servicekey-admin-cache-v1';
const urlsToCache = [
  './', // Cacha el propio directorio (admin/)
  './index.php', // El dashboard
  './admin_styles.css', // CSS específico del admin
  '../css/style.css', // CSS global del frontend (una carpeta arriba de admin/)
  // '../js/main.js',    // Descomenta si usas main.js en el admin
  './login.php', // Página de login del admin
  './manage_products.php', // Páginas de gestión
  './manage_orders.php',
  './manage_licenses.php',
  './manage_categories.php',
  './manage_coupons.php',
  './manage_slides.php',
  './manage_logos.php',
  './manage_settings.php',
  './manage_pages.php',
  './manifest.json', // El manifest
  // Rutas de iconos PWA (relativas al sw.js)
  './assets/images/android-chrome-192x192.png',
  './assets/images/android-chrome-512x512.png',
  // Otros assets que se carguen en el admin y quieras cachear
  '../assets/img/placeholder.png' // Si se usa una imagen de placeholder compartida
];


// Evento de Instalación: Se dispara cuando el Service Worker se instala.
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Cache abierto para el admin.');
        return cache.addAll(urlsToCache).then(() => {
            console.log('Service Worker: Todos los URLs han sido cacheados exitosamente.');
        }).catch(error => {
            console.error('Service Worker: Fallo al cachear algunos URLs:', error);
        });
      })
  );
});

// Evento Fetch: Se dispara cada vez que la página pide un recurso (CSS, JS, imagen, etc.).
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request).then(
          response => {
            if(!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            var responseToCache = response.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
            return response;
          }
        );
      }
    )
  );
});

// Evento Activate: Se dispara cuando el Service Worker se activa (ej. después de una instalación exitosa).
self.addEventListener('activate', event => {
  console.log('Service Worker: Activado para el admin.');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: Eliminando caché antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});