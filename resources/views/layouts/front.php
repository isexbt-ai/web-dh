<?php
/**
 * 前台主布局。
 * 数据：title/description/canonical/og(数组)/jsonld(数组)/content/siteTitle/showTopBar
 */
$siteTitle = $siteTitle ?? (string) config('seo.site_title');
$pageTitle = $title ?? $siteTitle;
$pageDesc = $description ?? (string) config('seo.site_description');
$canonical = $canonical ?? '';
$og = $og ?? [];
$jsonld = $jsonld ?? [];
$umami = $umami ?? ['enabled' => false, 'script_url' => '', 'website_id' => ''];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="theme-color" content="#e94560">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <?php if ($canonical !== ''): ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('pages.css')) ?>">
    <link rel="manifest" href="/manifest.json">
    <?php foreach ($og as $key => $value): ?>
    <meta property="<?= e($key) ?>" content="<?= e($value) ?>">
    <?php endforeach; ?>
    <?php foreach ($jsonld as $block): ?>
    <script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endforeach; ?>
    <?php if (!empty($umami['enabled']) && $umami['script_url'] !== ''): ?>
    <script defer src="<?= e($umami['script_url']) ?>" data-website-id="<?= e($umami['website_id']) ?>"></script>
    <?php endif; ?>
</head>
<body>
    <?php if (!empty($showTopBar)): ?>
    <header class="top-bar">
        <div class="header-left">
            <a href="/" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#333;font-size:14px;font-weight:500;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#e94560" stroke-width="2" style="width:20px;height:20px;">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>返回首页</span>
            </a>
        </div>
        <div class="header-right" style="font-size:14px;color:#999;"><?= e($siteTitle) ?></div>
    </header>
    <?php endif; ?>

    <div class="page-wrapper"><?= $content ?? '' ?></div>

    <footer class="site-footer">
        <p>© <?= date('Y') ?> <?= e($siteTitle) ?></p>
        <p style="margin-top:8px;">
            <a href="/articles" style="color:#ddd;text-decoration:none;margin:0 10px;">精选文章</a>
            <a href="/showcase" style="color:#ddd;text-decoration:none;margin:0 10px;">效果展示</a>
            <a href="/guestbook" style="color:#ddd;text-decoration:none;margin:0 10px;">留言板</a>
        </p>
    </footer>

    <script src="<?= e(asset('main.js')) ?>" defer></script>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    }
    </script>
</body>
</html>
