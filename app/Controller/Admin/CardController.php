<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Card;
use App\Model\Category;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台卡片管理控制器。
 */
final class CardController
{
    public function __construct(private View $view, private Card $model, private Category $category)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/cards', [
            'title' => '卡片管理',
            'active' => 'cards',
            'items' => $this->model->getByCategory(null, false),
            'categories' => $this->category->getAll(false),
        ]);
    }
}
