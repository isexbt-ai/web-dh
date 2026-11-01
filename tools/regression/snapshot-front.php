<?php

declare(strict_types=1);

/**
 * 前台页面视觉回归快照工具。
 *
 * 用法：
 *   php tools/regression/snapshot-front.php <输出目录>                    生成快照
 *   php tools/regression/snapshot-front.php <输出目录> --compare <基准目录>  对比差异
 *
 * 快照内容：各前台页面渲染后的 HTML（去掉 CSRF token、资源 hash、时间戳等易变部分）。
 * 用途：改动模板/CSS 后用它验证「页面结构未发生意外变化」。
 */

require __DIR__ . '/../../vendor/autoload.php';

App\Support\Env::load(base_path('.env'));
App\Support\Config::load(base_path('config'));

$container = App\Container::build();
$view = $container->get(App\Service\View::class);
$seo = $container->get(App\Service\SeoService::class);

// ===== 用真实数据库数据渲染，覆盖真实渲染路径 =====
$cardModel = $container->get(App\Model\Card::class);
$catModel = $container->get(App\Model\Category::class);
$articleModel = $container->get(App\Model\Article::class);
$adModel = $container->get(App\Model\Ad::class);
$noticeModel = $container->get(App\Model\Notice::class);
$showcaseModel = $container->get(App\Model\Showcase::class);
$galleryModel = $container->get(App\Model\Gallery::class);
$messageModel = $container->get(App\Model\Message::class);
$settings = $container->get(App\Service\SettingService::class);

$categories = $catModel->getAll(true);
$categoryCards = [];
foreach ($categories as $cat) {
    $categoryCards[] = [
        'id' => (int) $cat['id'],
        'name' => (string) $cat['name'],
        'cards' => $cardModel->getByCategory((int) $cat['id'], true, 'default'),
    ];
}
$firstCard = $cardModel->getAllActive('default')[0] ?? null;

/** 需要快照的页面（使用真实数据）。 */
$pages = [
    'home' => ['front/home', [
        'siteTitle' => (string) $settings->get('site_title', '快照站'),
        'siteSubtitle' => (string) $settings->get('site_subtitle', ''),
        'ads' => $adModel->getAll(true),
        'categoryCards' => $categoryCards,
        'notices' => $noticeModel->getAll(true),
        'visitorCount' => 0,
        'guestbookEnabled' => true,
        'wideLayout' => true,
    ]],
    'articles' => ['front/articles', ['articles' => $articleModel->getAllActive(0, 20), 'page' => 1, 'pages' => 1]],
    'category' => ['front/category', [
        'category' => $categories[0] ?? ['name' => '快照分类'],
        'cards' => $cardModel->getByCategory((int) ($categories[0]['id'] ?? 0), true, 'default'),
    ]],
    'card' => ['front/card', ['card' => $firstCard ?? ['id'=>1,'title'=>'快照卡片','detail'=>'','image'=>'','image_width'=>0,'image_height'=>0,'card_type'=>'link','link'=>'#','badge_text'=>'']]],
    'guestbook' => ['front/guestbook', [
        'messages' => $messageModel->getApproved(0, 10),
        'page' => 1, 'pages' => 1,
        'guestbook' => ['title'=>'留言','subtitle'=>'','notice'=>'','image'=>''],
    ]],
    'showcase' => ['front/showcase', [
        'showcases' => $showcaseModel->getAll(true),
        'galleries' => $galleryModel->getAll(true),
        'currentGalleryId' => 0,
    ]],
];

/** 去掉易变内容，保证快照可比对。 */
function normalize(string $html): string
{
    // CSRF token、访客数、时间戳
    $html = preg_replace('/content="[a-f0-9]{32}"/', 'content="TOKEN"', $html);
    $html = preg_replace('/第\s*<!--VISITOR_COUNT-->\s*位访客/', '第 <!--VISITOR_COUNT--> 位访客', $html);
    $html = preg_replace('/\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', 'TIMESTAMP', $html);
    // 资源版本 hash（内容变化即变），对比时忽略
    $html = preg_replace('/\?v=[a-f0-9]{6}/', '?v=HASH', $html);
    // 折叠空白，消除缩进差异
    $html = preg_replace('/\n\s*\n+/', "\n", $html);
    $html = preg_replace('/^[ \t]+/m', '', $html);
    return trim($html);
}

$outDir = $argv[1] ?? '';
$compareDir = null;
if (($argv[2] ?? '') === '--compare') {
    $compareDir = $argv[3] ?? '';
}
if ($outDir === '') {
    fwrite(STDERR, "用法: php tools/_refactor/snapshot.php <目录> [--compare <基准目录>]\n");
    exit(1);
}

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$report = [];
foreach ($pages as $name => [$tpl, $data]) {
    $data['siteTitle'] = $data['siteTitle'] ?? '快照站';
    $data['siteSubtitle'] = $data['siteSubtitle'] ?? '';
    $data['umami'] = ['enabled' => false, 'script_url' => '', 'website_id' => ''];
    $data['theme'] = 'default';
    $data['bgImage'] = '';
    $data['bgOverlay'] = '35';
    $data['title'] = '快照';
    $data['description'] = '快照';
    $data['canonical'] = '';
    $data['og'] = [];
    $data['jsonld'] = [];
    $data['content'] = $view->partial($tpl, $data);
    $html = normalize($view->partial('layouts/front', $data));
    file_put_contents($outDir . '/' . $name . '.html', $html);
    $report[$name] = strlen($html);
}

echo "快照已生成到 {$outDir}\n";
foreach ($report as $n => $len) {
    printf("  %-12s %6d 字节\n", $n, $len);
}

if ($compareDir !== null) {
    echo "\n=== 与基准对比 ===\n";
    $diffCount = 0;
    foreach ($pages as $name => $_) {
        $baseFile = $compareDir . '/' . $name . '.html';
        $newFile = $outDir . '/' . $name . '.html';
        if (!is_file($baseFile)) {
            echo "  {$name}: 基准缺失，跳过\n";
            continue;
        }
        $base = file_get_contents($baseFile);
        $new = file_get_contents($newFile);
        if ($base === $new) {
            echo "  {$name}: ✅ 完全一致\n";
            continue;
        }
        // 逐行对比
        $b = explode("\n", $base);
        $n2 = explode("\n", $new);
        $onlyB = array_diff($b, $n2);
        $onlyN = array_diff($n2, $b);
        echo "  {$name}: ⚠ 有差异\n";
        foreach (array_slice($onlyB, 0, 12) as $l) {
            echo "     - " . substr(trim($l), 0, 110) . "\n";
        }
        foreach (array_slice($onlyN, 0, 12) as $l) {
            echo "     + " . substr(trim($l), 0, 110) . "\n";
        }
        $diffCount++;
    }
    echo $diffCount === 0 ? "\n全部页面一致 ✅\n" : "\n{$diffCount} 个页面有差异\n";
}
