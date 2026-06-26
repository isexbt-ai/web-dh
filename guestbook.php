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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($guestbookTitle); ?> - <?php echo e($config['site_title']); ?></title>
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
    <!-- 顶部栏（带返回首页） -->
    <header class="top-bar">
        <div class="header-left">
            <div class="avatar-section" onclick="window.location.href='index.php'" style="cursor:pointer;">
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
            <a href="index.php" class="func-btn" title="返回首页" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">
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

                <!-- 分页 -->
                <div id="guestbookPagination" style="text-align: center; padding: 16px 0 0; border-top: 1px solid #f0f0f0; margin-top: 8px;">
                    <?php if ($totalMessages > 10): ?>
                    <button class="pagination-btn" onclick="loadMoreMessages()">加载更多</button>
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
        <p class="visitor-count">您是本站的第 <?php echo getTotalVisitsAll(); ?> 位访客，欢迎光临本站。</p>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        const GUESTBOOK_PAGE_SIZE = 10;
        let guestbookOffset = <?php echo count($messages); ?>;
        let guestbookHasMore = <?php echo $totalMessages > count($messages) ? 'true' : 'false'; ?>;

        // 字符计数
        document.getElementById('gbContent').addEventListener('input', function() {
            document.getElementById('gbCharCount').textContent = this.value.length + '/500';
        });

        // 加载更多
        function loadMoreMessages() {
            if (!guestbookHasMore) return;
            const btn = document.querySelector('.pagination-btn');
            if (btn) btn.textContent = '加载中...';

            fetch(`admin/api/messages.php?offset=${guestbookOffset}&limit=${GUESTBOOK_PAGE_SIZE}`)
                .then(r => r.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        appendMessages(result.data);
                        guestbookOffset += result.data.length;
                        guestbookHasMore = result.data.length === GUESTBOOK_PAGE_SIZE;
                        if (!guestbookHasMore && btn) btn.style.display = 'none';
                    } else {
                        guestbookHasMore = false;
                        if (btn) btn.style.display = 'none';
                    }
                })
                .catch(() => {
                    if (btn) btn.textContent = '加载失败，点击重试';
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
            div.innerHTML = `
                <div class="guestbook-item-header">
                    <span class="guestbook-item-nickname">${escapeHtml(msg.nickname || '匿名用户')}</span>
                    <span class="guestbook-item-time">${formatTime(msg.created_at)}</span>
                </div>
                <div class="guestbook-item-content">${escapeHtml(msg.content)}</div>
            `;
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
    <?php processVisitQueue(); ?>
</body>
</html>
