<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Ad;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台广告管理控制器（数据操作走 Admin\ApiController）。
 */
final class AdController
{
    public function __construct(private View $view, private Ad $model)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/ads', [
            'title' => '广告管理',
            'active' => 'ads',
            'items' => $this->model->getAll(false),
        ]);
    }
}
