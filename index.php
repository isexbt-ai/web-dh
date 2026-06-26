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
$firstCategoryCards = [];
if (!empty($categories)) {
    $firstCategoryCards = getCards($categories[0]['id']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(getConfig('site_title', '美女导航')); ?></title>
    <meta name="description" content="<?php echo e(getConfig('site_description', '精选美女导航网站')); ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --cards-per-row-desktop: <?php echo e(getConfig('cards_per_row_desktop', 'repeat(auto-fill, 120px)')); ?>;
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
                <?php if (!empty($links)): ?>
                    <?php foreach ($links as $link): ?>
                    <a href="<?php echo e($link['url']); ?>"<?php echo (strpos($link['url'], 'admin/') === false) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo e($link['title']); ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="copyLink()">复制链接</a>
                    <a href="javascript:void(0)" onclick="sharePage()">分享页面</a>
                    <a href="admin/" target="_blank">后台编辑</a>
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
        <p class="visitor-count">总访问量 <?php echo getTotalVisitsAll(); ?></p>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
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
    // 页面输出完成后处理访问统计队列
    processVisitQueue();
    ?>
</body>
</html>
