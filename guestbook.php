<?php
require_once 'includes/functions.php';

// 检查留言板是否开启
if (getConfig('guestbook_enabled', '1') !== '1') {
    header('Location: index.php');
    exit;
}

$config = [
    'site_title' => getConfig('site_title', '美女导航'),
    'avatar' => getConfig('avatar', ''),
    'contact_info' => getConfig('contact_info', '微信：xxx')
];

// 获取留言板配置
$guestbookTitle = getConfig('guestbook_title', '留言板');
$guestbookSubtitle = getConfig('guestbook_subtitle', '欢迎留下你的想法');
$guestbookImage = getConfig('guestbook_image', '');

// 获取留言总数
$totalMessages = getMessageCount();

// 获取最新留言（首屏SSR，避免白屏）
$messages = getMessages(0, 10);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($guestbookTitle); ?> - <?php echo e($config['site_title']); ?></title>
    <meta name="description" content="<?php echo e(getConfig('site_description', '精选美女导航网站')); ?>">
    <meta name="keywords" content="<?php echo e($guestbookTitle . ',留言板,' . $config['site_title']); ?>">
    <link rel="canonical" href="<?php echo e(getCurrentUrl()); ?>">
    <link rel="icon" type="image/png" href="/assets/images/logo.png">
    <?php if (getConfig('umami_enabled', '1') === '1'): ?>
    <link rel="preconnect" href="https://umami.xldh.cc">
    <?php endif; ?>
    <!-- Open Graph / Twitter Card -->
    <meta property="og:title" content="<?php echo e($guestbookTitle); ?>">
    <meta property="og:description" content="<?php echo e(getConfig('site_description', '精选美女导航网站')); ?>">
    <meta property="og:image" content="<?php echo e($config['avatar'] ?: 'assets/images/logo.png'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e(getCurrentUrl()); ?>">
    <meta property="og:site_name" content="<?php echo e($config['site_title']); ?>">
    <meta name="twitter:card" content="summary">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <?php
    $theme = getConfig('theme', 'default');
    if ($theme === 'memphis' && file_exists('assets/css/theme-memphis.css')):
    ?>
    <link rel="stylesheet" href="/assets/css/theme-memphis.css?v=<?php echo filemtime('assets/css/theme-memphis.css'); ?>">
    <?php elseif ($theme === 'dreamy' && file_exists('assets/css/theme-dreamy.css')): ?>
    <link rel="stylesheet" href="/assets/css/theme-dreamy.css?v=<?php echo filemtime('assets/css/theme-dreamy.css'); ?>">
    <?php endif; ?>
    <?php if (getConfig('umami_enabled', '1') === '1'): ?>
    <script defer src="<?php echo e(getConfig('umami_script_url', 'https://umami.xldh.cc/script.js')); ?>" data-website-id="<?php echo e(getConfig('umami_website_id', 'd1d35aa8-18e3-4c74-8db4-bcb610de22b5')); ?>"></script>
    <?php endif; ?>
    <style>
        :root {
            --cards-per-row-desktop: <?php echo e(getConfig('cards_per_row_desktop', 'repeat(auto-fill, 120px)')); ?>;
            --cards-per-row-tablet: <?php echo e(getConfig('cards_per_row_tablet', 'repeat(4, 1fr)')); ?>;
            --cards-per-row-mobile: <?php echo e(getConfig('cards_per_row_mobile', 'repeat(3, 1fr)')); ?>;
        }

        /* 骨架屏动画 */
        @keyframes skeleton-loading {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s ease-in-out infinite;
            border-radius: 8px;
        }

        .skeleton-text {
            height: 14px;
            margin-bottom: 8px;
        }

        .skeleton-text.short {
            width: 60%;
        }

        .skeleton-text.long {
            width: 100%;
        }

        .skeleton-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .skeleton-item {
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .skeleton-item:last-child {
            border-bottom: none;
        }

        .skeleton-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        /* 加载状态 */
        .loading-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #999;
            font-size: 14px;
            gap: 8px;
        }

        .loading-indicator .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f0f0f0;
            border-top-color: #e94560;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 无限滚动触发器 */
        .infinite-scroll-trigger {
            height: 20px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- 顶部栏（带返回首页） -->
    <header class="top-bar">
        <div class="header-left">
            <div class="avatar-section" onclick="window.location.href='/'" style="cursor:pointer;">
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
            <a href="/" class="func-btn" title="返回首页" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </a>
        </div>
    </header>

    <div class="page-wrapper">
        <!-- 留言板标题区 -->
        <section style="padding: 10px 0;">
            <div class="section-card" style="padding: 30px 20px; text-align: center;">
                <?php if ($guestbookImage): ?>
                <img src="<?php echo e($guestbookImage); ?>" alt="留言板顶部图片" class="guestbook-header-image" loading="eager">
                <?php endif; ?>
                <div style="font-size: 48px; margin-bottom: 12px;">💬</div>
                <h1 style="font-size: 24px; font-weight: 700; color: #1a1a2e; margin-bottom: 8px;"><?php echo e($guestbookTitle); ?></h1>
                <p style="font-size: 14px; color: #999;">
                    <?php echo e(str_replace('{count}', $totalMessages, $guestbookSubtitle)); ?>
                </p>
            </div>
        </section>

        <!-- 留言列表 -->
        <section style="padding: 10px 0;">
            <div class="section-card" style="padding: 20px;">
                <div class="guestbook-list" id="guestbookList">
                    <?php renderGuestbookMessages($messages); ?>
                </div>

                <!-- 骨架屏（初始隐藏） -->
                <div id="skeletonContainer" style="display: none;">
                    <div class="skeleton-item">
                        <div class="skeleton-header">
                            <div class="skeleton skeleton-avatar"></div>
                            <div style="flex: 1;">
                                <div class="skeleton skeleton-text short"></div>
                            </div>
                        </div>
                        <div class="skeleton skeleton-text long"></div>
                        <div class="skeleton skeleton-text" style="width: 80%;"></div>
                    </div>
                    <div class="skeleton-item">
                        <div class="skeleton-header">
                            <div class="skeleton skeleton-avatar"></div>
                            <div style="flex: 1;">
                                <div class="skeleton skeleton-text short"></div>
                            </div>
                        </div>
                        <div class="skeleton skeleton-text long"></div>
                        <div class="skeleton skeleton-text" style="width: 60%;"></div>
                    </div>
                    <div class="skeleton-item">
                        <div class="skeleton-header">
                            <div class="skeleton skeleton-avatar"></div>
                            <div style="flex: 1;">
                                <div class="skeleton skeleton-text short"></div>
                            </div>
                        </div>
                        <div class="skeleton skeleton-text long"></div>
                    </div>
                </div>

                <!-- 无限滚动触发器 -->
                <div class="infinite-scroll-trigger" id="scrollTrigger"></div>

                <!-- 加载状态 -->
                <div id="loadingIndicator" style="display: none;">
                    <div class="loading-indicator">
                        <div class="spinner"></div>
                        <span>加载中...</span>
                    </div>
                </div>

                <!-- 分页 -->
                <div id="guestbookPagination" style="text-align: center; padding: 16px 0 0; border-top: 1px solid #f0f0f0; margin-top: 8px;">
                    <?php if ($totalMessages > 10): ?>
                    <button class="pagination-btn" id="loadMoreBtn" onclick="loadMoreMessages()">加载更多</button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 留言表单 -->
        <section style="padding: 10px 0;">
            <div class="section-card" style="padding: 20px;">
                <h2 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #1a1a2e;">✏️ 写留言</h2>
                <div class="guestbook-form">
                    <input type="text" id="gbNickname" placeholder="昵称（选填，匿名可留空）" maxlength="20">
                    <textarea id="gbContent" placeholder="说点什么..." rows="4" maxlength="500" required></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span id="gbCharCount" style="font-size: 12px; color: #999;">0/500</span>
                        <button onclick="submitGuestbook()">发送留言</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- 页脚 -->
    <footer class="site-footer">
        <p><?php echo e($config['site_title']); ?> - 精选优质网站导航</p>
        <p class="visitor-count">您是本站的第 <?php echo number_format(getDisplayVisitorCount()); ?> 位访客，欢迎光临本站。</p>
    </footer>

    <script src="/assets/js/main.js?v=<?php echo filemtime('assets/js/main.js'); ?>"></script>
    <script>
        const GUESTBOOK_PAGE_SIZE = 10;
        let guestbookOffset = <?php echo count($messages); ?>;
        let guestbookHasMore = <?php echo $totalMessages > count($messages) ? 'true' : 'false'; ?>;
        let isLoading = false;

        // Intersection Observer 实现无限滚动
        const scrollTrigger = document.getElementById('scrollTrigger');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const loadMoreBtn = document.getElementById('loadMoreBtn');

        if (scrollTrigger && guestbookHasMore) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && guestbookHasMore && !isLoading) {
                        loadMoreMessages();
                    }
                });
            }, {
                rootMargin: '100px',
                threshold: 0
            });
            observer.observe(scrollTrigger);
        }

        // 字符计数
        document.getElementById('gbContent').addEventListener('input', function() {
            document.getElementById('gbCharCount').textContent = this.value.length + '/500';
        });

        // 加载更多
        function loadMoreMessages() {
            if (!guestbookHasMore || isLoading) return;
            isLoading = true;

            // 显示加载状态
            if (loadingIndicator) loadingIndicator.style.display = 'block';
            if (loadMoreBtn) loadMoreBtn.textContent = '加载中...';

            fetch(`admin/api/messages.php?offset=${guestbookOffset}&limit=${GUESTBOOK_PAGE_SIZE}`)
                .then(r => r.json())
                .then(result => {
                    isLoading = false;
                    if (loadingIndicator) loadingIndicator.style.display = 'none';

                    if (result.success && result.data.length > 0) {
                        appendMessages(result.data);
                        guestbookOffset += result.data.length;
                        guestbookHasMore = result.data.length === GUESTBOOK_PAGE_SIZE;
                        if (!guestbookHasMore) {
                            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
                            if (scrollTrigger) scrollTrigger.style.display = 'none';
                        }
                    } else {
                        guestbookHasMore = false;
                        if (loadMoreBtn) loadMoreBtn.style.display = 'none';
                        if (scrollTrigger) scrollTrigger.style.display = 'none';
                    }
                })
                .catch(() => {
                    isLoading = false;
                    if (loadingIndicator) loadingIndicator.style.display = 'none';
                    if (loadMoreBtn) loadMoreBtn.textContent = '加载失败，点击重试';
                });
        }

        // 提交留言
        function submitGuestbook() {
            const nickname = document.getElementById('gbNickname').value.trim();
            const content = document.getElementById('gbContent').value.trim();

            if (!content) { showToast('请输入留言内容'); return; }
            if (content.length > 500) { showToast('留言内容不能超过500字'); return; }

            const btn = document.querySelector('.guestbook-form button');
            btn.disabled = true;
            btn.textContent = '发送中...';

            fetch('admin/api/messages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nickname, content })
            })
            .then(r => r.json())
            .then(result => {
                btn.disabled = false;
                btn.textContent = '发送留言';
                if (result.success) {
                    showToast('留言成功！');
                    document.getElementById('gbContent').value = '';
                    document.getElementById('gbCharCount').textContent = '0/500';
                    prependNewMessage({
                        id: result.data.id,
                        nickname: nickname || '匿名用户',
                        content: content,
                        created_at: new Date().toISOString()
                    });
                } else {
                    showToast(result.message || '提交失败');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = '发送留言';
                showToast('网络错误，请稍后重试');
            });
        }

        // 追加消息
        function appendMessages(messages) {
            const list = document.getElementById('guestbookList');
            messages.forEach(msg => {
                const item = createMessageItem(msg);
                list.appendChild(item);
            });
        }

        // 新消息插入顶部
        function prependNewMessage(msg) {
            const list = document.getElementById('guestbookList');
            if (list.querySelector('.guestbook-empty')) {
                list.innerHTML = '';
            }
            const item = createMessageItem(msg);
            list.insertBefore(item, list.firstChild);
            item.style.animation = 'highlightNew 2s ease';
        }

        // 创建消息DOM
        function createMessageItem(msg) {
            const div = document.createElement('div');
            div.className = 'guestbook-item';
            let html = `
                <div class="guestbook-item-header">
                    <span class="guestbook-item-nickname">${escapeHtml(msg.nickname || '匿名用户')}</span>
                    <span class="guestbook-item-time">${formatTime(msg.created_at)}</span>
                </div>
                <div class="guestbook-item-content">${escapeHtml(msg.content)}</div>
            `;
            if (msg.reply) {
                html += `
                    <div class="guestbook-reply">
                        <div class="guestbook-reply-label">管理员回复</div>
                        <div class="guestbook-reply-content">${escapeHtml(msg.reply)}</div>
                    </div>
                `;
            }
            div.innerHTML = html;
            return div;
        }

        // 格式化时间
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            if (diff < 60) return '刚刚';
            if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
            if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
            if (diff < 604800) return Math.floor(diff / 86400) + '天前';
            return date.toLocaleString('zh-CN', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
    </script>
    <?php
    // 记录留言板访问
    recordVisit('guestbook');
    processVisitQueue();
    ?>
</body>
</html>
