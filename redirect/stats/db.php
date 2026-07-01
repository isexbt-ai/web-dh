<?php
/**
 * stats/db.php - SQLite数据库操作
 * 访问统计数据库模型
 */

class StatsDB {
    private $db;
    private $path;

    public function __construct($path = null) {
        $this->path = $path ?: __DIR__ . '/data/stats.db';
        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->db = new SQLite3($this->path);
        $this->db->busyTimeout(5000);
        $this->initTables();
    }

    private function initTables() {
        // 访问记录表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS visits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT NOT NULL,
                country_code TEXT,
                country_name TEXT,
                province TEXT,
                city TEXT,
                user_agent TEXT,
                device_type TEXT,
                browser TEXT,
                os TEXT,
                referer TEXT,
                page_url TEXT,
                cf_ray TEXT,
                created_at INTEGER
            )
        ');

        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_visits_time ON visits(created_at);
            CREATE INDEX IF NOT EXISTS idx_visits_ip ON visits(ip);
            CREATE INDEX IF NOT EXISTS idx_visits_country ON visits(country_code);
        ');

        // 每日统计汇总表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS daily_stats (
                date TEXT PRIMARY KEY,
                total_visits INTEGER DEFAULT 0,
                unique_ips INTEGER DEFAULT 0,
                mobile_visits INTEGER DEFAULT 0,
                desktop_visits INTEGER DEFAULT 0,
                top_country TEXT,
                updated_at INTEGER
            )
        ');
    }

    // 记录访问
    public function recordVisit($data) {
        $stmt = $this->db->prepare('
            INSERT INTO visits (ip, country_code, country_name, province, city,
                user_agent, device_type, browser, os, referer, page_url, cf_ray, created_at)
            VALUES (:ip, :cc, :cn, :prov, :city, :ua, :device, :browser, :os,
                :referer, :page, :ray, :time)
        ');

        $stmt->bindValue(':ip', $data['ip'] ?? '0.0.0.0');
        $stmt->bindValue(':cc', $data['country_code'] ?? 'XX');
        $stmt->bindValue(':cn', $data['country_name'] ?? '未知');
        $stmt->bindValue(':prov', $data['province'] ?? null);
        $stmt->bindValue(':city', $data['city'] ?? null);
        $stmt->bindValue(':ua', substr($data['user_agent'] ?? '', 0, 500));
        $stmt->bindValue(':device', $data['device_type'] ?? 'unknown');
        $stmt->bindValue(':browser', $data['browser'] ?? 'unknown');
        $stmt->bindValue(':os', $data['os'] ?? 'unknown');
        $stmt->bindValue(':referer', substr($data['referer'] ?? '', 0, 2048));
        $stmt->bindValue(':page', substr($data['page_url'] ?? '', 0, 2048));
        $stmt->bindValue(':ray', $data['cf_ray'] ?? null);
        $stmt->bindValue(':time', time());

        return $stmt->execute();
    }

    // 获取今日统计
    public function getTodayStats() {
        $today = date('Y-m-d');
        $start = strtotime($today . ' 00:00:00');

        $result = $this->db->query("
            SELECT
                COUNT(*) as total,
                COUNT(DISTINCT ip) as unique_ips,
                SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile,
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet
            FROM visits WHERE created_at >= $start
        ");

        return $result->fetchArray(SQLITE3_ASSOC) ?: [
            'total' => 0, 'unique_ips' => 0,
            'mobile' => 0, 'desktop' => 0, 'tablet' => 0
        ];
    }

    // 获取国家分布TOP10
    public function getCountryDistribution($limit = 10) {
        $result = $this->db->query("
            SELECT country_name, country_code, COUNT(*) as count
            FROM visits WHERE country_code IS NOT NULL AND country_code != 'XX'
            GROUP BY country_code ORDER BY count DESC LIMIT $limit
        ");

        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    // 获取设备分布
    public function getDeviceDistribution() {
        $result = $this->db->query("
            SELECT device_type, COUNT(*) as count
            FROM visits GROUP BY device_type ORDER BY count DESC
        ");

        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    // 获取浏览器分布
    public function getBrowserDistribution() {
        $result = $this->db->query("
            SELECT browser, COUNT(*) as count
            FROM visits GROUP BY browser ORDER BY count DESC LIMIT 10
        ");

        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    // 获取最近访问记录
    public function getRecentVisits($limit = 50) {
        $result = $this->db->query("
            SELECT * FROM visits ORDER BY created_at DESC LIMIT $limit
        ");

        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['created_at'] = date('Y-m-d H:i:s', $row['created_at']);
            $data[] = $row;
        }
        return $data;
    }

    // 获取7天趋势
    public function getTrend($days = 7) {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $start = strtotime($date . ' 00:00:00');
            $end = $start + 86400;

            $result = $this->db->query("
                SELECT COUNT(*) as count, COUNT(DISTINCT ip) as unique_ips
                FROM visits WHERE created_at >= $start AND created_at < $end
            ");

            $row = $result->fetchArray(SQLITE3_ASSOC);
            $data[] = [
                'date' => $date,
                'count' => (int)$row['count'],
                'unique_ips' => (int)$row['unique_ips']
            ];
        }
        return $data;
    }

    // 获取总统计
    public function getTotalStats() {
        $result = $this->db->query("
            SELECT COUNT(*) as total, COUNT(DISTINCT ip) as unique_ips,
                   MIN(created_at) as first_visit
            FROM visits
        ");

        return $result->fetchArray(SQLITE3_ASSOC) ?: [
            'total' => 0, 'unique_ips' => 0, 'first_visit' => null
        ];
    }
}
