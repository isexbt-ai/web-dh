<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Category;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台分类管理控制器。
 */
final class CategoryController
{
    public function __construct(private View $view, private Category $model)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/categories', [
            'title' => '分类管理',
            'active' => 'categories',
            'items' => $this->model->getAll(false),
        ]);
    }
}
