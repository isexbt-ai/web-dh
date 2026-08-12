<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Ad;
use App\Model\Article;
use App\Model\Card;
use App\Model\Message;
use App\Model\Notice;
use App\Service\View;
use App\Service\VisitService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台控制台控制器：一次聚合统计各模块数据。
 */
final class DashboardController
{
    public function __construct(
        private View $view,
        private VisitService $visit,
        private Card $card,
        private Article $article,
        private Message $message,
        private Ad $ad,
        private Notice $notice
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $stats = $this->visit->dashboardStats((int) config('app.visitor_display_days', 30));

        return $this->view->page($response, 'layouts/admin', 'admin/dashboard', [
            'title' => '控制台',
            'active' => 'dashboard',
            'stats' => [
                'today_visitors' => $stats['today'],
                'total_visitors' => $stats['total'],
                'total_pv' => $stats['total_pv'],
                'recent_visitors' => $stats['recent'],
                'trend' => $stats['trend'],
                'cards' => $this->card->count(),
                'articles' => $this->article->count(),
                'messages' => $this->message->count(),
                'messages_active' => $this->message->countActive(),
                'ads' => $this->ad->count(),
                'notices' => $this->notice->count(),
            ],
        ]);
    }
}
