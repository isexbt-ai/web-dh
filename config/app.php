<?php

declare(strict_types=1);

return [
    'name' => '美女导航',
    'timezone' => 'Asia/Shanghai',
    'debug' => (bool) ($_ENV['APP_DEBUG'] ?? false),

    // 上传
    'upload_max_size' => 50 * 1024 * 1024,
    'thumbnail_width' => 300,
    'thumbnail_height' => 300,

    // 缓存
    'cache_dir' => dirname(__DIR__) . '/storage/cache',
    'cache_ttl' => 300,
    'cache_ttl_short' => 60,
    'cache_ttl_long' => 3600,

    // 会话
    'session_timeout' => 1800,
    'login_lock_attempts' => 5,
    'login_lock_duration' => 900,

    // 分页
    'per_page' => 12,
    'guestbook_per_page' => 10,

    // 全站访问统计（访客数展示）
    'visitor_display_days' => 30,
];
