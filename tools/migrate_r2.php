<?php

declare(strict_types=1);

/**
 * 迁移本地图片到 R2（重构版）
 * 用法: php tools/migrate_r2.php
 *
 * R2 密钥从 .env 读取（config/storage.r2），PDO 预处理更新数据库。
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Service\R2Service;
use App\Support\Config;
use App\Support\Env;
use PDO;

Env::load(base_path('.env'));
Config::load(base_path('config'));

$r2 = new R2Service((array) config('storage.r2', []));
if (!$r2->enabled()) {
    echo "❌ R2 未启用（请检查 .env 中 R2_ENABLED/R2_* 配置）\n";
    exit(1);
}

$pdo = new PDO('sqlite:' . (string) config('database.path'));
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$migrated = 0;
$skipped = 0;
$failed = 0;

echo "=== 迁移本地图片到 R2 ===\n\n";

$rows = $pdo->query('SELECT id, title, content, cover_image FROM articles ORDER BY id')->fetchAll();

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $title = mb_substr((string) $row['title'], 0, 40);
    $content = (string) $row['content'];
    $coverImage = (string) $row['cover_image'];

    // 已迁移（封面是 R2 URL）
    if (str_starts_with($coverImage, 'http')) {
        echo "⏭️  ID={$id} 已在 R2，跳过\n";
        $skipped++;
        continue;
    }

    echo "📄 ID={$id} {$title}...\n";

    $localImages = [];
    if (preg_match_all('/src="(uploads\/articles\/[^"]+)"/', $content, $matches)) {
        $localImages = array_unique($matches[1]);
    }
    if ($localImages === []) {
        echo "   ⚠️  无本地图片\n";
        $skipped++;
        continue;
    }

    echo "   图片: " . count($localImages) . " 张\n";

    $newContent = $content;
    $newCover = $coverImage;
    $uploaded = 0;

    foreach ($localImages as $localPath) {
        $localFile = base_path() . '/' . $localPath;
        if (!is_file($localFile)) {
            echo "   ❌ 文件不存在: {$localPath}\n";
            continue;
        }

        $filename = basename($localPath);
        $r2Key = 'articles/' . $filename;
        $url = $r2->upload($localFile, $r2Key, mime_content_type($localFile) ?: 'image/jpeg');

        if ($url !== null) {
            $newContent = str_replace('src="' . $localPath . '"', 'src="' . $url . '"', $newContent);
            if ($localPath === $coverImage) {
                $newCover = $url;
            }
            $uploaded++;
        } else {
            echo "   ❌ 上传失败 {$filename}\n";
        }
    }

    if ($uploaded > 0) {
        $stmt = $pdo->prepare('UPDATE articles SET content = ?, cover_image = ? WHERE id = ?');
        $stmt->execute([$newContent, $newCover, $id]);
        echo "   ✅ 迁移完成: {$uploaded} 张上传到 R2\n";
        $migrated++;
    } else {
        echo "   ❌ 迁移失败\n";
        $failed++;
    }
}

echo "\n=== 完成 ===\n";
echo "已迁移: {$migrated}\n";
echo "已跳过: {$skipped}\n";
echo "失败: {$failed}\n";
