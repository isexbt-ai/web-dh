<?php
/**
 * 留言板页内容模板。
 * 数据：messages/page/pages/guestbook(数组)/visitorCount
 */
$messages = $messages ?? [];
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$gb = $guestbook ?? [];
$visitorCount = (int) ($visitorCount ?? 0);
?>
<div class="guestbook-container">
    <?php if (!empty($gb['image'])): ?>
    <div class="guestbook-header-image">
        <img src="<?= e($gb['image']) ?>" alt="<?= e($gb['title']) ?>" loading="lazy">
    </div>
    <?php endif; ?>

    <section class="guestbook-topbar">
        <h1 class="sr-only"><?= e($gb['title'] ?? '联系我们') ?></h1>
        <h2><?= e($gb['title'] ?? '联系我们') ?></h2>
        <p><?= e($gb['subtitle'] ?? '') ?></p>
        <?php if (!empty($gb['notice'])): ?>
        <span><?= e($gb['notice']) ?></span>
        <?php endif; ?>
    </section>

    <section class="guestbook-section">
        <div class="section-card">
            <h3 class="guestbook-form-title">联系方式和留言</h3>
            <form class="guestbook-form" id="guestbookForm" novalidate>
                <input type="text" id="gbNickname" placeholder="昵称（选填，匿名可留空）" maxlength="20" autocomplete="nickname">
                <textarea id="gbContent" placeholder="说点什么..." rows="4" maxlength="500" required></textarea>
                <div class="guestbook-form-actions">
                    <span id="gbCharCount" class="guestbook-char-count">0/500</span>
                    <button type="submit" id="gbSubmit">发送留言</button>
                </div>
            </form>
            <div id="guestbookSuccess" class="guestbook-success" hidden>
                <p>留言已提交！感谢您的留言，我们会尽快处理。</p>
            </div>
        </div>
    </section>

    <section class="guestbook-list-section">
        <h3 class="guestbook-list-title">全部留言</h3>
        <?php if ($messages !== []): ?>
        <div class="guestbook-list">
            <?php foreach ($messages as $m): ?>
            <div class="guestbook-item">
                <div class="guestbook-item-header">
                    <span class="guestbook-item-nickname"><?= e($m['nickname'] ?: '匿名') ?></span>
                    <span class="guestbook-item-time"><?= e(date('Y-m-d H:i', strtotime((string) $m['created_at']))) ?></span>
                </div>
                <div class="guestbook-item-content"><?= e($m['content']) ?></div>
                <?php if (!empty($m['reply'])): ?>
                <div class="guestbook-reply">
                    <span class="guestbook-reply-label">站长回复</span>
                    <span class="guestbook-reply-content"><?= e($m['reply']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="guestbook-empty">暂无留言，快来抢沙发</div>
        <?php endif; ?>
    </section>

    <?= $__view->partial('partials/pagination', ['page' => $page, 'pages' => $pages, 'base' => '/guestbook']) ?>

    <?php if ($visitorCount > 0): ?>
    <p class="visitor-count">您是本站的第 <?= number_format($visitorCount) ?> 位访客，欢迎光临本站。</p>
    <?php endif; ?>
</div>
