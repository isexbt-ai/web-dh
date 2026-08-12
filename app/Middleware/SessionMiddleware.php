<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 会话中间件：注入请求上下文到静态 Context，并以安全参数启动 session。
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Context::$request = $request;

        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure' => !empty($_SERVER['HTTPS']),
            ]);
            session_name('dhz_session');
            session_start();
        }

        return $handler->handle($request);
    }
}
