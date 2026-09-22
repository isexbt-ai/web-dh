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
 * 静态资源/后台/API 不记录。IP 直接从 request 取，避免依赖中间件执行顺序。
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
            $this->visitService->record($path, $this->extractIp($request));
        }
        return $handler->handle($request);
    }

    /**
     * 从请求头解析客户端 IP（CF/X-Real-IP > REMOTE_ADDR）。
     * 不依赖 Context::$request（避免被中间件执行顺序耦合）。
     */
    private function extractIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'] as $header) {
            if (!empty($server[$header]) && is_string($server[$header])) {
                return $server[$header];
            }
        }
        return (string) ($server['REMOTE_ADDR'] ?? '');
    }
}
