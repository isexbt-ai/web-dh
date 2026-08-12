<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Gallery;
use App\Model\Showcase;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台效果展示管理控制器。
 */
final class ShowcaseController
{
    public function __construct(private View $view, private Showcase $model, private Gallery $gallery)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/showcase', [
            'title' => '效果展示',
            'active' => 'showcase',
            'items' => $this->model->getAll(false),
            'galleries' => $this->gallery->getAll(false),
        ]);
    }
}
