<?php
/**
 * 分类独立页内容模板。
 * 数据：category/cards
 */
$category = $category ?? [];
$cards = $cards ?? [];
?>
<div class="category-page">
    <div class="category-page-header">
        <div class="category-page-breadcrumb">
            <a href="/">首页</a><span>/</span><span><?= e($category['name']) ?></span>
        </div>
        <h1 class="category-page-title"><?= e($category['name']) ?></h1>
    </div>

    <?php if ($cards !== []): ?>
    <div class="card-grid">
        <?php foreach ($cards as $card): ?>
            <?= $__view->partial('partials/card-item', ['card' => $card]) ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">该分类暂无内容</div>
    <?php endif; ?>
</div>
