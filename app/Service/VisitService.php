<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\VisitStat;
use App\Support\Context;

/**
 * 访问统计服务：今日同 IP 去重记录 + 前台/后台统计聚合。
 */
final class VisitService
{
    /** 前台展示数据缓存 key + TTL（与首页整页缓存一致：5 分钟）。 */
    private const DISPLAY_CACHE_KEY = 'visitor_display_stats';
    private const DISPLAY_CACHE_TTL = 300;

    public function __construct(
        private VisitStat $model,
        private SettingService $settings,
        private CacheService $cache,
    ) {
    }

    /**
     * 记录一次访问：同一 IP 当天只记一条（明细防刷）。
     * IP 由中间件传入（避免依赖 Context::ip() 与 SessionMiddleware 顺序耦合），
     * 其他调用方（管理脚本等）可传空字符串占位。
     */
    public function record(string $page, string $clientIp = ''): void
    {
        if ($clientIp === '') {
            $clientIp = (string) (Context::ip() ?? '');
        }
        if ($clientIp === '') {
            return;
        }
        if ($this->model->hasVisitedToday($clientIp)) {
            return;
        }
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->model->add($page, $clientIp, mb_substr($ua, 0, 255));
    }

    /** 前台访客数展示：真实去重访客数。5 分钟缓存避免每次请求都查 stats 表。 */
    public function displayStats(int $days = 30): array
    {
        $cached = $this->cache->get(self::DISPLAY_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }
        $data = [
            'total_visitors' => $this->model->countUniqueTotal(),
            'recent_visitors' => $this->model->countUniqueSince($days),
        ];
        $this->cache->set(self::DISPLAY_CACHE_KEY, $data, self::DISPLAY_CACHE_TTL);
        return $data;
    }

    /** 后台仪表盘统计汇总。 */
    public function dashboardStats(int $days = 30): array
    {
        return [
            'today' => $this->model->countUniqueToday(),
            'total' => $this->model->countUniqueTotal(),
            'total_pv' => $this->model->countTotal(),
            'recent' => $this->model->countUniqueSince($days),
            'trend' => $this->model->dailyTrend($days),
        ];
    }
}
