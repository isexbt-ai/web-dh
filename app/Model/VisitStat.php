<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 访问统计 Model（visit_stats 明细 + 聚合）。
 */
final class VisitStat extends BaseModel
{
    public function hasVisitedToday(string $ip): bool
    {
        $count = (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM visit_stats WHERE ip = ? AND visit_date = date('now')",
            [$ip]
        );
        return $count > 0;
    }

    public function add(string $page, string $ip, string $userAgent): int
    {
        $this->execute(
            "INSERT INTO visit_stats (page, ip, user_agent, visit_date) VALUES (?, ?, ?, date('now'))",
            [$page, $ip, $userAgent]
        );
        return $this->lastInsertId();
    }

    public function countToday(): int
    {
        return (int) $this->fetchColumn("SELECT COUNT(*) FROM visit_stats WHERE visit_date = date('now')");
    }

    public function countUniqueToday(): int
    {
        return (int) $this->fetchColumn("SELECT COUNT(DISTINCT ip) FROM visit_stats WHERE visit_date = date('now')");
    }

    public function countTotal(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM visit_stats');
    }

    public function countUniqueTotal(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(DISTINCT ip) FROM visit_stats');
    }

    /** 最早一次访问日期（YYYY-MM-DD），用于按开站天数计算人气加成。 */
    public function firstVisitDate(): ?string
    {
        $val = $this->fetchColumn('SELECT MIN(visit_date) FROM visit_stats');
        return is_string($val) && $val !== '' ? $val : null;
    }

    /** 最近 N 天内去重访客数（含今天）。 */
    public function countUniqueSince(int $days): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT ip) FROM visit_stats WHERE visit_date >= date('now', ?)",
            ["-$days day"]
        );
    }

    /** 最近 N 天每日去重访客数，用于后台趋势图。 */
    public function dailyTrend(int $days): array
    {
        return $this->fetchAll(
            "SELECT visit_date, COUNT(DISTINCT ip) AS unique_visitors
             FROM visit_stats
             WHERE visit_date >= date('now', ?)
             GROUP BY visit_date ORDER BY visit_date ASC",
            ["-$days day"]
        );
    }
}
