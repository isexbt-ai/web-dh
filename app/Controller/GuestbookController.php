<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Message;
use App\Service\SeoService;
use App\Service\SettingService;
use App\Service\View;
use App\Service\VisitService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * 留言板控制器：列表（分页）+ 页面配置。
 */
final class GuestbookController
{
    public function __construct(
        private View $view,
        private Message $messageModel,
        private SeoService $seo,
        private SettingService $settings,
        private VisitService $visitService
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        if ($this->settings->get('guestbook_enabled', '1') !== '1') {
            throw new HttpNotFoundException($request, '留言板已关闭');
        }

        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, (int) config('app.guestbook_per_page', 10));

        $messages = $this->messageModel->getApproved(($page - 1) * $perPage, $perPage);
        $total = $this->messageModel->countApproved();
        $pages = (int) ceil($total / $perPage);

        $seo = $this->seo->page(
            $this->settings->get('guestbook_title', '联系我们'),
            (string) $this->settings->get('guestbook_notice', ''),
            '/guestbook'
        );

        return $this->view->page($response, 'layouts/front', 'front/guestbook', array_merge($seo, [
            'messages' => $messages,
            'page' => $page,
            'pages' => $pages,
            'guestbook' => [
                'title' => $this->settings->get('guestbook_title', '联系我们'),
                'subtitle' => $this->settings->get('guestbook_subtitle', '欢迎留下你的想法'),
                'image' => $this->settings->get('guestbook_image', ''),
                'notice' => $this->settings->get('guestbook_notice', '欢迎联系我们！如有任何问题或建议，请填写以下表单，我们会尽快回复您。'),
            ],
        ]));
    }
}
