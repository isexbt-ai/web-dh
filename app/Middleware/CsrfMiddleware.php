<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * CSRF 中间件：确保 session 持有 token，并对写请求（POST/PUT/PATCH/DELETE）用 hash_equals 校验。
 * 校验失败返回 403 JSON。
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }

        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $body = $request->getParsedBody();
            $sent = is_array($body) && isset($body['csrf_token']) ? (string) $body['csrf_token'] : '';
            if ($sent === '') {
                $sent = $request->getHeaderLine('X-CSRF-Token');
            }
            if ($sent === '' || !hash_equals((string) $_SESSION['csrf_token'], $sent)) {
                $res = new Response();
                $res->getBody()->write((string) json_encode(
                    ['success' => false, 'message' => '请求校验失败，请刷新页面重试'],
                    JSON_UNESCAPED_UNICODE
                ));
                return $res->withStatus(403)->withHeader('Content-Type', 'application/json; charset=utf-8');
            }
        }

        return $handler->handle($request);
    }
}
