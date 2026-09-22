<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 搜索引擎主动推送服务：百度推送 + IndexNow（同时支持 Bing/Yandex）。
 * 配置缺失时静默跳过；推送失败仅写 error_log，不影响主流程。
 */
final class PingService
{
    private const BAIDU_ENDPOINT = 'https://data.zz.baidu.com/urls';
    private const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
    private const TIMEOUT = 5;

    public function __construct(
        private SettingService $settings,
        private SeoService $seo
    ) {
    }

    /** 推送一组 URL（每条 URL 同时投到 Baidu + IndexNow）。 */
    public function pingUrls(array $urls): void
    {
        $urls = array_values(array_filter(array_map(static fn ($u): string => (string) $u, $urls)));
        if ($urls === []) {
            return;
        }
        $this->baiduPush($urls);
        $this->indexNowPush($urls);
    }

    private function baiduPush(array $urls): void
    {
        $token = $this->settings->get('baidu_push_token', '');
        if ($token === '') {
            return;
        }
        $site = $this->seo->canonical('/');
        $endpoint = self::BAIDU_ENDPOINT . '?site=' . rawurlencode($site) . '&token=' . rawurlencode($token);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: text/plain', 'User-Agent: Mozilla/5.0 (compatible; NavPing/1.0)'],
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $code >= 400) {
            error_log('[ping/baidu] failed code=' . $code . ' err=' . $err . ' resp=' . substr((string) $resp, 0, 200));
        }
    }

    private function indexNowPush(array $urls): void
    {
        $key = $this->settings->get('indexnow_key', '');
        if ($key === '') {
            return;
        }
        $host = parse_url($this->seo->canonical('/'), PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return;
        }
        $payload = (string) json_encode([
            'host' => $host,
            'key' => $key,
            'urlList' => $urls,
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init(self::INDEXNOW_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'User-Agent: Mozilla/5.0 (compatible; NavPing/1.0)'],
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $code >= 400) {
            error_log('[ping/indexnow] failed code=' . $code . ' err=' . $err . ' resp=' . substr((string) $resp, 0, 200));
        }
    }
}