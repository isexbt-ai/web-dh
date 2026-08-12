<?php
/**
 * 后台主布局。
 * 数据：title/active/content/csrfToken/adminUser/siteTitle
 */
$siteTitle = $siteTitle ?? '后台管理';
$pageTitle = $title ?? '控制台';
$active = $active ?? '';
$csrfToken = $csrfToken ?? (string) ($_SESSION['csrf_token'] ?? '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> - <?= e($siteTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header"><h2><?= e($siteTitle) ?></h2></div>
            <nav class="sidebar-nav">
                <a href="/admin" class="nav-item<?= $active === 'dashboard' ? ' active' : '' ?>">仪表盘</a>
                <a href="/admin/config" class="nav-item<?= $active === 'config' ? ' active' : '' ?>">站点配置</a>
                <a href="/admin/ads" class="nav-item<?= $active === 'ads' ? ' active' : '' ?>">广告管理</a>
                <a href="/admin/notices" class="nav-item<?= $active === 'notices' ? ' active' : '' ?>">公告管理</a>
                <a href="/admin/articles" class="nav-item<?= $active === 'articles' ? ' active' : '' ?>">文章管理</a>
                <a href="/admin/categories" class="nav-item<?= $active === 'categories' ? ' active' : '' ?>">分类管理</a>
                <a href="/admin/cards" class="nav-item<?= $active === 'cards' ? ' active' : '' ?>">卡片管理</a>
                <a href="/admin/links" class="nav-item<?= $active === 'links' ? ' active' : '' ?>">链接管理</a>
                <a href="/admin/showcase" class="nav-item<?= $active === 'showcase' ? ' active' : '' ?>">效果展示</a>
                <a href="/admin/messages" class="nav-item<?= $active === 'messages' ? ' active' : '' ?>">留言管理</a>
                <a href="/admin/password" class="nav-item<?= $active === 'password' ? ' active' : '' ?>">修改密码</a>
            </nav>
            <div class="sidebar-footer">
                <form method="post" action="/admin/logout" id="logoutForm">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="nav-item" style="background:none;border:none;cursor:pointer;width:100%;text-align:left;">退出登录</button>
                </form>
            </div>
        </aside>

        <main class="main-content">
            <?= $content ?? '' ?>
        </main>
    </div>

    <script src="<?= e(asset('admin.js')) ?>" defer></script>
</body>
</html>
