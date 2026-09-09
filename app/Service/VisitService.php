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
    /** 前台人气基数默认值（看起来很多人）。 */
    private const DEFAULT_BASE_OFFSET = 88888;
    /** 每天净增默认值。 */
    private const DEFAULT_DAILY_INCREMENT = 137;

    public function __construct(
        private VisitStat $model,
        private SettingService $settings,
    ) {
    }

    /** 记录一次访问：同一 IP 当天只记一条（明细防刷）。 */
    public function record(string $page): void
    {
        $clientIp = Context::ip();
        if ($this->model->hasVisitedToday($clientIp)) {
            return;
        }
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->model->add($page, $clientIp, mb_substr($ua, 0, 255));
    }

    /** 前台访客数展示：真实数 + 人气基数 + 按天增量 + 小幅抖动，看起来人多且每天增长。 */
    public function displayStats(int $days = 30): array
    {
        $realTotal = $this->model->countUniqueTotal();
        $realRecent = $this->model->countUniqueSince($days);
        $boost = $this->popularityBoost();
        return [
            'total_visitors' => $realTotal,
            'recent_visitors' => $realRecent,
            'display_total' => $realTotal + $boost,
            'display_recent' => $realRecent + $boost,
        ];
    }

    /** 人气加成：基数 + 开站天数 × 日增量 + 当天固定抖动（同一天内数字稳定）。 */
    private function popularityBoost(): int
    {
        $base = (int) $this->settings->get('visitor_base_offset', (string) self::DEFAULT_BASE_OFFSET);
        $inc = (int) $this->settings->get('visitor_daily_increment', (string) self::DEFAULT_DAILY_INCREMENT);
        $firstDate = $this->model->fetchColumn('SELECT MIN(visit_date) FROM visit_stats');
        $daysSinceStart = 0;
        if (is_string($firstDate) && $firstDate !== '') {
            $ts = strtotime($firstDate);
            if ($ts !== false) {
                $daysSinceStart = max(0, (int) floor((time() - $ts) / 86400));
            }
        }
        mt_srand((int) date('Ymd'));
        return $base + $daysSinceStart * $inc + mt_rand(0, 99);
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
