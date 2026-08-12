<?php

declare(strict_types=1);

use App\Controller\Admin\AdController as AdminAdController;
use App\Controller\Admin\ApiController as AdminApiController;
use App\Controller\Admin\ArticleController as AdminArticleController;
use App\Controller\Admin\AuthController;
use App\Controller\Admin\CardController as AdminCardController;
use App\Controller\Admin\CategoryController as AdminCategoryController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\LinkController as AdminLinkController;
use App\Controller\Admin\MessageController as AdminMessageController;
use App\Controller\Admin\NoticeController as AdminNoticeController;
use App\Controller\Admin\SettingController;
use App\Controller\Admin\ShowcaseController as AdminShowcaseController;
use App\Controller\ApiController;
use App\Controller\ArticleController;
use App\Controller\CardController;
use App\Controller\CategoryController;
use App\Controller\FeedController;
use App\Controller\GuestbookController;
use App\Controller\HomeController;
use App\Controller\RobotsController;
use App\Controller\ShowcaseController;
use App\Controller\SitemapController;
use App\Controller\SwController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Model\Article;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    // ===== 前台 =====
    $app->get('/', [HomeController::class, 'index'])->setName('home');
    $app->get('/card/{id}.html', [CardController::class, 'show'])->setName('card.show');
    $app->get('/category/{id}.html', [CategoryController::class, 'index'])->setName('category');
    $app->get('/articles', [ArticleController::class, 'index'])->setName('articles');
    $app->get('/article/{id}-{slug}.html', [ArticleController::class, 'show'])->setName('article.show');
    $app->get('/guestbook', [GuestbookController::class, 'index'])->setName('guestbook');
    $app->get('/showcase', [ShowcaseController::class, 'index'])->setName('showcase');
    $app->get('/showcase/{id}.html', [ShowcaseController::class, 'gallery'])->setName('showcase.gallery');

    // ===== SEO 静态文件 =====
    $app->get('/sitemap.xml', [SitemapController::class, 'index']);
    $app->get('/robots.txt', [RobotsController::class, 'index']);
    $app->get('/feed.xml', [FeedController::class, 'index']);
    $app->get('/sw.js', [SwController::class, 'index']);

    // ===== 旧 URL 301 重定向（保留权重）=====
    $app->get('/detail.php', function (Request $req, Response $res) use ($app): Response {
        $id = (int) ($req->getQueryParams()['id'] ?? 0);
        return $res->withStatus(301)->withHeader('Location', $id > 0 ? "/card/$id.html" : '/');
    });
    $app->get('/article.php', function (Request $req, Response $res) use ($app): Response {
        $id = (int) ($req->getQueryParams()['id'] ?? 0);
        if ($id > 0) {
            $article = $app->getContainer()->get(Article::class)->find($id, true);
            if ($article !== null) {
                return $res->withStatus(301)->withHeader('Location', '/article/' . $id . '-' . $article['slug'] . '.html');
            }
        }
        return $res->withStatus(301)->withHeader('Location', '/articles');
    });
    $app->get('/articles.php', fn (Request $req, Response $res): Response => $res->withStatus(301)->withHeader('Location', '/articles'));
    $app->get('/guestbook.php', fn (Request $req, Response $res): Response => $res->withStatus(301)->withHeader('Location', '/guestbook'));
    $app->get('/showcase.php', fn (Request $req, Response $res): Response => $res->withStatus(301)->withHeader('Location', '/showcase'));
    $app->get('/sitemap.php', fn (Request $req, Response $res): Response => $res->withStatus(301)->withHeader('Location', '/sitemap.xml'));
    $app->get('/feed.php', fn (Request $req, Response $res): Response => $res->withStatus(301)->withHeader('Location', '/feed.xml'));

    // ===== 公开 API =====
    $app->get('/api/cards', [ApiController::class, 'cards']);
    $app->post('/api/click', [ApiController::class, 'click']);
    $app->map(['GET', 'POST'], '/api/messages', [ApiController::class, 'messages']);

    // ===== 后台（Auth 外层 + Csrf 内层，login 放行）=====
    $app->group('/admin', function (\Slim\Routing\RouteCollectorProxy $app): void {
        $app->get('/login', [AuthController::class, 'loginForm']);
        $app->post('/login', [AuthController::class, 'login']);
        $app->post('/logout', [AuthController::class, 'logout']);

        $app->get('', [DashboardController::class, 'index'])->setName('admin.dashboard');
        $app->get('/config', [SettingController::class, 'index']);
        $app->get('/ads', [AdminAdController::class, 'index']);
        $app->get('/notices', [AdminNoticeController::class, 'index']);
        $app->get('/articles', [AdminArticleController::class, 'index']);
        $app->get('/categories', [AdminCategoryController::class, 'index']);
        $app->get('/cards', [AdminCardController::class, 'index']);
        $app->get('/links', [AdminLinkController::class, 'index']);
        $app->get('/showcase', [AdminShowcaseController::class, 'index']);
        $app->get('/messages', [AdminMessageController::class, 'index']);
        $app->get('/password', [AuthController::class, 'passwordForm']);
        $app->post('/password', [AuthController::class, 'password']);

        // 后台 API
        $app->post('/api/save', [AdminApiController::class, 'save']);
        $app->post('/api/delete', [AdminApiController::class, 'delete']);
        $app->post('/api/upload', [AdminApiController::class, 'upload']);
        $app->post('/api/ip_query', [AdminApiController::class, 'ipQuery']);
    })->add(new CsrfMiddleware())->add(new AuthMiddleware());
};
