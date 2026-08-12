<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Card;
use App\Model\Category;
use App\Service\SeoService;
use App\Service\SettingService;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * 分类独立页：展示某分类全部卡片（SEO 收录用）。
 */
final class CategoryController
{
    public function __construct(
        private View $view,
        private Category $categoryModel,
        private Card $cardModel,
        private SeoService $seo,
        private SettingService $settings
    ) {
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $category = $this->categoryModel->find($id);
        if ($category === null || (int) $category['is_active'] !== 1) {
            throw new HttpNotFoundException($request, '分类不存在');
        }

        $sortMethod = $this->settings->get('card_sort_method', 'default');
        $cards = $this->cardModel->getByCategory($id, true, $sortMethod);
        $seo = $this->seo->page(
            (string) $category['name'],
            '',
            '/category/' . $id . '.html'
        );

        return $this->view->page($response, 'layouts/front', 'front/category', array_merge($seo, [
            'category' => $category,
            'cards' => $cards,
        ]));
    }
}
