<?php
/**
 * 卡片详情页内容模板。
 * 数据：card
 */
$card = $card ?? [];
?>
<div class="detail-container">
    <div class="detail-card">
        <?php if (!empty($card['image'])): ?>
        <div class="detail-image">
            <img src="<?= e($card['image']) ?>" alt="<?= e($card['title']) ?>" loading="eager"
                 width="<?= (int) ($card['image_width'] ?? 0) ?>" height="<?= (int) ($card['image_height'] ?? 0) ?>">
        </div>
        <?php else: ?>
        <div class="detail-image-placeholder"><span><?= e($card['title']) ?></span></div>
        <?php endif; ?>

        <div class="detail-content">
            <div class="detail-breadcrumb">
                <a href="/">首页</a>
                <span>/</span>
                <span><?= e($card['category_name'] ?? '未分类') ?></span>
                <span>/</span>
                <span><?= e($card['title']) ?></span>
            </div>

            <h1 class="detail-title"><?= e($card['title']) ?></h1>

            <div class="detail-meta">
                <div class="detail-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span><?= (int) ($card['click_count'] ?? 0) ?> 次浏览</span>
                </div>
                <div class="detail-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span><?= e(date('Y-m-d', strtotime((string) ($card['created_at'] ?? 'now')))) ?></span>
                </div>
            </div>

            <div class="detail-body">
                <?php if (!empty($card['detail'])): ?>
                    <?= $__view->partial('partials/rich-text', ['raw' => $card['detail']]) ?>
                <?php else: ?>
                    <div class="detail-empty">暂无详细介绍</div>
                <?php endif; ?>
            </div>

            <div class="detail-actions">
                <a href="/" class="detail-btn detail-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    返回首页
                </a>
                <?php if (!empty($card['link']) && $card['link'] !== '#'): ?>
                <a href="<?= e($card['link']) ?>" target="_blank" rel="noopener nofollow" class="detail-btn detail-btn-primary">访问链接</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
