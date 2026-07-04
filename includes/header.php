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
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDesc); ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#e94560">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <?php if ($extraHead) echo $extraHead; ?>
    <?php if (getConfig('umami_enabled', '1') === '1'): ?>
    <script defer src="<?php echo e(getConfig('umami_script_url', 'https://umami.xldh.cc/script.js')); ?>" data-website-id="<?php echo e(getConfig('umami_website_id', 'd1d35aa8-18e3-4c74-8db4-bcb610de22b5')); ?>"></script>
    <?php endif; ?>
</head>
<body>
<?php
}
