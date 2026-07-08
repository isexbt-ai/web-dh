/**
 * Service Worker - 离线缓存静态资源
 * 使用 Cache First 策略，提升二次访问速度
 */

const CACHE_NAME = 'beauty-nav-v1';
const STATIC_CACHE = 'beauty-nav-static-v1';
const IMAGE_CACHE = 'beauty-nav-images-v1';

// 需要预缓存的静态资源
const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/assets/css/style.min.css',
    '/assets/js/main.min.js',
    '/detail.php',
    '/guestbook.php',
    '/showcase.php',
    '/sitemap.php'
];

// 安装时预缓存静态资源
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.log('[SW] 部分资源预缓存失败:', err);
            });
        }).then(() => {
            return self.skipWaiting();
        })
    );
});

// 激活时清理旧缓存
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== STATIC_CACHE && cacheName !== IMAGE_CACHE) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// 拦截请求并使用缓存策略
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // 跳过非GET请求
    if (request.method !== 'GET') {
        return;
    }

    // 跳过Chrome扩展请求
    if (url.protocol === 'chrome-extension:') {
        return;
    }

    // 图片资源使用 Stale While Revalidate 策略
    if (request.destination === 'image') {
        event.respondWith(handleImageRequest(request));
        return;
    }

    // CSS/JS 资源使用 Cache First 策略
    if (request.destination === 'style' || request.destination === 'script') {
        event.respondWith(handleStaticRequest(request));
        return;
    }

    // 页面请求使用 Network First 策略（保证内容最新）
    if (request.destination === 'document') {
        event.respondWith(handlePageRequest(request));
        return;
    }

    // API 请求直接走网络
    if (url.pathname.includes('/admin/api/')) {
        return;
    }

    // 其他请求使用 Cache First
    event.respondWith(handleStaticRequest(request));
});

// Cache First 策略：优先从缓存获取
async function handleStaticRequest(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    if (cached) {
        // 后台更新缓存
        fetch(request).then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
        }).catch(() => {});
        return cached;
    }

    // 缓存未命中，从网络获取
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.log('[SW] 网络请求失败:', error);
        return new Response('Offline', { status: 503 });
    }
}

// Network First 策略：优先从网络获取
async function handlePageRequest(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cache = await caches.open(STATIC_CACHE);
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }
        return new Response('Offline', { status: 503 });
    }
}

// Stale While Revalidate 策略：图片资源
async function handleImageRequest(request) {
    const cache = await caches.open(IMAGE_CACHE);
    const cached = await cache.match(request);

    // 后台更新缓存
    const fetchPromise = fetch(request).then((response) => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => {
        // 网络失败，返回缓存
        return cached;
    });

    return cached || fetchPromise;
}
