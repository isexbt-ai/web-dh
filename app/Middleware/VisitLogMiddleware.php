<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\VisitService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 访问统计中间件：仅对前台 GET 页面记录一次访问（今日同 IP 去重）。
 * 静态资源/后台/API 不记录。
 */
final class VisitLogMiddleware implements MiddlewareInterface
{
    public function __construct(private VisitService $visitService)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ($request->getMethod() === 'GET'
            && !str_starts_with($path, '/admin')
            && !str_starts_with($path, '/api/')
            && !preg_match('/\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|xml|txt|json|map|woff2?|mp4|webm)$/i', $path)
        ) {
            $this->visitService->record($path);
        }
        return $handler->handle($request);
    }
}
