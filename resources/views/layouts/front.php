<?php
/**
 * 前台主布局。
 * 数据：title/description/canonical/og(数组)/jsonld(数组)/content/siteTitle/
 *       wideLayout/bgImage/bgOverlay
 */
$siteTitle = $siteTitle ?? (string) config('seo.site_title');
$pageTitle = $title ?? $siteTitle;
$pageDesc = $description ?? (string) config('seo.site_description');
$canonical = $canonical ?? '';
$og = $og ?? [];
$jsonld = $jsonld ?? [];
$activeTheme = $theme ?? 'default';
$wideLayout = $wideLayout ?? false;
$bgImage = $bgImage ?? '';
$bgOverlay = $bgOverlay ?? '35';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <?php
/** 浏览器地址栏颜色：按主题选用品牌主色，深色主题用页面底色。 */
$themeColorMap = [
    'dark' => '#0f1419',
    'corporate' => '#0066cc',
    'warm' => '#ff6b35',
    'mint' => '#14b8a6',
];
$themeColor = $themeColorMap[$activeTheme] ?? '#e94560';
?>
<meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="theme-color" content="<?= e($themeColor) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <?php if ($canonical !== ''): ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
    <?php if ($activeTheme !== 'default'): ?>
    <link rel="stylesheet" href="<?= e(asset('theme-' . $activeTheme)) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('pages.css')) ?>">
    <link rel="manifest" href="/manifest.json">
    <?php foreach ($og as $key => $value): ?>
    <meta property="<?= e($key) ?>" content="<?= e($value) ?>">
    <?php endforeach; ?>
    <?php foreach ($jsonld as $block): ?>
    <script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endforeach; ?>
</head>
<?php
/** 背景图：仅当配置了合法地址时才启用 body 背景层。 */
$bgCss = bg_image_css((string) $bgImage);
$bodyStyle = '';
$bodyClasses = [];
if (!empty($wideLayout)) {
    $bodyClasses[] = 'has-wide-layout';
}
if ($bgCss !== '') {
    $bodyClasses[] = 'has-site-bg';
    $bodyStyle = '--site-bg-image:' . $bgCss . ';--site-bg-overlay:' . (max(0, min(100, (int) $bgOverlay)) / 100);
}
?>
<body<?= $activeTheme !== 'default' ? ' data-theme="' . e($activeTheme) . '"' : '' ?><?= $bodyClasses !== [] ? ' class="' . e(implode(' ', $bodyClasses)) . '"' : '' ?><?= $bodyStyle !== '' ? ' style="' . e($bodyStyle) . '"' : '' ?>>
    <?php if (empty($wideLayout)): ?>
    <!-- 子页面顶栏：站点名 + 全站搜索 + 导航（首页使用自带的 home-header） -->
    <header class="site-bar">
        <div class="site-bar-inner">
            <a class="site-bar-logo" href="/"><?= e($siteTitle) ?></a>
            <button class="home-search-btn" id="homeSearchBtn" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
                <span>搜索全站…</span>
            </button>
            <nav class="home-nav">
                <a href="/showcase" class="home-nav-btn">效果展示</a>
                <a href="/articles" class="home-nav-btn">精选文章</a>
                <a href="/guestbook" class="home-nav-btn">留言板</a>
            </nav>
        </div>
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

    <!-- 搜索覆盖层（全站搜索：链接 + 文章），全站页面通用 -->
    <div class="search-overlay" id="searchOverlay" hidden>
        <div class="search-panel">
            <div class="search-bar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
                <input type="search" id="searchInput" placeholder="搜索全站链接与文章..." autocomplete="off" maxlength="50">
                <button class="search-close" id="searchClose" type="button" aria-label="关闭">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
                </button>
            </div>
            <p class="search-meta" id="searchMeta"></p>
            <div class="search-results" id="searchResults"></div>
            <p class="search-hint" id="searchHint">按 ESC 关闭 · ↑↓ 选择 · Enter 打开</p>
        </div>
    </div>

    <script src="<?= e(asset('main.js')) ?>" defer></script>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    }
    </script>
</body>
</html>
