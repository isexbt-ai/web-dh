<?php
require_once 'includes/functions.php';

$ads = getAds();
$notices = getNotices();
$categories = getCategories();
$config = [
    'avatar' => getConfig('avatar', ''),
    'contact_info' => getConfig('contact_info', '微信：xxx'),
    'site_title' => getConfig('site_title', '美女导航')
];
$links = getLinks();

// 获取第一个分类的卡片用于首屏SSR
$cardSortMethod = getConfig('card_sort_method', 'default');
$firstCategoryCards = [];
if (!empty($categories)) {
    $firstCategoryCards = getCards($categories[0]['id'], true, $cardSortMethod);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(getConfig('site_title', '美女导航')); ?></title>
    <meta name="description" content="<?php echo e(getConfig('site_description', '精选美女导航网站')); ?>">
    <!-- Open Graph / Twitter Card -->
    <meta property="og:title" content="<?php echo e(getConfig('site_title', '美女导航')); ?>">
    <meta property="og:description" content="<?php echo e(getConfig('site_description', '精选美女导航网站')); ?>">
    <meta property="og:image" content="<?php echo e($config['avatar'] ?: 'assets/images/logo.png'); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#e94560">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <style>
        :root {
            --cards-per-row-desktop: <?php echo e(getConfig('cards_per_row_desktop', 'repeat(6, 1fr)')); ?>;
            --cards-per-row-tablet: <?php echo e(getConfig('cards_per_row_tablet', 'repeat(4, 1fr)')); ?>;
            --cards-per-row-mobile: <?php echo e(getConfig('cards_per_row_mobile', 'repeat(3, 1fr)')); ?>;
        }
    </style>
</head>
<body>
    <!-- 顶部栏 -->
    <header class="top-bar">
        <div class="header-left">
            <div class="avatar-section">
                <?php if ($config['avatar']): ?>
                    <img src="<?php echo e($config['avatar']); ?>" alt="头像" class="avatar-img" loading="eager">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                <?php endif; ?>
                <span class="contact-info"><?php echo e($config['contact_info']); ?></span>
            </div>
        </div>
        <div class="header-right">
            <button class="func-btn" id="funcBtn" title="功能菜单">
                <span>功能</span>
            </button>
            <div class="func-menu" id="funcMenu">
                <a href="javascript:void(0)" onclick="copyLink()">复制链接</a>
                <a href="javascript:void(0)" onclick="sharePage()">分享页面</a>
                <?php if (!empty($links)): ?>
                    <?php foreach ($links as $link): ?>
                    <a href="<?php echo e($link['url']); ?>"<?php echo (strpos($link['url'], 'admin/') === false) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo e($link['title']); ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <!-- 轮播图区 -->
        <?php if (!empty($ads)): ?>
        <section class="slide-section">
            <div class="section-card" style="padding: 10px;">
                <div class="slide-carousel" id="slideCarousel">
                    <?php foreach ($ads as $index => $ad): ?>
                    <div class="slide-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <a href="<?php echo e($ad['link'] ?? '#'); ?>" target="_blank" rel="noopener">
                            <?php if ($ad['image']): ?>
                                <img src="<?php echo e($ad['image']); ?>" alt="<?php echo e($ad['title']); ?>" <?php echo $index === 0 ? '' : 'loading="lazy"'; ?>>
                            <?php else: ?>
                                <div class="slide-placeholder">
                                    <span><?php echo e($ad['title'] ?: '推荐位'); ?></span>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($ads) > 1): ?>
                    <div class="slide-dots">
                        <?php foreach ($ads as $index => $ad): ?>
                        <span class="slide-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 公告区（精简版，只显示内容） -->
        <?php if (!empty($notices)): ?>
        <section class="notice-section">
            <div class="section-card" style="padding: 0;">
                <div class="notice-list" id="noticeList">
                    <?php foreach ($notices as $notice): ?>
                    <div class="notice-item">
                        <?php echo e($notice['content'] ?: $notice['title']); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 分类目录 -->
        <?php if (!empty($categories)): ?>
        <section class="category-section">
            <div class="section-card" style="padding: 10px;">
                <div class="category-tabs" id="categoryTabs">
                    <?php foreach ($categories as $index => $cat): ?>
                    <button class="category-tab <?php echo $index === 0 ? 'active' : ''; ?>"
                            data-id="<?php echo $cat['id']; ?>"
                            onclick="switchCategory(<?php echo $cat['id']; ?>)">
                        <?php echo e($cat['name']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- 卡片网格 -->
        <section class="card-section">
            <div class="section-card" style="padding: 14px;">
                <div class="card-grid" id="cardGrid">
                    <?php renderCardsHtml($firstCategoryCards); ?>
                </div>
            </div>
        </section>
    </div>

    <!-- 页脚 -->
    <footer class="site-footer">
        <p><?php echo e(getConfig('site_title', '美女导航')); ?> - 精选优质网站导航</p>
        <p class="visitor-count">您是本站的第 <?php echo number_format(getDisplayVisitorCount()); ?> 位访客，欢迎光临本站。</p>
    </footer>

    <!-- 多功能悬浮按钮组 -->
    <div class="float-btn-group" id="floatBtnGroup">
        <!-- 返回顶部按钮 -->
        <button class="float-btn" id="backToTop" title="返回顶部">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 15l-6-6-6 6"/>
            </svg>
        </button>

        <!-- 效果展示入口 -->
        <a href="showcase.php" class="float-btn" title="效果展示">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            <span>展示</span>
        </a>

        <!-- 留言板入口 -->
        <?php if (getConfig('guestbook_enabled', '1') === '1'): ?>
        <a href="guestbook.php" class="float-btn" title="留言板">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span>留言</span>
        </a>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // 注册 Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('SW registration failed: ', error);
                    });
            });
        }

        // 标记首屏已加载的分类，避免JS重复请求
        window.__firstCategoryLoaded = <?php echo !empty($categories) ? $categories[0]['id'] : 'null'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($categories)): ?>
            // 更新Tab状态但不触发AJAX（首屏已由PHP渲染）
            const tabs = document.querySelectorAll('.category-tab');
            tabs.forEach(tab => {
                tab.classList.toggle('active', parseInt(tab.dataset.id) === <?php echo $categories[0]['id']; ?>);
            });
            <?php endif; ?>
        });
    </script>
    <?php
    // 记录首页访问
    recordVisit('index');
    // 页面输出完成后处理访问统计队列
    processVisitQueue();
    ?>
</body>
</html>
