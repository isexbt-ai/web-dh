<?php
/**
 * 效果展示页内容模板（灯箱版）。
 * 数据：showcases（每项含 image_url/video_src/poster_src/media_type/title）/
 *      galleries/currentGalleryId
 */
$showcases = $showcases ?? [];
$galleries = $galleries ?? [];
$currentGalleryId = (int) ($currentGalleryId ?? 0);
?>
<div class="showcase-container">
    <div class="showcase-header">
        <h1>效果展示</h1>
        <p>点击任意项查看大图 / 播放视频</p>
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
            <?php foreach ($showcases as $item): ?>
            <?php
            $thumb = (string) ($item['poster_src'] ?? '');
            if ($thumb === '') {
                $thumb = (string) ($item['image_url'] ?? '');
            }
            $videoSrc = (string) ($item['video_src'] ?? '');
            $isVideo = ($item['media_type'] ?? 'image') === 'video' && $videoSrc !== '';
            ?>
            <figure class="showcase-item"
                    data-title="<?= e($item['title']) ?>"
                    data-media-type="<?= $isVideo ? 'video' : 'image' ?>"
                    data-src="<?= e($isVideo ? $videoSrc : (string) ($item['image_url'] ?? '')) ?>"
                    data-poster="<?= e($thumb) ?>"
                    data-width="<?= (int) ($item['image_width'] ?? 0) ?>">
                <div class="showcase-thumb">
                    <?php if ($isVideo): ?>
                    <video src="<?= e($videoSrc) ?>"
                           <?= $thumb !== '' ? 'poster="' . e($thumb) . '"' : '' ?>
                           preload="metadata" muted playsinline
                           aria-label="<?= e($item['title']) ?>"></video>
                    <span class="showcase-play" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <?php else: ?>
                    <img src="<?= e($thumb) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                    <?php endif; ?>
                </div>
                <figcaption class="showcase-item-title"><?= e($item['title']) ?></figcaption>
            </figure>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 灯箱：图片支持缩放/拖拽/全屏，视频用原生 controls 完整播放 -->
    <div class="lbx" id="showcaseLightbox" hidden role="dialog" aria-modal="true" aria-label="媒体查看器">
        <div class="lbx-bar">
            <span class="lbx-title" id="lbxTitle"></span>
            <span class="lbx-counter" id="lbxCounter"></span>
            <button class="lbx-icon-btn" id="lbxFullscreen" type="button" title="全屏" aria-label="全屏">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
            </button>
            <button class="lbx-icon-btn" id="lbxClose" type="button" title="关闭 (Esc)" aria-label="关闭">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
            </button>
        </div>

        <div class="lbx-stage" id="lbxStage">
            <button class="lbx-nav prev" id="lbxPrev" type="button" aria-label="上一张">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>

            <div class="lbx-viewport" id="lbxViewport">
                <div class="lbx-media" id="lbxMedia"></div>
            </div>

            <button class="lbx-nav next" id="lbxNext" type="button" aria-label="下一张">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>

        <div class="lbx-tools" id="lbxTools">
            <button class="lbx-tool" id="lbxZoomOut" type="button" title="缩小 (-)">−</button>
            <span class="lbx-zoom" id="lbxZoomLabel">100%</span>
            <button class="lbx-tool" id="lbxZoomIn" type="button" title="放大 (+)">+</button>
            <button class="lbx-tool wide" id="lbxActual" type="button" title="原始大小 (0)">1:1</button>
            <button class="lbx-tool wide" id="lbxFit" type="button" title="适应窗口">适应</button>
        </div>
    </div>
</div>
