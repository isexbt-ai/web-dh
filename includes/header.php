<?php
/**
 * 前台公共头部模板
 *
 * 用法：
 *   <?php renderPageHeader($title, $description, $extraHead = ''); ?>
 *
 * @param string $title       页面标题（不含站点名）
 * @param string $description 页面描述
 * @param string $extraHead   额外的 <head> 内容（如 Open Graph、自定义样式等）
 * @return void 直接输出 HTML
 */
function renderPageHeader($title = '', $description = '', $extraHead = '') {
    $siteTitle = getConfig('site_title', '美女导航');
    $pageTitle = $title ? $title . ' - ' . $siteTitle : $siteTitle;
    $pageDesc = $description ?: getConfig('site_description', '精选美女导航网站');
    $currentUrl = getCurrentUrl();
    $theme = getConfig('theme', 'default');
    $themeColor = $theme === 'memphis' ? '#ffe14d' : ($theme === 'dreamy' ? '#ffd6e7' : '#e94560');
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDesc); ?>">
    <meta name="keywords" content="<?php echo e(getConfig('site_keywords', '美女导航,网站导航,精选网站')); ?>">
    <link rel="canonical" href="<?php echo e($currentUrl); ?>">
    <link rel="icon" type="image/png" href="/assets/images/logo.png">
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:description" content="<?php echo e($pageDesc); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e($currentUrl); ?>">
    <meta property="og:site_name" content="<?php echo e($siteTitle); ?>">
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="<?php echo e($themeColor); ?>">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">
    <!-- RSS Feed -->
    <link rel="alternate" type="application/rss+xml" title="<?php echo e($siteTitle); ?> RSS" href="/feed.php">
    <!-- 预加载关键资源 -->
    <link rel="preload" href="/assets/css/style.min.css?v=<?php echo filemtime('assets/css/style.min.css'); ?>" as="style">
    <link rel="dns-prefetch" href="//umami.xldh.cc">
    <!-- 样式 -->
    <link rel="stylesheet" href="/assets/css/style.min.css?v=<?php echo filemtime('assets/css/style.min.css'); ?>">
    <?php if ($theme === 'memphis' && file_exists('assets/css/theme-memphis.css')): ?>
    <link rel="stylesheet" href="/assets/css/theme-memphis.css?v=<?php echo filemtime('assets/css/theme-memphis.css'); ?>">
    <?php elseif ($theme === 'dreamy' && file_exists('assets/css/theme-dreamy.css')): ?>
    <link rel="stylesheet" href="/assets/css/theme-dreamy.css?v=<?php echo filemtime('assets/css/theme-dreamy.css'); ?>">
    <?php endif; ?>
    <!-- 自定义头部代码（从后台配置读取） -->
    <?php
    $customHeaderCode = getConfig('custom_header_code', '');
    if ($customHeaderCode) {
        echo $customHeaderCode;
    }
    ?>
    <!-- Umami 统计 -->
    <?php if (getConfig('umami_enabled', '1') === '1'): ?>
    <script defer src="<?php echo e(getConfig('umami_script_url', 'https://umami.xldh.cc/script.js')); ?>" data-website-id="<?php echo e(getConfig('umami_website_id', 'd1d35aa8-18e3-4c74-8db4-bcb610de22b5')); ?>"></script>
    <?php endif; ?>
    <?php if ($extraHead) echo $extraHead; ?>
</head>
<body>
<?php
}
?>
