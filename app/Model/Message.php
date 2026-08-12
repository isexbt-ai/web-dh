<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 留言板 Model。
 */
final class Message extends BaseModel
{
    /** 前台列表：带 IP 归属信息，仅显示已审核留言。 */
    public function getApproved(int $offset = 0, int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT m.*, l.country AS loc_country, l.region AS loc_region, l.city AS loc_city
             FROM messages m LEFT JOIN ip_location_cache l ON m.ip = l.ip
             WHERE m.is_active = 1 ORDER BY m.created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /** 后台列表：全部留言（含未审核），带 IP 归属。 */
    public function getAllAdmin(): array
    {
        return $this->fetchAll(
            'SELECT m.*, l.country AS loc_country, l.region AS loc_region, l.city AS loc_city
             FROM messages m LEFT JOIN ip_location_cache l ON m.ip = l.ip
             ORDER BY m.created_at DESC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM messages WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM messages');
    }

    public function countActive(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM messages WHERE is_active = 1');
    }

    public function countApproved(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM messages WHERE is_active = 1');
    }

    public function create(string $nickname, string $content, string $ip, string $userAgent): int
    {
        $this->execute(
            'INSERT INTO messages (nickname, content, ip, user_agent) VALUES (?, ?, ?, ?)',
            [$nickname, $content, $ip, $userAgent]
        );
        return $this->lastInsertId();
    }

    /** 审核/取消审核留言。 */
    public function setActive(int $id, int $isActive): bool
    {
        return $this->execute('UPDATE messages SET is_active = ? WHERE id = ?', [$isActive, $id]);
    }

    /** 后台回复：回复文本为空时清空回复时间。 */
    public function reply(int $id, string $replyText): bool
    {
        return $this->execute(
            "UPDATE messages SET reply = ?, replied_at = CASE WHEN ? = '' THEN NULL ELSE CURRENT_TIMESTAMP END WHERE id = ?",
            [$replyText, $replyText, $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM messages WHERE id = ?', [$id]);
    }

    /** 统计某 IP 最近 N 分钟内提交的留言数（提交防刷）。 */
    public function countRecentByIp(string $ip, int $minutes = 1): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > datetime('now', ?)",
            [$ip, '-' . $minutes . ' minute']
        );
    }
}
