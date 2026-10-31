<?php
/**
 * 首页内容模板（顶部导航布局）。
 * 数据：siteTitle/siteSubtitle/ads/categoryCards/notices/visitorCount/guestbookEnabled
 */
$siteTitle = $siteTitle ?? (string) config('seo.site_title');
$siteSubtitle = $siteSubtitle ?? '';
$ads = $ads ?? [];
$categoryCards = $categoryCards ?? [];
$notices = $notices ?? [];
$guestbookEnabled = $guestbookEnabled ?? false;
$totalCards = 0;
foreach ($categoryCards as $c) {
    $totalCards += count($c['cards']);
}
?>
<h1 class="sr-only"><?= e($siteTitle) ?> - <?= e($siteSubtitle) ?></h1>

<div class="home-shell">
    <!-- 顶部导航（吸顶，通栏） -->
    <header class="home-header">
        <div class="home-header-inner">
            <div class="home-logo">
                <span class="home-logo-name"><?= e($siteTitle) ?></span>
                <?php if ($siteSubtitle !== ''): ?>
                <span class="home-logo-sub"><?= e($siteSubtitle) ?></span>
                <?php endif; ?>
            </div>

            <button class="home-search-btn" id="homeSearchBtn" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
                <span>搜索链接…</span>
            </button>

            <nav class="home-nav">
                <a href="/showcase" class="home-nav-btn">效果展示</a>
                <?php if ($guestbookEnabled): ?>
                <a href="/guestbook" class="home-nav-btn home-nav-btn-primary">留言板</a>
                <?php endif; ?>
                <a href="/articles" class="home-nav-btn">精选文章</a>
            </nav>
        </div>

        <nav class="home-cats" id="homeCats" aria-label="分类导航">
            <button class="active" type="button" data-cat="all">全部<span class="home-cat-count"><?= (int) $totalCards ?></span></button>
            <?php foreach ($categoryCards as $cat): ?>
            <button type="button" data-cat="<?= (int) $cat['id'] ?>">
                <?= e($cat['name']) ?><span class="home-cat-count"><?= count($cat['cards']) ?></span>
            </button>
            <?php endforeach; ?>
            <?php if (empty($categoryCards)): ?>
            <p class="side-empty">暂无分类</p>
            <?php endif; ?>
        </nav>
    </header>

    <div class="home-container">
        <!-- 顶部展示区（广告位轮播） -->
        <?php if (!empty($ads)): ?>
        <section class="home-banner" aria-label="推荐位">
            <div class="slide-carousel" id="slideCarousel">
                <?php foreach ($ads as $i => $ad): ?>
                <div class="slide-item<?= $i === 0 ? ' active' : '' ?>">
                    <?php if (!empty($ad['image'])): ?>
                    <a href="<?= e($ad['link'] ?? '#') ?>" target="_blank" rel="noopener">
                        <img src="<?= e(image_url((string) $ad['image'])) ?>" alt="<?= e($ad['title']) ?>" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
                    </a>
                    <?php else: ?>
                    <div class="slide-placeholder"><?= e($ad['title']) ?></div>
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
        </section>
        <?php endif; ?>

        <!-- 主内容区 -->
        <main class="home-main" id="homeMain">
            <div class="cat-panels" id="catPanels">
                <?php foreach ($categoryCards as $cat): ?>
                <section class="category-block" data-cat="<?= (int) $cat['id'] ?>">
                    <h2 class="category-title"><?= e($cat['name']) ?></h2>
                    <div class="card-grid">
                        <?php foreach ($cat['cards'] as $card): ?>
                            <?= $__view->partial('partials/card-item', ['card' => $card]) ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
                <?php if (empty($categoryCards)): ?>
                <div class="empty-state">暂无分类，请在后台添加</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

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
<p class="visitor-count">您是本站的第 <!--VISITOR_COUNT--> 位访客，欢迎光临本站。</p>
<?php endif; ?>

<button class="back-to-top" id="backToTop" type="button" title="返回顶部" aria-label="返回顶部">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg>
</button>
