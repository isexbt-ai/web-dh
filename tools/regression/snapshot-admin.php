<?php

declare(strict_types=1);

/**
 * 后台视图视觉回归快照工具。
 *
 * 用法：
 *   php tools/regression/snapshot-admin.php <输出目录>                    生成快照
 *   php tools/regression/snapshot-admin.php <输出目录> --compare <基准目录>  对比差异
 *
 * 用途：改动后台 CRUD 视图后用它验证「渲染结果未发生意外变化」。
 */

require __DIR__ . '/../../vendor/autoload.php';

App\Support\Env::load(base_path('.env'));
App\Support\Config::load(base_path('config'));

$container = App\Container::build();
$view = $container->get(App\Service\View::class);

$items = [
    ['id' => 1, 'title' => '示例标题', 'image' => '/uploads/ads/x.png', 'link' => 'https://example.com',
     'content' => '示例公告内容', 'url' => 'https://example.com', 'icon' => '★',
     'name' => '示例分类', 'sort_order' => 3, 'is_active' => 1],
    ['id' => 2, 'title' => '第二个', 'image' => '', 'link' => '', 'content' => '第二条内容',
     'url' => '', 'icon' => '', 'name' => '第二类', 'sort_order' => 0, 'is_active' => 0],
];

$views = [
    'admin_ads' => ['admin/ads', ['items' => $items]],
    'admin_links' => ['admin/links', ['items' => $items]],
    'admin_notices' => ['admin/notices', ['items' => $items]],
    'admin_categories' => ['admin/categories', ['items' => $items]],
];

$outDir = $argv[1] ?? '';
$compareDir = ($argv[2] ?? '') === '--compare' ? ($argv[3] ?? '') : null;
if ($outDir === '') {
    fwrite(STDERR, "用法: php tools/_refactor/snapshot_admin.php <目录> [--compare <基准目录>]\n");
    exit(1);
}
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

function normalize(string $html): string
{
    $html = preg_replace('/content="[a-f0-9]{32}"/', 'content="TOKEN"', $html);
    $html = preg_replace('/\?v=[a-f0-9]{6}/', '?v=HASH', $html);
    $html = preg_replace('/\n\s*\n+/', "\n", $html);
    $html = preg_replace('/^[ \t]+/m', '', $html);
    return trim($html);
}

foreach ($views as $name => [$tpl, $data]) {
    $html = normalize($view->partial($tpl, $data));
    file_put_contents($outDir . '/' . $name . '.html', $html);
}
echo "后台快照已生成到 {$outDir}\n";

if ($compareDir !== null) {
    echo "\n=== 与基准对比 ===\n";
    $diff = 0;
    foreach (array_keys($views) as $name) {
        $b = file_get_contents($compareDir . '/' . $name . '.html');
        $n = file_get_contents($outDir . '/' . $name . '.html');
        if ($b === $n) {
            echo "  {$name}: ✅ 完全一致\n";
            continue;
        }
        echo "  {$name}: ⚠ 有差异\n";
        $bl = explode("\n", $b); $nl = explode("\n", $n);
        foreach (array_slice(array_diff($bl, $nl), 0, 8) as $l) { echo "     - " . substr(trim($l), 0, 120) . "\n"; }
        foreach (array_slice(array_diff($nl, $bl), 0, 8) as $l) { echo "     + " . substr(trim($l), 0, 120) . "\n"; }
        $diff++;
    }
    echo $diff === 0 ? "\n后台页面全部一致 ✅\n" : "\n{$diff} 个后台页面有差异\n";
}
