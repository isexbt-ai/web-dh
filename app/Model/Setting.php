<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 站点配置 Model（site_config 键值表）。
 */
final class Setting extends BaseModel
{
    public function all(): array
    {
        return $this->fetchAll('SELECT key, value, type FROM site_config');
    }

    public function get(string $key, string $default = ''): string
    {
        $value = $this->fetchColumn('SELECT value FROM site_config WHERE key = ?', [$key]);
        return $value === false || $value === null ? $default : (string) $value;
    }

    public function set(string $key, string $value): bool
    {
        return $this->execute(
            "INSERT INTO site_config (key, value) VALUES (?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP",
            [$key, $value]
        );
    }

    /** 批量写入配置（白名单过滤由调用方负责）。 */
    public function setMany(array $configs): void
    {
        foreach ($configs as $key => $value) {
            $this->set($key, (string) $value);
        }
    }
}
