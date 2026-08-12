<?php

declare(strict_types=1);

return [
    // 本地上传目录（相对站点根，与生产路径保持一致）
    'upload_dir' => dirname(__DIR__) . '/uploads',
    'upload_url_base' => '/uploads',

    // Cloudflare R2（生产配置从 .env 读取，禁止硬编码）
    'r2' => [
        'enabled' => (bool) ($_ENV['R2_ENABLED'] ?? false),
        'account_id' => $_ENV['R2_ACCOUNT_ID'] ?? '',
        'access_key_id' => $_ENV['R2_ACCESS_KEY_ID'] ?? '',
        'secret_access_key' => $_ENV['R2_SECRET_ACCESS_KEY'] ?? '',
        'bucket' => $_ENV['R2_BUCKET'] ?? '',
        'public_url' => $_ENV['R2_PUBLIC_URL'] ?? '',
    ],
];
