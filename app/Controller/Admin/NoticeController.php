<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Notice;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台公告管理控制器。
 */
final class NoticeController
{
    public function __construct(private View $view, private Notice $model)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/notices', [
            'title' => '公告管理',
            'active' => 'notices',
            'items' => $this->model->getAll(false),
        ]);
    }
}
