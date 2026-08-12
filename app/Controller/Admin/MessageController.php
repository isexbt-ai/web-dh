<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Message;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台留言管理控制器。
 */
final class MessageController
{
    public function __construct(private View $view, private Message $model)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->page($response, 'layouts/admin', 'admin/messages', [
            'title' => '留言管理',
            'active' => 'messages',
            'items' => $this->model->getAllAdmin(),
        ]);
    }
}
