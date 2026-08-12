<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * 后台认证中间件：未登录访问后台页面时 302 到登录页。
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (empty($_SESSION['admin_username']) && $path !== '/admin/login') {
            return (new Response())->withStatus(302)->withHeader('Location', '/admin/login');
        }
        return $handler->handle($request);
    }
}
