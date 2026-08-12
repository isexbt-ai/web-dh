<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 登录尝试记录 Model（暴力破解防护数据层）。
 */
final class LoginAttempt extends BaseModel
{
    public function latest(string $ip): ?array
    {
        return $this->fetchOne(
            'SELECT id, attempts, locked_until FROM login_attempts WHERE ip = ? ORDER BY id DESC LIMIT 1',
            [$ip]
        );
    }

    public function insertAttempt(string $ip, int $attempts): int
    {
        $this->execute('INSERT INTO login_attempts (ip, attempts) VALUES (?, ?)', [$ip, $attempts]);
        return $this->lastInsertId();
    }

    public function updateAttempt(int $id, int $attempts, ?string $lockedUntil): bool
    {
        if ($lockedUntil === null) {
            return $this->execute('UPDATE login_attempts SET attempts = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$attempts, $id]);
        }
        return $this->execute(
            'UPDATE login_attempts SET attempts = ?, locked_until = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$attempts, $lockedUntil, $id]
        );
    }

    public function deleteByIp(string $ip): bool
    {
        return $this->execute('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
    }

    /** 清理已过期的锁定记录（超过 1 小时的旧数据），返回删除行数。 */
    public function cleanupExpired(): int
    {
        $stmt = $this->_pdo->prepare("DELETE FROM login_attempts WHERE locked_until < datetime('now', '-1 hour')");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
