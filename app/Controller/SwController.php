<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Service Worker 生成器：读 manifest.json 动态内联预缓存清单（只含实际资源），
 * CSS/JS 用 Stale-While-Revalidate，页面网络优先 + 离线回退。
 *
 * 缓存键保留 ?v=hash 版本号：资源改版后 URL 随之变化，缓存自然失效，
 * 无需等待 SW 二次安装即可让老用户拿到新样式。
 */
final class SwController
{
    public function index(Request $request, Response $response): Response
    {
        $manifestFile = public_path('assets/manifest.json');
        $precache = [];
        if (is_file($manifestFile)) {
            $data = json_decode((string) file_get_contents($manifestFile), true);
            if (is_array($data)) {
                // 保留 ?v=hash：缓存键与页面引用完全一致，改版即失效
                $precache = array_values($data);
            }
        }
        $precacheJson = (string) json_encode($precache, JSON_UNESCAPED_SLASHES);

        $js = <<<JS
// 美女导航 Service Worker（预缓存清单由 PHP 内联生成）
self.__PRECACHE = {$precacheJson};
var CACHE = 'nav-v2';

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE)
            .then(function (cache) { return cache.addAll(self.__PRECACHE); })
            .then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); }));
        }).then(function () { return self.clients.claim(); })
    );
});

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') return;
    var url = new URL(req.url);
    if (url.origin !== self.location.origin) return;
    var fullUrl = url.href;                 // 含 ?v=hash，资源版本变化即换缓存键
    var cleanUrl = url.origin + url.pathname;

    // 静态资源：Stale-While-Revalidate（命中先返回缓存，后台更新）
    var isAsset = self.__PRECACHE.indexOf(fullUrl) !== -1 || /\\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|woff2?)$/.test(url.pathname);
    if (isAsset) {
        event.respondWith(
            caches.match(fullUrl).then(function (cached) {
                var fetchPromise = fetch(req).then(function (res) {
                    if (res && res.ok) {
                        var copy = res.clone();
                        caches.open(CACHE).then(function (c) { return c.put(fullUrl, copy); });
                    }
                    return res;
                }).catch(function () { return cached; });
                return cached || fetchPromise;
            })
        );
        return;
    }

    // 页面：网络优先，离线回退缓存
    event.respondWith(
        fetch(req).then(function (res) {
            if (res && res.ok && res.type === 'basic') {
                var copy = res.clone();
                caches.open(CACHE).then(function (c) { return c.put(cleanUrl, copy); });
            }
            return res;
        }).catch(function () {
            return caches.match(cleanUrl).then(function (c) { return c || caches.match('/'); });
        })
    );
});
JS;

        $response->getBody()->write($js);
        return $response->withHeader('Content-Type', 'application/javascript; charset=utf-8');
    }
}
