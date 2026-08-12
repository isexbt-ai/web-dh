<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Article;
use App\Model\Card;
use App\Service\SeoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * feed.xml RSS 2.0：最新卡片（20）+ 最新文章（10）。
 */
final class FeedController
{
    public function __construct(
        private Card $cardModel,
        private Article $articleModel,
        private SeoService $seo
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $siteTitle = $this->seo->siteTitle();
        $siteDesc = $this->seo->siteDescription();
        $base = $this->seo->baseUrl();
        $now = date(DATE_RSS);

        $items = '';
        foreach ($this->cardModel->getAllActive() as $i => $card) {
            if ($i >= 20) {
                break;
            }
            $items .= $this->item(
                (string) $card['title'],
                $base . '/card/' . (int) $card['id'] . '.html',
                (string) ($card['detail'] ?? ''),
                $this->rssDate((string) $card['created_at'])
            );
        }
        foreach ($this->articleModel->sitemapItems() as $i => $a) {
            if ($i >= 10) {
                break;
            }
            $items .= $this->item(
                (string) $a['title'],
                $base . '/article/' . (int) $a['id'] . '-' . $a['slug'] . '.html',
                (string) ($a['title'] ?? ''),
                $this->rssDate((string) $a['created_at'])
            );
        }

        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n"
            . '<channel>' . "\n"
            . '<title>' . $this->xml($siteTitle) . '</title>' . "\n"
            . '<link>' . $base . '/</link>' . "\n"
            . '<description>' . $this->xml($siteDesc) . '</description>' . "\n"
            . '<language>zh-CN</language>' . "\n"
            . '<lastBuildDate>' . $now . '</lastBuildDate>' . "\n"
            . '<atom:link href="' . $base . '/feed.xml" rel="self" type="application/rss+xml"/>' . "\n"
            . $items
            . '</channel>' . "\n"
            . '</rss>' . "\n";

        $response->getBody()->write($body);
        return $response->withHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    private function item(string $title, string $link, string $desc, string $pubDate): string
    {
        return '<item>'
            . '<title>' . $this->xml($title) . '</title>'
            . '<link>' . $this->xml($link) . '</link>'
            . '<guid isPermaLink="false">' . $this->xml($link) . '</guid>'
            . '<description>' . $this->xml($desc) . '</description>'
            . '<pubDate>' . $pubDate . '</pubDate>'
            . "</item>\n";
    }

    private function rssDate(string $datetime): string
    {
        $ts = strtotime($datetime);
        return $ts === false ? date(DATE_RSS) : date(DATE_RSS, $ts);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
