<?php
/**
 * 效果展示页面 - 展示 WebP 动态/静态图片和视频
 */
require_once 'includes/functions.php';

// 获取相册参数
$galleryId = isset($_GET['gallery']) ? intval($_GET['gallery']) : null;

// 获取相册列表（包含展示数量）
$galleries = getGalleriesWithCount(true);

// 获取效果展示列表
$showcases = getShowcases(true, $galleryId);

// 记录访问
recordVisit('showcase');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>效果展示 - <?php echo e(getConfig('site_title', '美女导航')); ?></title>
    <meta name="description" content="<?php echo e(getConfig('site_description', '精选美女导航网站') . ' - 效果展示'); ?>">
    <meta name="keywords" content="效果展示,<?php echo e(getConfig('site_title', '美女导航')); ?>">
    <link rel="canonical" href="<?php echo e(getCurrentUrl()); ?>">
    <link rel="icon" type="image/png" href="/assets/images/logo.png">
    <?php if (getConfig('umami_enabled', '1') === '1'): ?>
    <link rel="preconnect" href="https://umami.xldh.cc">
    <?php endif; ?>
    <meta property="og:title" content="效果展示 - <?php echo e(getConfig('site_title', '美女导航')); ?>">
    <meta property="og:description" content="<?php echo e(getConfig('site_description', '精选美女导航网站')); ?>">
    <meta property="og:image" content="<?php echo e(getConfig('avatar', '') ?: 'assets/images/logo.png'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e(getCurrentUrl()); ?>">
    <meta property="og:site_name" content="<?php echo e(getConfig('site_title', '美女导航')); ?>">
    <meta name="twitter:card" content="summary">
    <?php if (getConfig('umami_enabled', '1') === '1'): ?>
    <script defer src="<?php echo e(getConfig('umami_script_url', 'https://umami.xldh.cc/script.js')); ?>" data-website-id="<?php echo e(getConfig('umami_website_id', 'd1d35aa8-18e3-4c74-8db4-bcb610de22b5')); ?>"></script>
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <?php
    $theme = getConfig('theme', 'default');
    if ($theme === 'memphis' && file_exists('assets/css/theme-memphis.css')):
    ?>
    <link rel="stylesheet" href="/assets/css/theme-memphis.css?v=<?php echo filemtime('assets/css/theme-memphis.css'); ?>">
    <?php elseif ($theme === 'dreamy' && file_exists('assets/css/theme-dreamy.css')): ?>
    <link rel="stylesheet" href="/assets/css/theme-dreamy.css?v=<?php echo filemtime('assets/css/theme-dreamy.css'); ?>">
    <?php endif; ?>
    <style>
        /* ==================== 效果展示页面专属样式 ==================== */
        .showcase-page {
            min-height: 100vh;
            background: #f5f7fa;
        }

        .showcase-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #ffffff;
            border-bottom: 1px solid #f0f0f0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .showcase-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .showcase-header-left a {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #333333;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .showcase-header-left a:hover {
            color: #e94560;
        }

        .showcase-header-left svg {
            width: 20px;
            height: 20px;
            color: #e94560;
        }

        .showcase-header h1 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .showcase-count {
            font-size: 13px;
            color: #999999;
        }

        .showcase-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .showcase-item {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #f0f0f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
        }

        .showcase-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            border-color: #e8e8e8;
        }

        .showcase-image-wrapper {
            position: relative;
            width: 100%;
            padding-top: 75%; /* 4:3 比例 */
            overflow: hidden;
            background: #f8f9fa;
        }

        .showcase-image-wrapper img,
        .showcase-image-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .showcase-image-wrapper video {
            background: #000;
        }

        .showcase-item-title {
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #333333;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
        }

        .showcase-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            z-index: 5;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: #fff;
            box-shadow: 0 2px 8px rgba(233, 69, 96, 0.3);
        }

        .showcase-badge.webpanimated {
            background: linear-gradient(135deg, #7c4dff, #b388ff);
        }

        .showcase-badge.video {
            background: linear-gradient(135deg, #00bcd4, #4dd0e1);
        }

        /* 全屏查看模态框 */
        .showcase-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.92);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .showcase-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .showcase-modal-content {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .showcase-modal-content img,
        .showcase-modal-content video {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
        }

        .showcase-modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .showcase-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .showcase-modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .showcase-modal-nav:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .showcase-modal-nav.prev {
            left: -60px;
        }

        .showcase-modal-nav.next {
            right: -60px;
        }

        .showcase-modal-title {
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            white-space: nowrap;
            text-shadow: 0 1px 4px rgba(0,0,0,0.5);
        }

        .showcase-modal-counter {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
        }

        /* 空状态 */
        .showcase-empty {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            color: #999999;
        }

        .showcase-empty svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .showcase-empty p {
            font-size: 16px;
        }

        /* 加载动画 */
        .showcase-loading {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 0;
            color: #999999;
        }

        .showcase-loading .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f0f0f0;
            border-top-color: #e94560;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 相册导航 */
        .gallery-nav {
            display: flex;
            gap: 10px;
            padding: 16px 20px;
            max-width: 1200px;
            margin: 0 auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .gallery-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid #e8e8e8;
            color: #666666;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.3s ease;
        }

        .gallery-tab:hover {
            background: #fff5f5;
            border-color: #e94560;
            color: #e94560;
        }

        .gallery-tab.active {
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 2px 8px rgba(233, 69, 96, 0.3);
        }

        .gallery-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 10px;
            background: rgba(255,255,255,0.3);
            font-size: 11px;
            font-weight: 600;
        }

        .gallery-tab.active .gallery-count {
            background: rgba(255,255,255,0.3);
        }

        /* 响应式 */
        @media (max-width: 768px) {
            .showcase-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 12px;
                padding: 12px;
            }

            .showcase-image-wrapper {
                padding-top: 100%; /* 正方形 */
            }

            .showcase-modal-nav.prev {
                left: 10px;
            }

            .showcase-modal-nav.next {
                right: 10px;
            }

            .showcase-modal-nav {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .showcase-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                padding: 10px;
            }

            .showcase-item-title {
                font-size: 12px;
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body class="showcase-page">
    <!-- 顶部导航 -->
    <header class="showcase-header">
        <div class="showcase-header-left">
            <a href="/">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>返回首页</span>
            </a>
        </div>
        <h1>效果展示</h1>
        <span class="showcase-count">共 <?php echo count($showcases); ?> 张</span>
    </header>

    <!-- 相册导航 -->
    <?php if (!empty($galleries)): ?>
    <div class="gallery-nav">
        <a href="showcase.html" class="gallery-tab <?php echo $galleryId === null ? 'active' : ''; ?>">全部</a>
        <?php foreach ($galleries as $gallery): ?>
        <a href="showcase/<?php echo $gallery['id']; ?>.html" class="gallery-tab <?php echo $galleryId == $gallery['id'] ? 'active' : ''; ?>">
            <?php echo e($gallery['title']); ?>
            <span class="gallery-count"><?php echo $gallery['showcase_count']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 展示网格 -->
    <div class="showcase-grid" id="showcaseGrid">
        <?php if (empty($showcases)): ?>
        <div class="showcase-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="M21 15l-5-5L5 21"/>
            </svg>
            <p>暂无展示内容</p>
        </div>
        <?php else: ?>
            <?php foreach ($showcases as $index => $item):
                $imageUrl = getShowcaseImageUrl($item);
                $mediaType = $item['media_type'] ?? 'image';
                // 判断是否为动图/WebP：本地视频上传后图床会转为webp_animated
                $isWebpAnimated = false;
                $isVideo = false;
                if (!empty($imageUrl)) {
                    $ext = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
                    // 如果原始是视频类型，或者URL包含.webp，都当作动图处理
                    if ($mediaType === 'video' || $ext === 'webp' || strpos($imageUrl, '.webp') !== false) {
                        $isWebpAnimated = true;
                    }
                    // 只有本地原始视频文件(mp4/webm/mov)才用video标签
                    if ($mediaType === 'video' && in_array($ext, ['mp4', 'webm', 'mov'])) {
                        $isVideo = true;
                    }
                }
            ?>
            <div class="showcase-item" data-index="<?php echo $index; ?>" data-title="<?php echo e($item['title']); ?>" data-src="<?php echo e($imageUrl); ?>" data-media-type="<?php echo $isWebpAnimated ? 'animated' : 'image'; ?>">
                <div class="showcase-image-wrapper">
                    <?php if ($isVideo): ?>
                        <span class="showcase-badge video">视频</span>
                        <video src="<?php echo e($imageUrl); ?>" muted loop playsinline preload="metadata" onloadeddata="this.play()" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"></video>
                        <div class="video-fallback" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; align-items:center; justify-content:center; background:#333; color:#fff; font-size:14px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:32px; height:32px;"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                    <?php elseif ($isWebpAnimated): ?>
                        <span class="showcase-badge webpanimated">动图</span>
                        <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($item['title']); ?>" loading="lazy" onerror="this.src='assets/images/logo.png'">
                    <?php else: ?>
                        <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($item['title']); ?>" loading="lazy" onerror="this.src='assets/images/logo.png'">
                    <?php endif; ?>
                </div>
                <div class="showcase-item-title"><?php echo e($item['title']); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 全屏查看模态框 -->
    <div class="showcase-modal" id="showcaseModal">
        <div class="showcase-modal-content">
            <span class="showcase-modal-close" id="modalClose">&times;</span>
            <span class="showcase-modal-counter" id="modalCounter"></span>
            <div id="modalMediaContainer"></div>
            <span class="showcase-modal-nav prev" id="modalPrev">&#8249;</span>
            <span class="showcase-modal-nav next" id="modalNext">&#8250;</span>
            <span class="showcase-modal-title" id="modalTitle"></span>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="site-footer">
        <p><?php echo e(getConfig('site_title', '美女导航')); ?> - 效果展示</p>
    </footer>

    <script>
    (function() {
        const items = document.querySelectorAll('.showcase-item');
        const modal = document.getElementById('showcaseModal');
        const modalMediaContainer = document.getElementById('modalMediaContainer');
        const modalTitle = document.getElementById('modalTitle');
        const modalCounter = document.getElementById('modalCounter');
        const modalClose = document.getElementById('modalClose');
        const modalPrev = document.getElementById('modalPrev');
        const modalNext = document.getElementById('modalNext');

        let currentIndex = 0;
        const totalItems = items.length;

        if (totalItems === 0) return;

        // 点击打开模态框
        items.forEach((item, index) => {
            item.addEventListener('click', function() {
                currentIndex = index;
                openModal(item);
            });
        });

        function openModal(item) {
            const src = item.dataset.src;
            const title = item.dataset.title;
            const mediaType = item.dataset.mediaType || 'image';

            modalMediaContainer.innerHTML = '';

            if (mediaType === 'animated') {
                // WebP动图：使用img标签（浏览器原生支持）
                const img = document.createElement('img');
                img.src = src;
                img.alt = title;
                img.style.maxWidth = '90vw';
                img.style.maxHeight = '85vh';
                img.style.objectFit = 'contain';
                modalMediaContainer.appendChild(img);
            } else if (mediaType === 'video') {
                // 原始视频文件(mp4/webm/mov)
                const video = document.createElement('video');
                video.src = src;
                video.autoplay = true;
                video.loop = true;
                video.muted = false;
                video.playsInline = true;
                video.controls = true;
                video.style.maxWidth = '90vw';
                video.style.maxHeight = '85vh';
                modalMediaContainer.appendChild(video);
            } else {
                // 静态图片
                const img = document.createElement('img');
                img.src = src;
                img.alt = title;
                modalMediaContainer.appendChild(img);
            }

            modalTitle.textContent = title;
            modalCounter.textContent = (currentIndex + 1) + ' / ' + totalItems;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            // 停止视频播放
            const videos = modalMediaContainer.querySelectorAll('video');
            videos.forEach(v => v.pause());
        }

        function showPrev() {
            currentIndex = (currentIndex - 1 + totalItems) % totalItems;
            openModal(items[currentIndex]);
        }

        function showNext() {
            currentIndex = (currentIndex + 1) % totalItems;
            openModal(items[currentIndex]);
        }

        // 事件绑定
        modalClose.addEventListener('click', closeModal);
        modalPrev.addEventListener('click', function(e) {
            e.stopPropagation();
            showPrev();
        });
        modalNext.addEventListener('click', function(e) {
            e.stopPropagation();
            showNext();
        });

        // 点击背景关闭
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // 键盘导航
        document.addEventListener('keydown', function(e) {
            if (!modal.classList.contains('active')) return;
            if (e.key === 'Escape') closeModal();
            if (e.key === 'ArrowLeft') showPrev();
            if (e.key === 'ArrowRight') showNext();
        });

        // 触摸滑动支持
        let touchStartX = 0;
        let touchEndX = 0;

        modal.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        modal.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    showNext();
                } else {
                    showPrev();
                }
            }
        }
    })();
    </script>
    <?php
    // 页面输出完成后处理访问统计队列
    processVisitQueue();
    ?>
</body>
</html>
