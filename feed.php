<?php
/**
 * RSS Feed - 输出最新卡片的 RSS 2.0 XML
 */
require_once 'includes/functions.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$siteUrl = 'https://' . $_SERVER['HTTP_HOST'];
$siteTitle = getConfig('site_title', '美女导航');
$siteDesc = getConfig('site_description', '精选美女导航网站');

// 获取最新20条卡片
$cards = getCards(null, true, 'default');
$cards = array_slice($cards, 0, 20);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?php echo e($siteTitle); ?></title>
        <description><?php echo e($siteDesc); ?></description>
        <link><?php echo $siteUrl; ?>/</link>
        <atom:link href="<?php echo $siteUrl; ?>/feed.php" rel="self" type="application/rss+xml"/>
        <language>zh-CN</language>
        <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
        <?php foreach ($cards as $card): ?>
        <item>
            <title><?php echo e($card['title']); ?></title>
            <link><?php echo $siteUrl; ?>/detail/<?php echo $card['id']; ?>.html</link>
            <guid isPermaLink="true"><?php echo $siteUrl; ?>/detail/<?php echo $card['id']; ?>.html</guid>
            <pubDate><?php echo date('r', strtotime($card['created_at'])); ?></pubDate>
            <?php if (!empty($card['detail'])): ?>
            <description><?php echo e(smartTruncate($card['detail'], 300)); ?></description>
            <?php endif; ?>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
