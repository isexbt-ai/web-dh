<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 文件缓存服务（PSR-16 风格）：值序列化到 storage/cache/*.cache 文件。
 * - ttl=0 表示永不过期；get() 命中时惰性清理过期文件。
 */
final class CacheService
{
    private string $dir;
    private int $defaultTtl;

    public function __construct(string $dir, int $defaultTtl = 300)
    {
        $this->dir = rtrim($dir, '/');
        $this->defaultTtl = $defaultTtl;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);
        if (!is_file($file)) {
            return $default;
        }
        $data = @unserialize((string) file_get_contents($file), ['allowed_classes' => false]);
        if (!is_array($data) || !array_key_exists('expires', $data) || !array_key_exists('value', $data)) {
            return $default;
        }
        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            @unlink($file);
            return $default;
        }
        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $expires = $ttl === 0 ? 0 : time() + ($ttl ?? $this->defaultTtl);
        $payload = serialize(['expires' => $expires, 'value' => $value]);
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }
        return file_put_contents($this->file($key), $payload) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->file($key);
        return is_file($file) ? @unlink($file) : true;
    }

    public function clear(): bool
    {
        if (!is_dir($this->dir)) {
            return true;
        }
        $ok = true;
        foreach (glob($this->dir . '/*.cache') ?: [] as $file) {
            if (!@unlink($file)) {
                $ok = false;
            }
        }
        return $ok;
    }

    private function file(string $key): string
    {
        return $this->dir . '/' . hash('sha256', $key) . '.cache';
    }
}
