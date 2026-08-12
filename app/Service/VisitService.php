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
    public function __construct(private VisitStat $model)
    {
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

    /** 前台访客数展示：总访客 + 近 N 天访客。 */
    public function displayStats(int $days = 30): array
    {
        return [
            'total_visitors' => $this->model->countUniqueTotal(),
            'recent_visitors' => $this->model->countUniqueSince($days),
        ];
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
