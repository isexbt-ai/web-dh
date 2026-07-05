<?php
require_once 'includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$siteUrl = 'https://' . $_SERVER['HTTP_HOST'];
$cards = getCards(null, true);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo $siteUrl; ?>/</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $siteUrl; ?>/guestbook.html</loc>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?php echo $siteUrl; ?>/showcase.html</loc>
        <priority>0.6</priority>
    </url>
    <?php foreach ($cards as $card): ?>
    <url>
        <loc><?php echo $siteUrl; ?>/detail/<?php echo $card['id']; ?>.html</loc>
        <lastmod><?php echo date('Y-m-d', strtotime($card['created_at'])); ?></lastmod>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
</urlset>