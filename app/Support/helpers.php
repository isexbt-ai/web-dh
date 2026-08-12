<?php

declare(strict_types=1);

use App\Support\Config;
use App\Support\Context;

if (!function_exists('e')) {
    /** HTML 转义输出。 */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    /** 读取点号路径配置。 */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('ip')) {
    /** 当前请求 IP。 */
    function ip(): string
    {
        return Context::ip();
    }
}

if (!function_exists('asset')) {
    /**
     * 生成静态资源 URL：读取 public/assets/manifest.json，
     * 返回压缩产物 + 内容 hash 版本号；未构建时回退到未压缩源路径。
     */
    function asset(string $key): string
    {
        static $manifest = null;
        if ($manifest === null) {
            $file = dirname(__DIR__, 2) . '/public/assets/manifest.json';
            $manifest = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }

        // 已构建：直接用 manifest 映射
        if (isset($manifest[$key])) {
            return $manifest[$key];
        }

        // 未构建：回退到未压缩源路径（P1 阶段可用）
        static $fallbacks = [
            'style.css' => '/assets/css/style.css',
            'main.js' => '/assets/js/main.js',
            'admin.css' => '/assets/css/admin.css',
            'admin.js' => '/assets/js/admin.js',
            'pages.css' => '/assets/css/pages.css',
        ];
        return $fallbacks[$key] ?? '/' . $key;
    }
}

if (!function_exists('storage_path')) {
    /** 存储目录绝对路径。 */
    function storage_path(string $sub = ''): string
    {
        return dirname(__DIR__, 2) . '/storage' . ($sub !== '' ? '/' . $sub : '');
    }
}

if (!function_exists('base_path')) {
    /** 项目根目录绝对路径。 */
    function base_path(string $sub = ''): string
    {
        return dirname(__DIR__, 2) . ($sub !== '' ? '/' . $sub : '');
    }
}

if (!function_exists('public_path')) {
    /** Web 根目录绝对路径。 */
    function public_path(string $sub = ''): string
    {
        return dirname(__DIR__, 2) . '/public' . ($sub !== '' ? '/' . $sub : '');
    }
}

if (!function_exists('str_slug')) {
    /** 中文标题转 URL 友好 slug：保留字母数字，其余转短横线。 */
    function str_slug(string $text, int $max = 50): string
    {
        $text = trim($text);
        // 若含中文，回退为小写 ASCII + 截断（不强制拼音化）
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $text) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'item';
        }
        return substr(strtolower($slug), 0, $max);
    }
}

if (!function_exists('image_url')) {
    /**
     * 图片地址规范化：绝对 URL（http/data:/）/绝对路径（/开头）原样返回，
     * 相对路径（uploads/...）补 / 前缀，避免子页面相对解析错误。
     */
    function image_url(string $path): string
    {
        if ($path === '') {
            return '';
        }
        return preg_match('/^(https?:|data:|blob:|\/)/i', $path) ? $path : '/' . ltrim($path, '/');
    }
}
