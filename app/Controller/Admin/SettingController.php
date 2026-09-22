<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SettingService;
use App\Service\View;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 后台站点配置控制器：渲染可编辑配置项（保存走 Admin\ApiController，需 superadmin）。
 */
final class SettingController
{
    /** 后台可配置的键（白名单） */
    private const CONFIG_KEYS = [
        'site_title', 'site_subtitle', 'site_description', 'site_keywords',
        'site_theme',
        'card_sort_method', 'guestbook_enabled',
        'guestbook_title', 'guestbook_subtitle', 'guestbook_image', 'guestbook_notice',
        'umami_enabled', 'umami_script_url', 'umami_website_id',
        'cards_per_row_desktop', 'cards_per_row_tablet', 'cards_per_row_mobile',
        'visitor_base_offset', 'visitor_daily_increment',
        'baidu_push_token', 'indexnow_key',
    ];

    /** 可选主题清单（key => 显示名）。 */
    public const THEME_CHOICES = [
        'default' => '浅红品牌（默认）',
        'dark' => '暗夜深邃',
        'corporate' => '商务极简',
        'warm' => '暖橙亲和',
        'mint' => '薄荷清新',
    ];

    public function __construct(private View $view, private SettingService $settings)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $values = [];
        foreach (self::CONFIG_KEYS as $key) {
            $values[$key] = $this->settings->get($key, $this->defaultFor($key));
        }

        return $this->view->page($response, 'layouts/admin', 'admin/config', [
            'title' => '站点配置',
            'active' => 'config',
            'values' => $values,
            'theme_choices' => self::THEME_CHOICES,
        ]);
    }

    private function defaultFor(string $key): string
    {
        return match ($key) {
            'site_title' => (string) config('seo.site_title', '美女导航'),
            'site_description' => (string) config('seo.site_description', ''),
            'site_keywords' => (string) config('seo.site_keywords', ''),
            'site_theme' => 'default',
            'card_sort_method' => 'default',
            'guestbook_enabled', 'umami_enabled' => '1',
            'visitor_base_offset' => '88888',
            'visitor_daily_increment' => '137',
            default => '',
        };
    }
}
