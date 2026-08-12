<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SeoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * robots.txt：允许抓取、屏蔽后台，Sitemap 域名从配置读取。
 */
final class RobotsController
{
    public function __construct(private SeoService $seo)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $body = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admin\n"
            . "Disallow: /api/\n"
            . "\n"
            . "Sitemap: " . $this->seo->canonical('/sitemap.xml') . "\n";

        $response->getBody()->write($body);
        return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }
}
