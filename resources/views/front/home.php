<?php
/**
 * 首页内容模板。
 * 数据：categories/categoryCards/ads/notices/siteTitle/siteSubtitle/visitorCount
 */
$siteTitle = $siteTitle ?? (string) config('seo.site_title');
$siteSubtitle = $siteSubtitle ?? '';
$ads = $ads ?? [];
$categoryCards = $categoryCards ?? [];
$notices = $notices ?? [];
?>
<h1 class="sr-only"><?= e($siteTitle) ?> - <?= e($siteSubtitle) ?></h1>

<?php if (!empty($ads)): ?>
<section class="slide-section">
    <div class="section-card">
        <div class="slide-carousel" id="slideCarousel">
            <?php foreach ($ads as $i => $ad): ?>
            <div class="slide-item<?= $i === 0 ? ' active' : '' ?>">
                <?php if (!empty($ad['image'])): ?>
                <a href="<?= e($ad['link'] ?? '#') ?>" target="_blank" rel="noopener">
                    <img src="<?= e($ad['image']) ?>" alt="<?= e($ad['title']) ?>" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (count($ads) > 1): ?>
            <div class="slide-dots">
                <?php foreach ($ads as $i => $ad): ?>
                <span class="slide-dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>"></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($categoryCards)): ?>
    <?php foreach ($categoryCards as $cat): ?>
    <section class="category-block" id="cat-<?= e((string) $cat['id']) ?>">
        <div class="section-card">
            <h2 class="category-title"><?= e($cat['name']) ?></h2>
            <div class="card-grid">
                <?php foreach ($cat['cards'] as $card): ?>
                    <?= $__view->partial('partials/card-item', ['card' => $card]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>
<?php else: ?>
<div class="empty-state">暂无分类，请在后台添加</div>
<?php endif; ?>

<?php if (!empty($notices)): ?>
<div class="notice-modal" id="noticeModal">
    <div class="notice-modal-overlay"></div>
    <div class="notice-modal-content">
        <div class="notice-modal-header">
            <h3>公告</h3>
            <button class="notice-modal-close" id="noticeClose" type="button">&times;</button>
        </div>
        <div class="notice-modal-body">
            <?php foreach ($notices as $notice): ?>
            <div class="notice-modal-item">
                <strong><?= e($notice['title']) ?></strong>
                <p><?= e($notice['content']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="notice-modal-footer">
            <label class="notice-dont-show">
                <input type="checkbox" id="noticeDontShow"> 今日不再显示
            </label>
            <button class="notice-modal-confirm" id="noticeConfirm" type="button">我知道了</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($visitorCount)): ?>
<p class="visitor-count">您是本站的第 <?= number_format((int) $visitorCount) ?> 位访客，欢迎光临本站。</p>
<?php endif; ?>
