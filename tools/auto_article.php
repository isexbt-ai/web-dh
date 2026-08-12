<?php

declare(strict_types=1);

/**
 * 自动发布文章脚本（重构版）
 * 用法: php tools/auto_article.php
 *
 * 扫描 sitehtml/ 目录 HTML → 提取标题/正文/图片 → 复制图片到 uploads + 可选 R2 → 入库。
 * R2 密钥从 .env 读取，PDO 预处理，路径基于项目根。
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Service\R2Service;
use App\Support\Config;
use App\Support\Env;
use DOMDocument;
use PDO;

Env::load(base_path('.env'));
Config::load(base_path('config'));

$siteHtmlDir = base_path('sitehtml/');
$uploadDir = base_path('uploads/articles/');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$r2 = new R2Service((array) config('storage.r2', []));

/** 扫描目录内 .html 文件。 */
function getHtmlFiles(string $dir): array
{
    $files = [];
    if (is_dir($dir)) {
        foreach (glob($dir . '*.html') ?: [] as $file) {
            if (is_file($file)) {
                $files[] = $file;
            }
        }
    }
    return $files;
}

/** 从 HTML 提取标题。 */
function extractTitle(string $html, string $filename): string
{
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $titles = $dom->getElementsByTagName('title');
    if ($titles->length > 0) {
        $title = preg_replace('/\s*[-_].*?(司机社|求出处|飙车场).*$/u', '', (string) $titles->item(0)->textContent) ?? '';
        $title = trim($title);
        if (mb_strlen($title) > 3) {
            return $title;
        }
    }
    $title = preg_replace('/\.html$/i', '', basename($filename)) ?? '';
    $title = preg_replace('/【.*?】/u', '', $title) ?? '';
    $title = preg_replace('/\s*[-_].*$/u', '', $title) ?? '';
    return trim($title);
}

/** 提取正文 HTML 块。 */
function extractPostContent(string $html): string
{
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $selectors = [
        ['id', 'postmessage_'],
        ['class', 'postmessage'],
        ['class', 't_f'],
    ];
    foreach ($selectors as [$attr, $needle]) {
        foreach ($dom->getElementsByTagName('*') as $element) {
            if (str_contains((string) $element->getAttribute($attr), $needle)) {
                return (string) $dom->saveHTML($element);
            }
        }
    }
    return '';
}

/** 判断图片是否属于正文内容（过滤头像/图标/占位）。 */
function isContentImage(string $src): bool
{
    if (preg_match('/^\s*(javascript|data|vbscript):/i', $src)) {
        return false;
    }
    if (preg_match('/\/(\d{1,2}|S\d{1,2})\.gif$/i', $src)) {
        return false;
    }
    foreach (['avatar', 'logo', 'icon', 'verysmall', 'none.gif'] as $needle) {
        if (str_contains($src, $needle)) {
            return false;
        }
    }
    return true;
}

/** 提取正文中的图片 src。 */
function extractImages(string $content): array
{
    $images = [];
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
    foreach ($dom->getElementsByTagName('img') as $img) {
        $src = (string) $img->getAttribute('src');
        if ($src === '' || !isContentImage($src)) {
            continue;
        }
        $images[$src] = str_replace('./', '', $src);
    }
    return $images;
}

/** 校验路径在允许目录内。 */
function safePath(string $path, string $allowedDir): string|false
{
    $path = str_replace(['../', '..\\', "\0"], '', $path);
    $real = realpath($path);
    $allowed = realpath($allowedDir);
    if ($real === false || $allowed === false || !str_starts_with($real, $allowed)) {
        return false;
    }
    return $real;
}

/** 中文标题转 slug（保留 Unicode，转小写截断）。 */
function makeSlug(string $title): string
{
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $title) ?? '';
    return strtolower(mb_substr(trim($slug, '-'), 0, 50));
}

/** 复制图片到 uploads（本地 + 可选 R2），返回 [cover, map]。 */
function copyImagesToUploads(array $images, string $sourceDir, string $targetDir, R2Service $r2): array
{
    $map = [];
    $cover = '';
    $index = 0;
    $filesDirs = glob($sourceDir . '*_files/') ?: [];
    $filesDir = $filesDirs[0] ?? $sourceDir;

    foreach ($images as $originalSrc => $cleanPath) {
        $basename = basename($cleanPath);
        foreach ([$sourceDir . $cleanPath, $sourceDir . $basename, $filesDir . $basename] as $p) {
            $safe = safePath($p, $sourceDir);
            if ($safe !== false && is_file($safe)) {
                $sourcePath = $safe;
                break;
            }
        }
        if (!isset($sourcePath)) {
            continue;
        }

        $ext = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        $newName = 'article_' . date('Ymd') . '_' . bin2hex(random_bytes(8)) . '_' . $index . '.' . $ext;
        $targetPath = $targetDir . $newName;

        if (!copy($sourcePath, $targetPath)) {
            continue;
        }
        @chmod($targetPath, 0644);

        $r2Key = 'articles/' . $newName;
        $url = $r2->upload($targetPath, $r2Key, mime_content_type($targetPath) ?: 'image/jpeg');
        $webPath = $url ?? 'uploads/articles/' . $newName;

        $map[$originalSrc] = $webPath;
        if ($cover === '') {
            $cover = $webPath;
        }
        $index++;
        unset($sourcePath);
    }
    return ['cover' => $cover, 'map' => $map];
}

// ==================== 主程序 ====================
echo "=== 自动文章发布（重构版）===\n\n";
echo $r2->enabled() ? "☁️  R2 存储: 已启用\n\n" : "📁 本地存储模式\n\n";

$htmlFiles = getHtmlFiles($siteHtmlDir);
if ($htmlFiles === []) {
    echo "❌ sitehtml/ 目录没有 HTML 文件\n";
    exit(1);
}

$pdo = new PDO('sqlite:' . (string) config('database.path'));
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo '发现 ' . count($htmlFiles) . " 个文件\n\n";
$success = 0;
$fail = 0;

foreach ($htmlFiles as $htmlFile) {
    echo "📄 " . basename($htmlFile) . "\n";

    $html = @file_get_contents($htmlFile);
    if ($html === false || $html === '') {
        echo "   ❌ 文件为空\n\n";
        $fail++;
        continue;
    }

    $title = extractTitle($html, basename($htmlFile));
    if ($title === '') {
        echo "   ❌ 无法提取标题\n\n";
        $fail++;
        continue;
    }
    echo "   标题: {$title}\n";

    $postContent = extractPostContent($html);
    if ($postContent === '') {
        echo "   ❌ 无法提取正文\n\n";
        $fail++;
        continue;
    }

    $images = extractImages($postContent);
    echo "   图片: " . count($images) . " 张\n";

    $coverImage = '';
    $imageMap = [];
    if ($images !== []) {
        $result = copyImagesToUploads($images, $siteHtmlDir, $uploadDir, $r2);
        $coverImage = $result['cover'];
        $imageMap = $result['map'];
        echo '   上传: ' . count($imageMap) . " 张\n";
    }

    $content = $postContent;
    foreach ($imageMap as $originalSrc => $newPath) {
        $content = str_replace('src="' . $originalSrc . '"', 'src="' . $newPath . '"', $content);
        $content = str_replace("src='" . $originalSrc . "'", 'src="' . $newPath . '"', $content);
    }

    $summary = mb_substr((string) preg_replace('/\s+/', ' ', trim(strip_tags($content))), 0, 200);
    $slug = makeSlug($title);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO articles (title, slug, summary, content, cover_image, keywords, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, \'\', 1, datetime(\'now\'), datetime(\'now\'))'
        );
        $stmt->execute([$title, $slug, $summary, $content, $coverImage]);
        echo "   ✅ 入库 ID: " . (int) $pdo->lastInsertId() . "\n";
        $success++;
    } catch (PDOException $e) {
        echo '   ❌ 失败: ' . $e->getMessage() . "\n";
        $fail++;
    }
    echo "\n";
}

echo "=== 完成 ===\n";
echo "成功: {$success}\n";
echo "失败: {$fail}\n";
