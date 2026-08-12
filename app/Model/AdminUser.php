<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 后台管理员 Model。
 */
final class AdminUser extends BaseModel
{
    public function findByUsername(string $username): ?array
    {
        return $this->fetchOne('SELECT * FROM admin_users WHERE username = ?', [$username]);
    }

    public function roleOf(string $username): ?string
    {
        $role = $this->fetchColumn('SELECT role FROM admin_users WHERE username = ?', [$username]);
        return $role === false || $role === null ? null : (string) $role;
    }

    public function updatePassword(string $username, string $passwordHash): bool
    {
        return $this->execute('UPDATE admin_users SET password = ? WHERE username = ?', [$passwordHash, $username]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM admin_users');
    }

    public function create(string $username, string $passwordHash): int
    {
        $this->execute('INSERT INTO admin_users (username, password) VALUES (?, ?)', [$username, $passwordHash]);
        return $this->lastInsertId();
    }
}
