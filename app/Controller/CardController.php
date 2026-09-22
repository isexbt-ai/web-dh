<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Card;
use App\Service\SeoService;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * 卡片详情控制器：详情 + 点击计数（会话级节流）。
 */
final class CardController
{
    public function __construct(
        private View $view,
        private Card $cardModel,
        private SeoService $seo
    ) {
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $card = $this->cardModel->find($id, true);
        if ($card === null) {
            throw new HttpNotFoundException($request, '卡片不存在');
        }

        $this->incrementClick($id);

        $jsonld = [
            $this->seo->webPageSchema(
                (string) $card['title'],
                (string) ($card['detail'] ?? ''),
                $this->seo->canonical('/card/' . $id . '.html')
            ),
            $this->seo->breadcrumbSchema([
                ['name' => '首页', 'url' => $this->seo->canonical('/')],
                ['name' => (string) $card['title'], 'url' => $this->seo->canonical('/card/' . $id . '.html')],
            ]),
        ];

        $seo = $this->seo->page(
            (string) $card['title'],
            (string) ($card['detail'] ?? ''),
            '/card/' . $id . '.html',
            [],
            $jsonld
        );

        return $this->view->page($response, 'layouts/front', 'front/card', array_merge($seo, [
            'card' => $card,
        ]));
    }

    private function incrementClick(int $id): void
    {
        $key = 'card_clicked_' . $id;
        if (empty($_SESSION[$key])) {
            $this->cardModel->incrementClick($id);
            $_SESSION[$key] = true;
        }
    }
}
