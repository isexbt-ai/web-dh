<?php
/**
 * 留言板页内容模板。
 * 数据：messages/page/pages/guestbook(数组)
 */
$messages = $messages ?? [];
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$gb = $guestbook ?? [];
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

    <?php /* 留言列表仅管理员可见，前台不展示（管理员在后台「留言管理」查看） */ ?>
</div>
