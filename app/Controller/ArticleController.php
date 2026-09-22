<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Article;
use App\Service\CacheService;
use App\Service\SeoService;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * 文章控制器：列表（分页 + 整页缓存）+ 详情（友好 URL /article/{id}-{slug}.html）。
 */
final class ArticleController
{
    public function __construct(
        private View $view,
        private Article $articleModel,
        private SeoService $seo,
        private CacheService $cache
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));

        $cacheKey = 'page_articles_' . $page;
        $cached = $this->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            $response->getBody()->write($cached);
            return $response;
        }

        $perPage = max(1, (int) config('app.per_page', 12));
        $articles = $this->articleModel->getAllActive(($page - 1) * $perPage, $perPage);
        $total = $this->articleModel->countActive();
        $pages = (int) ceil($total / $perPage);

        $jsonld = [$this->seo->webSiteSchema()];
        $items = [];
        foreach ($articles as $a) {
            $items[] = ['name' => $a['title'], 'url' => $this->seo->canonical('/article/' . (int) $a['id'] . '-' . $a['slug'] . '.html')];
        }
        if ($items !== []) {
            $jsonld[] = $this->seo->itemListSchema($items, '文章资讯');
        }

        $seo = $this->seo->page('文章资讯', '', '/articles', [], $jsonld);
        $data = array_merge($seo, [
            'articles' => $articles,
            'page' => $page,
            'pages' => $pages,
        ]);

        $data['content'] = $this->view->partial('front/articles', $data);
        $html = $this->view->partial('layouts/front', $data);
        $this->cache->set($cacheKey, $html, (int) config('app.cache_ttl', 300));

        $response->getBody()->write($html);
        return $response;
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $article = $this->articleModel->find($id, true);
        if ($article === null) {
            throw new HttpNotFoundException($request, '文章不存在');
        }

        $url = '/article/' . $id . '-' . (string) $article['slug'] . '.html';
        $canonical = $this->seo->canonical($url);
        $jsonld = [
            $this->seo->articleSchema($article, $canonical),
            $this->seo->breadcrumbSchema([
                ['name' => '首页', 'url' => $this->seo->canonical('/')],
                ['name' => '文章资讯', 'url' => $this->seo->canonical('/articles')],
                ['name' => (string) $article['title'], 'url' => $canonical],
            ]),
        ];

        $seo = $this->seo->page(
            (string) $article['title'],
            (string) ($article['summary'] ?? ''),
            $url,
            ['og:type' => 'article'],
            $jsonld
        );

        return $this->view->page($response, 'layouts/front', 'front/article', array_merge($seo, [
            'article' => $article,
        ]));
    }
}
