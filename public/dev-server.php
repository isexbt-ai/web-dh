<?php

declare(strict_types=1);

/**
 * 本地开发服务器 router 脚本（仅开发用，生产由 nginx try_files 处理）。
 * 用法: php -S 127.0.0.1:8080 public/dev-server.php
 * 静态文件直接由内置服务器返回，其余请求转发到 Slim。
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false; // 让 PHP 内置服务器按静态文件处理
}

require __DIR__ . '/index.php';
