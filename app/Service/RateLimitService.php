<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\LoginAttempt;

/**
 * 限流服务：登录暴力破解防护（IP 级锁定）。
 */
final class RateLimitService
{
    public function __construct(
        private LoginAttempt $model,
        private int $maxAttempts = 5,
        private int $lockDuration = 900
    ) {
    }

    /** 该 IP 当前是否处于锁定状态。 */
    public function isLoginLocked(string $ip): bool
    {
        $row = $this->model->latest($ip);
        if ($row === null || $row['locked_until'] === null) {
            return false;
        }
        return strtotime((string) $row['locked_until']) > time();
    }

    /** 记录一次登录失败，返回剩余允许尝试次数（0 表示已锁定）。 */
    public function recordLoginFailure(string $ip): int
    {
        $row = $this->model->latest($ip);
        $attempts = $row === null ? 1 : (int) $row['attempts'] + 1;
        $lockedUntil = null;
        if ($attempts >= $this->maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + $this->lockDuration);
            $attempts = $this->maxAttempts;
        }
        if ($row === null) {
            $this->model->insertAttempt($ip, $attempts);
        } else {
            $this->model->updateAttempt((int) $row['id'], $attempts, $lockedUntil);
        }
        return max(0, $this->maxAttempts - $attempts);
    }

    /** 登录成功后清除该 IP 的失败记录。 */
    public function resetLoginFailures(string $ip): void
    {
        $this->model->deleteByIp($ip);
    }

    /** 定期清理过期锁定记录。 */
    public function cleanup(): void
    {
        $this->model->cleanupExpired();
    }
}
