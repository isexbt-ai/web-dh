<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$error = '';
$success = '';

// 处理配置保存
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_guestbook_config'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        $guestbookEnabled = isset($_POST['guestbook_enabled']) ? '1' : '0';
        $guestbookTitle = isset($_POST['guestbook_title']) ? trim($_POST['guestbook_title']) : '留言板';
        $guestbookSubtitle = isset($_POST['guestbook_subtitle']) ? trim($_POST['guestbook_subtitle']) : '欢迎留下你的想法';

        // 处理留言板图片上传
        $guestbookImage = getConfig('guestbook_image', '');
        if (isset($_FILES['guestbook_image_file']) && $_FILES['guestbook_image_file']['tmp_name']) {
            $result = uploadImage($_FILES['guestbook_image_file'], 'guestbook');
            if ($result['success']) {
                $guestbookImage = $result['path'];
            }
        }
        // 如果填入了URL，优先使用URL
        if (isset($_POST['guestbook_image']) && trim($_POST['guestbook_image'])) {
            $guestbookImage = trim($_POST['guestbook_image']);
        }

        setConfig('guestbook_enabled', $guestbookEnabled);
        setConfig('guestbook_title', $guestbookTitle);
        setConfig('guestbook_subtitle', $guestbookSubtitle);
        setConfig('guestbook_image', $guestbookImage);

        $success = '留言板配置保存成功';
    }
}

// 获取配置
$gbConfig = [
    'guestbook_enabled' => getConfig('guestbook_enabled', '1'),
    'guestbook_title' => getConfig('guestbook_title', '留言板'),
    'guestbook_subtitle' => getConfig('guestbook_subtitle', '欢迎留下你的想法'),
    'guestbook_image' => getConfig('guestbook_image', '')
];

// 获取所有留言（包括已删除的，用于管理）
$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

// 获取留言统计
$totalCount = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$activeCount = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_active = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>留言管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>后台管理</h2></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    <span>仪表盘</span>
                </a>
                <a href="config.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>站点配置</span>
                </a>
                <a href="ads.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <span>广告管理</span>
                </a>
                <a href="notices.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span>公告管理</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                    <span>分类管理</span>
                </a>
                <a href="cards.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    <span>卡片管理</span>
                </a>
                <a href="links.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <span>链接管理</span>
                </a>
                <a href="messages.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>留言管理</span>
                </a>
                <a href="password.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>修改密码</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>退出登录</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <h1>留言管理</h1>
                <p>管理留言板配置和留言内容</p>
            </header>

            <?php if ($success): ?>
            <div style="background: rgba(78, 204, 163, 0.2); border: 1px solid rgba(78, 204, 163, 0.3); color: #4ecca3; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 14px;">
                <?php echo e($success); ?>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div style="background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.3); color: #f44336; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 14px;">
                <?php echo e($error); ?>
            </div>
            <?php endif; ?>

            <!-- 留言板配置区域 -->
            <div class="table-section" style="margin-bottom: 32px;">
                <h2 class="section-title" style="margin-bottom: 20px;">💬 留言板设置</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="save_guestbook_config" value="1">

                    <div class="form-group">
                        <label>留言板开关</label>
                        <div class="toggle-switch">
                            <input type="checkbox" id="guestbookEnabled" name="guestbook_enabled"
                                   value="1" <?php echo $gbConfig['guestbook_enabled'] === '1' ? 'checked' : ''; ?>>
                            <label for="guestbookEnabled" class="toggle-label">
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-text"><?php echo $gbConfig['guestbook_enabled'] === '1' ? '已开启' : '已关闭'; ?></span>
                        </div>
                        <p class="form-hint">关闭后前台悬浮按钮和留言板页面将不可访问</p>
                    </div>

                    <div class="form-group">
                        <label>留言板标题</label>
                        <input type="text" name="guestbook_title" value="<?php echo e($gbConfig['guestbook_title']); ?>" placeholder="请输入留言板标题">
                    </div>

                    <div class="form-group">
                        <label>留言板副标题</label>
                        <input type="text" name="guestbook_subtitle" value="<?php echo e($gbConfig['guestbook_subtitle']); ?>" placeholder="支持 {count} 占位符，显示留言数量">
                        <p class="form-hint">支持 {count} 占位符，显示留言数量</p>
                    </div>

                    <div class="form-group">
                        <label>留言板顶部图片</label>
                        <div style="display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                            <!-- 方式1：本地上传 -->
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-size: 13px; color: #666; margin-bottom: 8px; display: block;">方式1：本地上传</label>
                                <div class="image-upload" style="max-width: 200px;">
                                    <input type="file" name="guestbook_image_file" accept="image/*" onchange="previewImage(this, 'guestbookImagePreview')">
                                    <div class="upload-icon">📷</div>
                                    <div class="upload-text">点击上传图片</div>
                                </div>
                            </div>
                            <!-- 方式2：填入URL -->
                            <div style="flex: 1; min-width: 200px;">
                                <label style="font-size: 13px; color: #666; margin-bottom: 8px; display: block;">方式2：图片URL</label>
                                <input type="text" name="guestbook_image" value="<?php echo e($gbConfig['guestbook_image']); ?>" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: #fff;">
                            </div>
                        </div>
                        <div id="guestbookImagePreview" style="margin-top: 12px;">
                            <?php if ($gbConfig['guestbook_image']): ?>
                                <img src="<?php echo e($gbConfig['guestbook_image']); ?>" style="max-width: 200px; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">保存配置</button>
                </form>
            </div>

            <!-- 统计卡片 -->
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e94560, #ff6b6b);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalCount; ?></h3>
                        <p>总留言数</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4ecca3, #6bcb77);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $activeCount; ?></h3>
                        <p>显示中</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f9a825, #ff9800);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalCount - $activeCount; ?></h3>
                        <p>已删除</p>
                    </div>
                </div>
            </div>

            <div class="table-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">留言列表</h2>
                </div>

                <?php if (empty($messages)): ?>
                <div class="empty-state">暂无留言</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>昵称</th>
                            <th>内容</th>
                            <th>IP</th>
                            <th>时间</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td><?php echo $msg['id']; ?></td>
                            <td><?php echo e($msg['nickname'] ?: '匿名用户'); ?></td>
                            <td><?php echo e(mb_substr($msg['content'], 0, 60)) . (mb_strlen($msg['content']) > 60 ? '...' : ''); ?></td>
                            <td><?php echo e($msg['ip'] ?? '-'); ?></td>
                            <td><?php echo e($msg['created_at']); ?></td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; <?php echo $msg['is_active'] ? 'background: rgba(78, 204, 163, 0.15); color: #4ecca3;' : 'background: rgba(244, 67, 54, 0.15); color: #f44336;'; ?>">
                                    <?php echo $msg['is_active'] ? '● 显示中' : '● 已删除'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <?php if ($msg['is_active']): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteMessage(<?php echo $msg['id']; ?>)" style="padding: 6px 12px; font-size: 12px; background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.2); color: #f44336; border-radius: 8px; cursor: pointer; transition: all 0.3s;">删除</button>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" onclick="restoreMessage(<?php echo $msg['id']; ?>)" style="padding: 6px 12px; font-size: 12px; background: #f8f9fa; border: 1px solid #e0e0e0; color: #333333; border-radius: 8px; cursor: pointer; transition: all 0.3s;">恢复</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function deleteMessage(id) {
            if (!confirm('确定要删除这条留言吗？')) return;
            saveData('message', { id: id, is_active: 0 }, () => location.reload());
        }

        function restoreMessage(id) {
            if (!confirm('确定要恢复这条留言吗？')) return;
            saveData('message', { id: id, is_active: 1 }, () => location.reload());
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; border-radius: 8px;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
