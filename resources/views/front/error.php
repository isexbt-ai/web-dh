<?php
/**
 * 错误页模板（404/500 独立完整页，不依赖前台布局）。
 * 数据：status/message/title
 */
$status = $status ?? 404;
$message = $message ?? '页面不存在';
$pageTitle = $title ?? '页面未找到';
$siteTitle = (string) config('seo.site_title');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e($siteTitle) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
</head>
<body>
    <div class="page-wrapper" style="padding-top:80px;">
        <div class="section-card" style="padding:60px 20px;text-align:center;">
            <div style="font-size:64px;font-weight:700;color:#e94560;margin-bottom:16px;"><?= (int) $status ?></div>
            <h1 style="font-size:22px;color:#1a1a2e;margin-bottom:12px;"><?= e($message) ?></h1>
            <p style="font-size:14px;color:#999;margin-bottom:32px;">抱歉，您访问的页面出现了问题</p>
            <a href="/" style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#e94560,#ff6b6b);color:#fff;border-radius:10px;font-size:14px;text-decoration:none;">返回首页</a>
        </div>
    </div>
</body>
</html>
