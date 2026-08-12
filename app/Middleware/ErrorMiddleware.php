<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

/**
 * 错误处理中间件（最外层）：捕获 404/405/异常，渲染统一错误页；
 * 错误详情只写日志，用户侧不泄露内部信息（debug 开启时除外）。
 */
final class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private View $view, private bool $debug = false)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpNotFoundException $e) {
            return $this->errorPage(404, '页面不存在');
        } catch (HttpMethodNotAllowedException $e) {
            return $this->errorPage(405, '请求方法不允许');
        } catch (\Throwable $e) {
            error_log(sprintf(
                '[Error] %s in %s:%d (ip=%s, uri=%s)',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                ip(),
                (string) $request->getUri()
            ));
            $message = $this->debug ? $e->getMessage() : '系统维护中，请稍后访问';
            return $this->errorPage(500, $message);
        }
    }

    private function errorPage(int $status, string $message): ResponseInterface
    {
        $response = new \Slim\Psr7\Response($status);
        $html = $this->view->partial('front/error', [
            'status' => $status,
            'message' => $message,
            'title' => $status === 500 ? '系统错误' : '页面未找到',
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}
