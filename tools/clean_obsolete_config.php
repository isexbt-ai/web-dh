<?php

declare(strict_types=1);

/**
 * 清理已废弃的站点配置项。
 *
 * 背景：站点配置页已移除 umami 统计、虚拟人气加成、搜索引擎主动推送、
 *      卡片排序方式（改为卡片管理内按分类维护 sort_order）。
 *      代码不再读取这些键，残留属于垃圾数据。
 *
 * 用法：php tools/clean_obsolete_config.php [--dry-run]
 */

require __DIR__ . '/../vendor/autoload.php';

App\Support\Env::load(base_path('.env'));
App\Support\Config::load(base_path('config'));

$dryRun = in_array('--dry-run', $argv, true);

$obsolete = [
    'card_sort_method',
    'umami_enabled', 'umami_script_url', 'umami_website_id',
    'visitor_base_offset', 'visitor_daily_increment',
    'baidu_push_token', 'indexnow_key',
];

$pdo = new PDO('sqlite:' . (string) config('database.path'));
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare('DELETE FROM site_config WHERE key = ?');
$removed = 0;
foreach ($obsolete as $key) {
    $check = $pdo->prepare('SELECT COUNT(*) FROM site_config WHERE key = ?');
    $check->execute([$key]);
    $exists = (int) $check->fetchColumn();
    if ($exists === 0) {
        continue;
    }
    if (!$dryRun) {
        $stmt->execute([$key]);
    }
    $removed++;
    echo "  移除配置项: {$key}\n";
}

echo "\n共处理 {$removed} 个废弃配置项\n";
echo $dryRun ? "（dry-run，未写入数据库）\n" : "已写入数据库\n";
