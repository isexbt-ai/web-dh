<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 静态配置存储：Bootstrap 时加载 config/*.php 全部配置，支持点号路径读取。
 * 例：config('app.name')、config('database.path')。
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $dir): void
    {
        $files = glob($dir . '/*.php') ?: [];
        foreach ($files as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
