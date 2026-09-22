<?php

declare(strict_types=1);

/**
 * 前端构建脚本：minify 压缩 resources 资源到 public/assets，生成 manifest.json（内容 hash 版本号）。
 * 自动扫描 themes/*.css；非默认主题会生成独立 manifest 项供前台按需加载。
 * 用法: composer build（或 php build.php）；产物随 git 入库，服务器零构建。
 */

require __DIR__ . '/vendor/autoload.php';

use MatthiasMullie\Minify;

// 基础资源
$assets = [
    'style.css' => [__DIR__ . '/resources/css/style.css', __DIR__ . '/public/assets/css/style.min.css', 'css'],
    'pages.css' => [__DIR__ . '/resources/css/pages.css', __DIR__ . '/public/assets/css/pages.min.css', 'css'],
    'admin.css' => [__DIR__ . '/resources/css/admin.css', __DIR__ . '/public/assets/css/admin.min.css', 'css'],
    'main.js' => [__DIR__ . '/resources/js/main.js', __DIR__ . '/public/assets/js/main.min.js', 'js'],
    'admin.js' => [__DIR__ . '/resources/js/admin.js', __DIR__ . '/public/assets/js/admin.min.js', 'js'],
];

// 自动扫描 themes/*.css（默认主题用 :root，不需要文件）
$themesDir = __DIR__ . '/resources/css/themes';
$themesOutDir = __DIR__ . '/public/assets/css/themes';
if (is_dir($themesDir)) {
    foreach (glob($themesDir . '/*.css') ?: [] as $themeSrc) {
        $name = pathinfo($themeSrc, PATHINFO_FILENAME);
        $themeKey = 'theme-' . $name;
        $themeDst = $themesOutDir . '/' . $name . '.min.css';
        $assets[$themeKey] = [$themeSrc, $themeDst, 'css'];
    }
}

$manifest = [];
$failed = false;

foreach ($assets as $key => [$src, $dst, $type]) {
    if (!is_file($src)) {
        echo "❌ 源文件不存在: {$src}\n";
        $failed = true;
        continue;
    }
    $minifier = $type === 'css' ? new Minify\CSS($src) : new Minify\JS($src);
    $minified = $minifier->minify();

    if (!is_dir(dirname($dst))) {
        mkdir(dirname($dst), 0755, true);
    }
    file_put_contents($dst, $minified);

    $hash = substr(hash('sha256', $minified), 0, 6);
    $rel = str_replace(__DIR__ . '/public/', '', $dst);
    $url = '/' . ltrim($rel, '/') . '?v=' . $hash;
    $manifest[$key] = $url;
    echo "✅ {$key} -> {$url} (" . number_format(strlen($minified)) . " B)\n";
}

if ($failed) {
    echo "\n构建失败，存在缺失源文件\n";
    exit(1);
}

file_put_contents(
    __DIR__ . '/public/assets/manifest.json',
    (string) json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
);

echo "\n🎉 构建完成，manifest.json 已生成\n";
echo "产物（.min + manifest.json）已入库，部署 git pull 即生效\n";