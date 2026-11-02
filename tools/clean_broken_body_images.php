<?php

declare(strict_types=1);

/**
 * 清理文章正文中的失效图片引用（指向 uploads/ 下已不存在的文件）。
 *
 * 背景：历史脚本在 R2 上传成功后因 url() 缺协议而回退存本地路径，
 *      而本地副本后续丢失，导致 3 篇文章正文出现 57 张 404 坏图。
 *      本脚本只移除这些坏图的 <img> 标签，不改动其它内容。
 *
 * 用法：
 *   php tools/clean_broken_body_images.php --dry-run   仅统计，不写库
 *   php tools/clean_broken_body_images.php             执行清理
 */

require __DIR__ . '/../vendor/autoload.php';

App\Support\Env::load(base_path('.env'));
App\Support\Config::load(base_path('config'));

$dryRun = in_array('--dry-run', $argv, true);

$pdo = new PDO('sqlite:' . (string) config('database.path'));
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$uploadDir = rtrim((string) config('storage.upload_dir'), '/');
$rows = $pdo->query('SELECT id, content FROM articles')->fetchAll();

$totalRemoved = 0;
$changed = 0;

foreach ($rows as $row) {
    $content = (string) $row['content'];
    if ($content === '') {
        continue;
    }

    $removed = 0;
    $newContent = preg_replace_callback(
        '/<img\s+[^>]*src="([^"]+)"[^>]*>/i',
        static function (array $m) use ($uploadDir, &$removed): string {
            $url = $m[1];
            // 仅处理站内 uploads 路径
            if (!preg_match('#^/?uploads/([^\s"]+)$#', $url, $um)) {
                return $m[0];
            }
            if (is_file($uploadDir . '/' . $um[1])) {
                return $m[0];
            }
            $removed++;
            return '';
        },
        $content
    ) ?? $content;

    if ($removed === 0) {
        continue;
    }
    $totalRemoved += $removed;
    $changed++;
    echo '文章 #' . (int) $row['id'] . " 移除坏图 {$removed} 张\n";

    if (!$dryRun) {
        $stmt = $pdo->prepare('UPDATE articles SET content = ? WHERE id = ?');
        $stmt->execute([$newContent, (int) $row['id']]);
    }
}

echo "\n共处理 {$changed} 篇文章，移除 {$totalRemoved} 张失效图片\n";
echo $dryRun ? "（dry-run，未写入数据库）\n" : "已写入数据库\n";
