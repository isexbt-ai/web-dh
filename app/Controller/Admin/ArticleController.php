<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Article;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台文章管理控制器。
 */
final class ArticleController
{
    public function __construct(private View $view, private Article $model)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/articles', [
            'title' => '文章管理',
            'active' => 'articles',
            'items' => $this->model->getAllAdmin(),
        ]);
    }
}
