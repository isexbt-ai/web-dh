<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 原生 PHP 模板渲染服务。
 * - page():   布局 + 内容页渲染成完整响应
 * - partial(): 渲染局部模板返回 HTML 字符串（供布局/循环使用）
 * 模板内通过全局函数 e()/config()/asset() 和传入的 $data 访问数据。
 */
final class View
{
    private string $viewsPath;

    public function __construct(private ContainerInterface $container)
    {
        $this->viewsPath = base_path('resources/views');
    }

    /** 渲染完整页面（布局包裹内容模板），写入响应体并返回。 */
    public function page(ResponseInterface $response, string $layout, string $template, array $data = []): ResponseInterface
    {
        $content = $this->partial($template, $data);
        $data['content'] = $content;
        return $this->render($response, $layout, $data);
    }

    /** 渲染完整模板到响应体。 */
    public function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        $html = $this->partial($template, $data);
        $response->getBody()->write($html);
        return $response;
    }

    /** 渲染局部模板，返回 HTML 字符串。 */
    public function partial(string $template, array $data = []): string
    {
        $data['__view'] = $this;
        extract($data, EXTR_SKIP);
        ob_start();
        include $this->viewsPath . '/' . $template . '.php';
        return (string) ob_get_clean();
    }
}
