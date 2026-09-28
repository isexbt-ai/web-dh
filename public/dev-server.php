<?php

declare(strict_types=1);

/**
 * 本地开发服务器 router 脚本（仅开发用，生产由 nginx try_files 处理）。
 * 用法: php -S 127.0.0.1:8080 public/dev-server.php
 * 静态文件直接由内置服务器返回，其余请求转发到 Slim。
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// /uploads/ 在 public/ 之外（项目根目录），需单独映射，对齐生产 nginx 的 location ^~ /uploads/ alias
if (str_starts_with($path, '/uploads/')) {
    $uploadFile = dirname(__DIR__) . $path;
    if (is_file($uploadFile)) {
        $ext = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');

        $size = filesize($uploadFile);
        $start = 0;
        $end = $size - 1;

        // 视频拖动进度条依赖 Range 请求（206 Partial Content）
        $range = $_SERVER['HTTP_RANGE'] ?? '';
        if ($range !== '' && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
            $s = $m[1] === '' ? null : (int) $m[1];
            $e = $m[2] === '' ? null : (int) $m[2];
            if ($s === null && $e !== null) {           // bytes=-N 取末尾 N 字节
                $start = max(0, $size - $e);
                $end = $size - 1;
            } else {
                $start = $s ?? 0;
                $end = $e !== null ? min($e, $size - 1) : $size - 1;
            }
            if ($start > $end || $start >= $size) {
                http_response_code(416);
                header('Content-Range: bytes */' . $size);
                return true;
            }
            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        }

        header('Content-Length: ' . ($end - $start + 1));
        $fp = fopen($uploadFile, 'rb');
        if ($fp !== false) {
            fseek($fp, $start);
            $remaining = $end - $start + 1;
            while ($remaining > 0 && !feof($fp)) {
                $chunk = fread($fp, (int) min(8192, $remaining));
                if ($chunk === false) {
                    break;
                }
                echo $chunk;
                $remaining -= strlen($chunk);
            }
            fclose($fp);
        }
        return true;
    }
    http_response_code(404);
    echo 'Not Found';
    return true;
}

$file = __DIR__ . $path;

if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false; // 让 PHP 内置服务器按静态文件处理
}

require __DIR__ . '/index.php';
