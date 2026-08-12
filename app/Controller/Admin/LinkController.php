<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Link;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台链接管理控制器。
 */
final class LinkController
{
    public function __construct(private View $view, private Link $model)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/links', [
            'title' => '链接管理',
            'active' => 'links',
            'items' => $this->model->getAll(false),
        ]);
    }
}
