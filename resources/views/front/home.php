<?php
/**
 * 首页内容模板（方案一：侧边栏导航布局）。
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

<div class="nav-layout">
    <aside class="side-nav" id="sideNav" aria-label="侧边导航">
        <div class="side-brand">
            <span class="side-brand-title"><?= e($siteTitle) ?></span>
            <?php if ($siteSubtitle !== ''): ?>
            <span class="side-brand-sub"><?= e($siteSubtitle) ?></span>
            <?php endif; ?>
        </div>

        <button class="side-search-btn" id="sideSearchBtn" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
            <span>搜索链接</span>
        </button>

        <nav class="side-cats" aria-label="分类导航">
            <button class="side-cat active" type="button" data-cat="all">
                <span class="side-cat-name">全部分类</span>
                <span class="side-cat-count"><?= (int) $totalCards ?></span>
            </button>
            <?php foreach ($categoryCards as $cat): ?>
            <button class="side-cat" type="button" data-cat="<?= (int) $cat['id'] ?>">
                <span class="side-cat-name"><?= e($cat['name']) ?></span>
                <span class="side-cat-count"><?= count($cat['cards']) ?></span>
            </button>
            <?php endforeach; ?>
            <?php if (empty($categoryCards)): ?>
            <p class="side-empty">暂无分类</p>
            <?php endif; ?>
        </nav>

        <div class="side-foot">
            <?php if ($guestbookEnabled): ?>
            <a class="side-link" href="/guestbook">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>留言板</span>
            </a>
            <?php endif; ?>
            <a class="side-link" href="/showcase">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span>效果展示</span>
            </a>
            <a class="side-link" href="/articles">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>精选文章</span>
            </a>
        </div>
    </aside>
    <button class="side-mask" id="sideMask" type="button" aria-label="关闭菜单"></button>

    <main class="nav-main">
        <header class="main-head">
            <button class="main-burger" id="mainBurger" type="button" aria-label="打开分类菜单">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="main-head-info">
                <h2 class="main-head-title" id="mainCatTitle">全部分类</h2>
                <p class="main-head-meta" id="mainCatMeta">共 <?= (int) $totalCards ?> 个链接</p>
            </div>
            <button class="main-search-btn" id="mainSearchBtn" type="button" aria-label="搜索">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
            </button>
        </header>

        <?php if (!empty($ads)): ?>
        <section class="slide-section" id="slideSection" data-show-on="all">
            <div class="section-card">
                <div class="slide-carousel" id="slideCarousel" style="<?= e($carouselStyle ?? '') ?>">
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

        <div class="cat-panels" id="catPanels">
            <?php foreach ($categoryCards as $cat): ?>
            <section class="category-block" data-cat="<?= (int) $cat['id'] ?>">
                <div class="section-card">
                    <h3 class="category-title"><?= e($cat['name']) ?></h3>
                    <div class="card-grid">
                        <?php foreach ($cat['cards'] as $card): ?>
                            <?= $__view->partial('partials/card-item', ['card' => $card]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endforeach; ?>
            <?php if (empty($categoryCards)): ?>
            <div class="empty-state">暂无分类，请在后台添加</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- 搜索覆盖层（全屏） -->
<div class="search-overlay" id="searchOverlay" hidden>
    <div class="search-panel">
        <div class="search-bar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
            <input type="search" id="searchInput" placeholder="输入关键词搜索链接..." autocomplete="off" maxlength="50">
            <button class="search-close" id="searchClose" type="button" aria-label="关闭">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
            </button>
        </div>
        <p class="search-meta" id="searchMeta"></p>
        <p class="search-hint">按 ESC 关闭</p>
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

<?php
// 悬浮按钮组：返回顶部 / 效果展示 / 留言板
?>
<div class="float-btn-group" id="floatBtnGroup">
    <button class="float-btn" id="backToTop" type="button" title="返回顶部">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg>
    </button>
    <a href="/showcase" class="float-btn" title="效果展示">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <span>展示</span>
    </a>
    <?php if ($guestbookEnabled): ?>
    <a href="/guestbook" class="float-btn" title="联系我们">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span>联系</span>
    </a>
    <?php endif; ?>
</div>