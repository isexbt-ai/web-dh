<?php
/**
 * 文章列表页内容模板。
 * 数据：articles/page/pages
 */
$articles = $articles ?? [];
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
?>
<div class="articles-container">
    <div class="articles-header">
        <h1>文章资讯</h1>
        <p>精选实用技巧与站点动态</p>
    </div>

    <?php if ($articles !== []): ?>
    <div class="article-list">
        <?php foreach ($articles as $a): ?>
        <a class="article-item" href="/article/<?= (int) $a['id'] ?>-<?= e($a['slug'] ?? 'item') ?>.html">
            <div class="article-cover">
                <?php if (!empty($a['cover_image'])): ?>
                <img src="<?= e($a['cover_image']) ?>" alt="<?= e($a['title']) ?>" loading="lazy">
                <?php else: ?>
                <div class="article-cover-placeholder">文章</div>
                <?php endif; ?>
            </div>
            <div class="article-content">
                <h2 class="article-title"><?= e($a['title']) ?></h2>
                <?php if (!empty($a['summary'])): ?>
                <p class="article-summary"><?= e($a['summary']) ?></p>
                <?php endif; ?>
                <span class="article-date"><?= e(date('Y-m-d', strtotime((string) $a['created_at']))) ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">暂无文章</div>
    <?php endif; ?>

    <?= $__view->partial('partials/pagination', ['page' => $page, 'pages' => $pages, 'base' => '/articles']) ?>
</div>
