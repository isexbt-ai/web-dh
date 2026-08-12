<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Article;
use App\Model\Card;
use App\Model\Category;
use App\Service\SeoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * sitemap.xml 生成：首页/分类/卡片/文章/静态页，含 lastmod 与优先级。
 */
final class SitemapController
{
    public function __construct(
        private Card $cardModel,
        private Article $articleModel,
        private Category $categoryModel,
        private SeoService $seo
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $urls = [['loc' => $this->seo->canonical('/'), 'lastmod' => date('Y-m-d'), 'priority' => '1.0']];

        foreach ($this->categoryModel->getAll(true) as $c) {
            $urls[] = ['loc' => $this->seo->canonical('/category/' . (int) $c['id'] . '.html'), 'priority' => '0.8'];
        }
        foreach ($this->cardModel->getAllActive() as $card) {
            $urls[] = [
                'loc' => $this->seo->canonical('/card/' . (int) $card['id'] . '.html'),
                'lastmod' => $this->lastmod((string) $card['created_at']),
                'priority' => '0.6',
            ];
        }
        foreach ($this->articleModel->sitemapItems() as $a) {
            $urls[] = [
                'loc' => $this->seo->canonical('/article/' . (int) $a['id'] . '-' . $a['slug'] . '.html'),
                'lastmod' => $this->lastmod((string) $a['created_at']),
                'priority' => '0.7',
            ];
        }
        foreach (['/articles', '/guestbook', '/showcase'] as $path) {
            $urls[] = ['loc' => $this->seo->canonical($path), 'priority' => '0.5'];
        }

        $items = '';
        foreach ($urls as $u) {
            $items .= '<url>'
                . '<loc>' . $this->xml((string) $u['loc']) . '</loc>'
                . (isset($u['lastmod']) ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
                . (isset($u['priority']) ? '<priority>' . $u['priority'] . '</priority>' : '')
                . "</url>\n";
        }

        $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . $items
            . "</urlset>\n";

        $response->getBody()->write($body);
        return $response->withHeader('Content-Type', 'application/xml; charset=utf-8');
    }

    private function lastmod(string $datetime): string
    {
        $ts = strtotime($datetime);
        return $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
