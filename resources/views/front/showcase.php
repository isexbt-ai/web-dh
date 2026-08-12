<?php
/**
 * 效果展示页内容模板。
 * 数据：showcases/galleries/currentGalleryId
 */
$showcases = $showcases ?? [];
$galleries = $galleries ?? [];
$currentGalleryId = (int) ($currentGalleryId ?? 0);
?>
<div class="showcase-container">
    <div class="showcase-header">
        <h1>效果展示</h1>
        <p>真实效果一览</p>
    </div>

    <?php if ($galleries !== []): ?>
    <div class="showcase-tabs" role="tablist">
        <a class="showcase-tab<?= $currentGalleryId === 0 ? ' active' : '' ?>" href="/showcase" role="tab">全部</a>
        <?php foreach ($galleries as $g): ?>
        <a class="showcase-tab<?= $currentGalleryId === (int) $g['id'] ? ' active' : '' ?>" href="/showcase/<?= (int) $g['id'] ?>.html" role="tab"><?= e($g['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="showcase-grid" id="showcaseGrid">
        <?php if ($showcases === []): ?>
        <div class="showcase-empty">
            <p>暂无展示内容</p>
        </div>
        <?php else: ?>
            <?php foreach ($showcases as $index => $item): ?>
            <?php
            $imageUrl = (string) ($item['image_url'] ?? '');
            $mediaType = (string) ($item['media_type'] ?? 'image');
            $isVideo = $mediaType === 'video';
            ?>
            <div class="showcase-item"
                 data-index="<?= $index ?>"
                 data-title="<?= e($item['title']) ?>"
                 data-src="<?= e($imageUrl) ?>"
                 data-media-type="<?= $isVideo ? 'video' : 'image' ?>">
                <div class="showcase-image-wrapper">
                    <?php if ($isVideo): ?>
                    <span class="showcase-badge video">视频</span>
                    <?php endif; ?>
                    <img src="<?= e($imageUrl) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                </div>
                <div class="showcase-item-title"><?= e($item['title']) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="showcase-modal" id="showcaseModal" hidden>
        <div class="showcase-modal-content">
            <span class="showcase-modal-close" id="modalClose">&times;</span>
            <span class="showcase-modal-counter" id="modalCounter"></span>
            <div id="modalMediaContainer"></div>
            <span class="showcase-modal-nav prev" id="modalPrev">&#8249;</span>
            <span class="showcase-modal-nav next" id="modalNext">&#8250;</span>
            <span class="showcase-modal-title" id="modalTitle"></span>
        </div>
    </div>
</div>
