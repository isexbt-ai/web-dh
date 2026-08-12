<?php

declare(strict_types=1);

namespace App\Service;

/**
 * SEO 服务：统一生成页面 title/description/canonical/OpenGraph/JSON-LD，
 * 站点名/描述从 site_config 读取（回退 config/seo.php 默认值）。
 */
final class SeoService
{
    private string $domain;

    public function __construct(private SettingService $settings)
    {
        $this->domain = rtrim((string) config('seo.domain', ''), '/');
    }

    /** 完整站点基址，如 https://dh.xlbk.blog。 */
    public function baseUrl(): string
    {
        $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        return $scheme . '://' . $this->domain;
    }

    public function siteTitle(): string
    {
        return $this->settings->get('site_title', (string) config('seo.site_title', '美女导航'));
    }

    public function siteDescription(): string
    {
        return $this->settings->get('site_description', (string) config('seo.site_description', ''));
    }

    /** 拼装规范链接（带域名）。 */
    public function canonical(string $path = '/'): string
    {
        return $this->baseUrl() . '/' . ltrim($path, '/');
    }

    /**
     * 生成页面 SEO 数据包（与 front 布局变量对齐）。
     *
     * @param array{title?: string, description?: string, image?: string, type?: string} $extraOg 追加 OG 字段
     * @param array<int, array<string, mixed>> $jsonld JSON-LD 块
     * @return array{title: string, description: string, canonical: string, og: array<string, string>, jsonld: array}
     */
    public function page(string $pageTitle, string $desc = '', string $path = '/', array $extraOg = [], array $jsonld = []): array
    {
        $siteTitle = $this->siteTitle();
        $fullTitle = $pageTitle === '' ? $siteTitle : $pageTitle . ' - ' . $siteTitle;
        $siteDesc = $desc !== '' ? $desc : $this->siteDescription();
        $canonical = $this->canonical($path);

        $og = array_merge([
            'og:title' => $fullTitle,
            'og:description' => $siteDesc,
            'og:image' => $this->baseUrl() . (string) config('seo.og_image', '/assets/images/logo.png'),
            'og:type' => 'website',
            'og:url' => $canonical,
            'og:site_name' => $siteTitle,
            'og:locale' => 'zh_CN',
        ], $extraOg);

        return [
            'title' => $fullTitle,
            'description' => $siteDesc,
            'canonical' => $canonical,
            'og' => $og,
            'jsonld' => $jsonld,
            'umami' => $this->trackingConfig(),
        ];
    }

    /** umami 统计脚本配置（布局输出用）。 */
    public function trackingConfig(): array
    {
        return [
            'enabled' => $this->settings->get('umami_enabled', '1') === '1',
            'script_url' => $this->settings->get('umami_script_url', ''),
            'website_id' => $this->settings->get('umami_website_id', ''),
        ];
    }

    /** 网站级 JSON-LD（WebSite schema，含 SearchAction）。 */
    public function webSiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteTitle(),
            'url' => $this->baseUrl() . '/',
            'description' => $this->siteDescription(),
        ];
    }

    /** ItemList 列表 JSON-LD。 */
    public function itemListSchema(array $items, string $name = '精选导航'): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'itemListElement' => array_values(array_map(
                static fn (array $it, int $i): array => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => (string) $it['name'],
                    'url' => (string) $it['url'],
                ],
                $items,
                array_keys($items)
            )),
        ];
    }

    /** 面包屑 JSON-LD。 */
    public function breadcrumbSchema(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_map(
                static fn (array $c, int $i): array => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => (string) $c['name'],
                    'item' => (string) $c['url'],
                ],
                $crumbs,
                array_keys($crumbs)
            )),
        ];
    }
}
