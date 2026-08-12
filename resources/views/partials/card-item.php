<?php
/**
 * 卡片单项 partial（事件委托，无 inline onclick）。
 * 数据：card
 */
$card = $card ?? [];
$cardType = $card['card_type'] ?? 'link';
$badgeText = $card['badge_text'] ?? '';
if ($badgeText === '') {
    $badgeClass = $cardType === 'detail' ? 'detail' : 'link';
    $badgeText = $cardType === 'detail' ? '详情' : '外链';
} else {
    $badgeClass = 'custom';
}
$imageSrc = image_url((string) ($card['image'] ?? ''));
$link = $card['link'] ?? '#';
?>
<div class="card-item"
     data-card-id="<?= (int) $card['id'] ?>"
     data-card-type="<?= e($cardType) ?>"
     data-link="<?= e($link) ?>">
    <span class="card-type-badge <?= e($badgeClass) ?>"><?= e($badgeText) ?></span>
    <div class="card-image">
        <?php if ($imageSrc !== ''): ?>
        <img src="<?= e($imageSrc) ?>" alt="<?= e($card['title']) ?>" loading="lazy"
             width="<?= (int) ($card['image_width'] ?? 0) ?>" height="<?= (int) ($card['image_height'] ?? 0) ?>">
        <?php else: ?>
        <div class="card-placeholder">图片</div>
        <?php endif; ?>
    </div>
    <div class="card-title"><?= e($card['title']) ?></div>
</div>
