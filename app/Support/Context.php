<?php

declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 当前请求上下文：由 SessionMiddleware 在请求开始时注入，
 * 供全局辅助函数（ip()、url()）无侵入读取请求信息。
 */
final class Context
{
    public static ?ServerRequestInterface $request = null;

    public static function ip(): string
    {
        $request = self::$request;
        if ($request === null) {
            return '';
        }
        $server = $request->getServerParams();
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'] as $header) {
            if (!empty($server[$header])) {
                return (string) $server[$header];
            }
        }
        return (string) ($server['REMOTE_ADDR'] ?? '');
    }

    public static function baseUrl(): string
    {
        $request = self::$request;
        if ($request === null) {
            return '';
        }
        return rtrim((string) $request->getUri()->getPath(), '/');
    }
}
