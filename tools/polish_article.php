<?php

declare(strict_types=1);

/**
 * 一键润色文章脚本（重构版）
 * 用法: php tools/polish_article.php <article_id> [简短标题] [简介文件路径]
 *
 * 从 .env / config 读取数据库路径，PDO 预处理，无硬编码。
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Config;
use App\Support\Env;
use PDO;

Env::load(base_path('.env'));
Config::load(base_path('config'));

if ($argc < 2) {
    echo "用法: php tools/polish_article.php <article_id> [简短标题] [简介文件路径]\n";
    exit(1);
}

$articleId = (int) $argv[1];
$titleShort = $argv[2] ?? '';
$introFile = $argv[3] ?? '';

$pdo = new PDO('sqlite:' . (string) config('database.path'));
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT id, title, content, cover_image FROM articles WHERE id = ?');
$stmt->execute([$articleId]);
$row = $stmt->fetch();

if ($row === false) {
    echo "错误: 文章 ID={$articleId} 不存在\n";
    exit(1);
}

$originalTitle = (string) $row['title'];
$originalContent = (string) $row['content'];

if ($titleShort === '') {
    $titleShort = mb_substr($originalTitle, 0, 30);
    if (mb_strlen($originalTitle) > 30) {
        $titleShort .= '...';
    }
}

// 匹配本地路径或 R2 URL 图片
$images = [];
if (preg_match_all('/src="((?:uploads\/articles\/|https:\/\/[^"]+\/articles\/)[^"]+)"/', $originalContent, $matches)) {
    $images = $matches[1];
}

$resourceInfo = '';
foreach ([$originalTitle, $originalContent] as $haystack) {
    if (preg_match('/(\d+P\s*\d+V\s*\d+[MG])/', $haystack, $m)) {
        $resourceInfo = $m[1];
        break;
    }
}

$introduction = '';
if ($introFile !== '' && is_file($introFile)) {
    $introduction = trim((string) file_get_contents($introFile));
}

$html = "<h2>资源信息</h2>\n";
$html .= "<p><strong>内容名称：</strong>{$titleShort}</p>\n";
if ($resourceInfo !== '') {
    $html .= "<p><strong>资源数量：</strong>{$resourceInfo}</p>\n";
}
$html .= "<p><strong>存储网盘：</strong>夸克网盘</p>\n";

if ($introduction !== '') {
    $html .= "<h2>内容介绍</h2>\n";
    $html .= "<p>{$introduction}</p>\n";
}

$html .= "<h2>精彩预览</h2>\n";
foreach ($images as $i => $img) {
    $html .= "<p><img src=\"{$img}\" alt=\"预览图" . ($i + 1) . "\"></p>\n";
}

$html .= "<h2>下载说明</h2>\n";
$html .= "<p>资源为夸克网盘分享，请自行转存或下载。</p>\n";
$html .= "<p style=\"color: #e94560; font-weight: bold;\">温馨提示：资源仅供学习交流，请于下载后24小时内删除。</p>\n";

$stmt = $pdo->prepare('UPDATE articles SET content = ? WHERE id = ?');
$stmt->execute([$html, $articleId]);

echo "✅ 文章 ID={$articleId} 润色完成\n";
echo "📄 标题: {$titleShort}\n";
echo "🖼️  图片: " . count($images) . " 张\n";
echo "📝 简介: " . ($introduction === '' ? '未提供（需要补充）' : mb_strlen($introduction) . '字') . "\n";
echo "🔗 访问: /article/{$articleId}-xxx.html\n";

if ($introduction === '') {
    echo "\n⚠️  缺少内容介绍，请生成200字简介后执行:\n";
    echo "   php tools/polish_article.php {$articleId} \"{$titleShort}\" /tmp/intro_{$articleId}.txt\n";
}
