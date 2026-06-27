<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF验证
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        $siteTitle = isset($_POST['site_title']) ? trim($_POST['site_title']) : '';
        $contactInfo = isset($_POST['contact_info']) ? trim($_POST['contact_info']) : '';
        $siteDescription = isset($_POST['site_description']) ? trim($_POST['site_description']) : '';

        // 处理头像上传
        $avatar = getConfig('avatar', '');
        if (isset($_FILES['avatar']) && $_FILES['avatar']['tmp_name']) {
            $result = uploadImage($_FILES['avatar'], 'avatar');
            if ($result['success']) {
                $avatar = $result['path'];
            }
        }

        // 保存卡片布局配置
        $cardsPerRowDesktop = isset($_POST['cards_per_row_desktop']) ? trim($_POST['cards_per_row_desktop']) : 'repeat(auto-fill, 120px)';
        $cardsPerRowTablet = isset($_POST['cards_per_row_tablet']) ? trim($_POST['cards_per_row_tablet']) : 'repeat(4, 1fr)';
        $cardsPerRowMobile = isset($_POST['cards_per_row_mobile']) ? trim($_POST['cards_per_row_mobile']) : 'repeat(3, 1fr)';

        // 保存配置
        setConfig('site_title', $siteTitle);
        setConfig('contact_info', $contactInfo);
        setConfig('site_description', $siteDescription);
        setConfig('avatar', $avatar);
        setConfig('cards_per_row_desktop', $cardsPerRowDesktop);
        setConfig('cards_per_row_tablet', $cardsPerRowTablet);
        setConfig('cards_per_row_mobile', $cardsPerRowMobile);

        $success = '配置保存成功';
    }
}

$config = [
    'site_title' => getConfig('site_title', '美女导航'),
    'contact_info' => getConfig('contact_info', '微信：xxx'),
    'site_description' => getConfig('site_description', '精选美女导航网站'),
    'avatar' => getConfig('avatar', ''),
    'cards_per_row_desktop' => getConfig('cards_per_row_desktop', 'repeat(auto-fill, 120px)'),
    'cards_per_row_tablet' => getConfig('cards_per_row_tablet', 'repeat(4, 1fr)'),
    'cards_per_row_mobile' => getConfig('cards_per_row_mobile', 'repeat(3, 1fr)')
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>站点配置 - 后台管理</title>
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
                <a href="config.php" class="nav-item active">
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
                <a href="messages.php" class="nav-item">
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
                <h1>站点配置</h1>
                <p>配置网站的基本信息和外观</p>
            </header>

            <?php if ($success): ?>
            <div style="background: rgba(78, 204, 163, 0.2); border: 1px solid rgba(78, 204, 163, 0.3); color: #4ecca3; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 14px;">
                <?php echo e($success); ?>
            </div>
            <?php endif; ?>

            <div class="table-section">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="form-group">
                        <label>网站标题</label>
                        <input type="text" name="site_title" value="<?php echo e($config['site_title']); ?>" placeholder="请输入网站标题">
                    </div>

                    <div class="form-group">
                        <label>网站描述</label>
                        <input type="text" name="site_description" value="<?php echo e($config['site_description']); ?>" placeholder="请输入网站描述">
                    </div>

                    <div class="form-group">
                        <label>联系方式</label>
                        <input type="text" name="contact_info" value="<?php echo e($config['contact_info']); ?>" placeholder="请输入联系方式">
                    </div>

                    <!-- 卡片布局配置 -->
                    <div class="form-group">
                        <label style="font-size: 16px; font-weight: 600; color: #1a1a2e; margin-bottom: 16px; display: block;">📱 卡片布局设置</label>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div>
                                <label style="font-size: 13px; color: #666; margin-bottom: 6px; display: block;">桌面端（>768px）</label>
                                <select name="cards_per_row_desktop" style="width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: #fff;">
                                    <option value="repeat(auto-fill, 120px)" <?php echo $config['cards_per_row_desktop'] === 'repeat(auto-fill, 120px)' ? 'selected' : ''; ?>>自动填充（120px）</option>
                                    <option value="repeat(2, 1fr)" <?php echo $config['cards_per_row_desktop'] === 'repeat(2, 1fr)' ? 'selected' : ''; ?>>每行 2 个</option>
                                    <option value="repeat(3, 1fr)" <?php echo $config['cards_per_row_desktop'] === 'repeat(3, 1fr)' ? 'selected' : ''; ?>>每行 3 个</option>
                                    <option value="repeat(4, 1fr)" <?php echo $config['cards_per_row_desktop'] === 'repeat(4, 1fr)' ? 'selected' : ''; ?>>每行 4 个</option>
                                    <option value="repeat(5, 1fr)" <?php echo $config['cards_per_row_desktop'] === 'repeat(5, 1fr)' ? 'selected' : ''; ?>>每行 5 个</option>
                                    <option value="repeat(6, 1fr)" <?php echo $config['cards_per_row_desktop'] === 'repeat(6, 1fr)' ? 'selected' : ''; ?>>每行 6 个</option>
                                </select>
                            </div>

                            <div>
                                <label style="font-size: 13px; color: #666; margin-bottom: 6px; display: block;">平板端（481-768px）</label>
                                <select name="cards_per_row_tablet" style="width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: #fff;">
                                    <option value="repeat(2, 1fr)" <?php echo $config['cards_per_row_tablet'] === 'repeat(2, 1fr)' ? 'selected' : ''; ?>>每行 2 个</option>
                                    <option value="repeat(3, 1fr)" <?php echo $config['cards_per_row_tablet'] === 'repeat(3, 1fr)' ? 'selected' : ''; ?>>每行 3 个</option>
                                    <option value="repeat(4, 1fr)" <?php echo $config['cards_per_row_tablet'] === 'repeat(4, 1fr)' ? 'selected' : ''; ?>>每行 4 个</option>
                                    <option value="repeat(5, 1fr)" <?php echo $config['cards_per_row_tablet'] === 'repeat(5, 1fr)' ? 'selected' : ''; ?>>每行 5 个</option>
                                </select>
                            </div>

                            <div>
                                <label style="font-size: 13px; color: #666; margin-bottom: 6px; display: block;">手机端（≤480px）</label>
                                <select name="cards_per_row_mobile" style="width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: #fff;">
                                    <option value="repeat(2, 1fr)" <?php echo $config['cards_per_row_mobile'] === 'repeat(2, 1fr)' ? 'selected' : ''; ?>>每行 2 个</option>
                                    <option value="repeat(3, 1fr)" <?php echo $config['cards_per_row_mobile'] === 'repeat(3, 1fr)' ? 'selected' : ''; ?>>每行 3 个</option>
                                    <option value="repeat(4, 1fr)" <?php echo $config['cards_per_row_mobile'] === 'repeat(4, 1fr)' ? 'selected' : ''; ?>>每行 4 个</option>
                                </select>
                            </div>
                        </div>
                        <p style="font-size: 12px; color: #999; margin-top: 8px;">修改后刷新首页即可看到效果</p>
                    </div>

                    <div class="form-group">
                        <label>头像</label>
                        <div class="image-upload" style="max-width: 200px;">
                            <input type="file" name="avatar" accept="image/*" onchange="previewImage(this, 'avatarPreview')">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">点击上传头像（支持 GIF）</div>
                        </div>
                        <div id="avatarPreview" style="margin-top: 12px;">
                            <?php if ($config['avatar']): ?>
                                <img src="../<?php echo e($config['avatar']); ?>" style="max-width: 200px; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">保存配置</button>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
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
