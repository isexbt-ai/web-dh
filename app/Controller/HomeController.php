<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Ad;
use App\Model\Card;
use App\Model\Category;
use App\Model\Notice;
use App\Service\CacheService;
use App\Service\SeoService;
use App\Service\SettingService;
use App\Service\View;
use App\Service\VisitService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 首页控制器：轮播 + 分类卡片（一次查询分组，无 N+1）+ 公告 + 访客统计。
 * 整页文件缓存 5 分钟，后台写操作时失效。
 */
final class HomeController
{
    public function __construct(
        private View $view,
        private SeoService $seo,
        private SettingService $settings,
        private Ad $adModel,
        private Category $categoryModel,
        private Card $cardModel,
        private Notice $noticeModel,
        private VisitService $visitService,
        private CacheService $cache
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $cacheKey = 'page_home';
        $cached = $this->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            $response->getBody()->write($cached);
            return $response;
        }

        $siteTitle = $this->seo->siteTitle();
        $siteSubtitle = $this->settings->get('site_subtitle', (string) config('seo.site_description'));
        $sortMethod = $this->settings->get('card_sort_method', 'default');

        $categories = $this->categoryModel->getAll(true);

        // 一次取全部启用卡片，按分类分组（消除循环查库）
        $cardsByCategory = [];
        foreach ($this->cardModel->getAllActive($sortMethod) as $card) {
            $cardsByCategory[(int) $card['category_id']][] = $card;
        }
        $categoryCards = [];
        foreach ($categories as $cat) {
            $categoryCards[] = [
                'id' => (int) $cat['id'],
                'name' => (string) $cat['name'],
                'cards' => $cardsByCategory[(int) $cat['id']] ?? [],
            ];
        }

        // 结构化数据：站点 + 卡片 ItemList
        $jsonld = [$this->seo->webSiteSchema()];
        $cardItems = [];
        foreach ($categoryCards as $cat) {
            foreach ($cat['cards'] as $card) {
                $cardItems[] = ['name' => $card['title'], 'url' => $this->seo->canonical('/card/' . (int) $card['id'] . '.html')];
            }
        }
        if ($cardItems !== []) {
            $jsonld[] = $this->seo->itemListSchema($cardItems, '精选导航');
        }

        $stats = $this->visitService->displayStats((int) config('app.visitor_display_days', 30));

        // 轮播容器比例：按第一张广告图尺寸自适应（避免固定 16:9 cover 裁剪显示不全）
        $ads = $this->adModel->getAll(true);
        $carouselStyle = 'height: 180px;';
        if (isset($ads[0]['image']) && is_string($ads[0]['image']) && str_starts_with($ads[0]['image'], 'uploads/')) {
            $imgPath = base_path('/') . ltrim($ads[0]['image'], '/');
            if (is_file($imgPath)) {
                $size = @getimagesize($imgPath);
                if (is_array($size) && ($size[0] ?? 0) > 0 && ($size[1] ?? 0) > 0) {
                    $carouselStyle = 'aspect-ratio: ' . (int) $size[0] . '/' . (int) $size[1] . ';';
                }
            }
        }

        $seo = $this->seo->page($siteTitle, $siteSubtitle, '/', [], $jsonld);
        $data = array_merge($seo, [
            'siteTitle' => $siteTitle,
            'siteSubtitle' => $siteSubtitle,
            'categories' => $categories,
            'categoryCards' => $categoryCards,
            'ads' => $ads,
            'carouselStyle' => $carouselStyle,
            'notices' => $this->noticeModel->getAll(true),
            'visitorCount' => (int) $stats['total_visitors'],
            'recentVisitors' => (int) $stats['recent_visitors'],
            'guestbookEnabled' => $this->settings->get('guestbook_enabled', '1') === '1',
        ]);

        $data['content'] = $this->view->partial('front/home', $data);
        $html = $this->view->partial('layouts/front', $data);
        $this->cache->set($cacheKey, $html, (int) config('app.cache_ttl', 300));

        $response->getBody()->write($html);
        return $response;
    }
}
