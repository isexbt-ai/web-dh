<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Bootstrap;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * 路由 smoke 测试：所有前台页面必须返回 200，后台页面未登录必须 302。
 *
 * 这是重构期间最有效的回归防护 —— 任何路由被误删或中间件顺序被破坏，
 * 这里都会立刻失败。
 */
final class RouteSmokeTest extends TestCase
{
    private \Slim\App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = Bootstrap::create();
    }

    private function request(string $method, string $path): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path);
        return $this->app->handle($request);
    }

    /** 前台页面：全部 200。 */
    public function testPublicPagesReturn200(): void
    {
        $paths = [
            '/',
            '/articles',
            '/guestbook',
            '/showcase',
            '/sitemap.xml',
            '/robots.txt',
            '/feed.xml',
            '/sw.js',
            '/api/cards',
            '/api/search?q=test',
        ];
        foreach ($paths as $path) {
            $response = $this->request('GET', $path);
            self::assertSame(200, $response->getStatusCode(), "{$path} 应返回 200");
        }
    }

    /** 旧 .php URL 必须 301 重定向（保留 SEO 权重）。 */
    public function testLegacyPhpUrlsRedirect(): void
    {
        $paths = ['/articles.php', '/guestbook.php', '/showcase.php', '/sitemap.php', '/feed.php'];
        foreach ($paths as $path) {
            $response = $this->request('GET', $path);
            self::assertSame(301, $response->getStatusCode(), "{$path} 应 301");
            self::assertNotSame('', $response->getHeaderLine('Location'));
        }
    }

    /** 后台页面：未登录 302 跳登录页。 */
    public function testAdminPagesRedirectWhenNotLoggedIn(): void
    {
        $paths = ['', '/config', '/ads', '/notices', '/articles', '/categories', '/cards', '/links', '/showcase', '/messages', '/password'];
        foreach ($paths as $path) {
            $response = $this->request('GET', '/admin' . $path);
            self::assertSame(302, $response->getStatusCode(), "/admin{$path} 未登录应 302");
            self::assertStringContainsString('/admin/login', $response->getHeaderLine('Location'));
        }
    }

    /** 登录页本身可访问。 */
    public function testAdminLoginPageIsAccessible(): void
    {
        $response = $this->request('GET', '/admin/login');
        self::assertSame(200, $response->getStatusCode());
    }

    /** 后台写接口未登录必须被拦截。 */
    public function testAdminWriteEndpointsAreProtected(): void
    {
        $response = $this->request('POST', '/admin/api/save');
        self::assertSame(302, $response->getStatusCode());

        $response = $this->request('POST', '/admin/api/upload?type=background');
        self::assertSame(302, $response->getStatusCode());
    }

    /** 不存在的路径返回 404。 */
    public function testUnknownPathReturns404(): void
    {
        $response = $this->request('GET', '/definitely-not-a-real-path');
        self::assertSame(404, $response->getStatusCode());
    }

    /** 首页必须包含关键结构：吸顶头部、分类导航、搜索入口。 */
    public function testHomePageContainsKeyStructure(): void
    {
        $response = $this->request('GET', '/');
        $html = (string) $response->getBody();

        self::assertStringContainsString('class="home-header"', $html);
        self::assertStringContainsString('id="homeCats"', $html);
        self::assertStringContainsString('id="homeSearchBtn"', $html);
        self::assertStringContainsString('id="searchOverlay"', $html);
        self::assertStringContainsString('class="category-block"', $html);
        // SEO：h1 + JSON-LD + canonical
        self::assertStringContainsString('<h1', $html);
        self::assertStringContainsString('application/ld+json', $html);
        self::assertStringContainsString('rel="canonical"', $html);
    }

    /** 所有前台页面都应带搜索面板（任意页面可唤起全站搜索）。 */
    public function testSearchPanelIsAvailableOnEveryPage(): void
    {
        $paths = ['/', '/articles', '/guestbook', '/showcase'];
        foreach ($paths as $path) {
            $html = (string) $this->request('GET', $path)->getBody();
            self::assertStringContainsString('id="searchOverlay"', $html, "{$path} 应包含搜索面板");
            self::assertStringContainsString('id="searchInput"', $html, "{$path} 应包含搜索输入框");
        }
    }
}
