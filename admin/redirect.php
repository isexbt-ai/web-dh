<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// 获取跳转页配置
$countdown = intval(getConfig('redirect_countdown', '3'));
$checkTimeout = intval(getConfig('redirect_check_timeout', '3000'));
$fallbackFirst = getConfig('redirect_fallback_first', '1') === '1';
$subdomainLength = intval(getConfig('redirect_subdomain_length', '6'));
$mainDomain = getConfig('redirect_main_domain', '');

// 获取跳转页统计数据
$redirectTodayVisits = 0;
$redirectTotalVisits = 0;
try {
    $today = date('Y-m-d');
    $start = strtotime($today . ' 00:00:00');
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM redirect_visits WHERE created_at >= $start");
    $redirectTodayVisits = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM redirect_visits");
    $redirectTotalVisits = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    // 忽略
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>跳转页管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
    <style>
        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stats-card { background: #fff; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stats-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; }
        .stats-card .number { font-size: 32px; font-weight: 700; color: #667eea; }
        .config-form { max-width: 800px; }
        .config-form .form-group { margin-bottom: 20px; }
        .config-form label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .config-form input[type="text"],
        .config-form input[type="number"] {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; transition: border-color 0.3s;
        }
        .config-form input:focus { border-color: #667eea; outline: none; }
        .config-form .help-text { font-size: 12px; color: #888; margin-top: 4px; }
        .btn-save { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none;
            padding: 12px 30px; border-radius: 8px; font-size: 16px; cursor: pointer; }
        .toggle-switch { display: flex; align-items: center; gap: 10px; }
        .toggle-switch input[type="checkbox"] { width: 44px; height: 24px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="sidebar-header"><h2>后台管理</h2></div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg><span>仪表盘</span></a>
                <a href="config.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg><span>站点配置</span></a>
                <a href="ads.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg><span>广告管理</span></a>
                <a href="notices.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span>公告管理</span></a>
                <a href="categories.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg><span>分类管理</span></a>
                <a href="cards.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg><span>卡片管理</span></a>
                <a href="showcase.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span>效果展示</span></a>
                <a href="links.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg><span>链接管理</span></a>
                <a href="messages.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>留言管理</span></a>
                <a href="redirect.php" class="nav-item active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg><span>跳转页管理</span></a>
                <a href="password.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><span>修改密码</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>退出登录</span></a>
            </div>
        </aside>

        <!-- 主内容区 -->
        <main class="main-content">
            <header class="page-header">
                <h1>跳转页管理</h1>
                <p>配置动态子域名跳转</p>
            </header>

            <!-- 统计卡片 -->
            <div class="stats-cards">
                <div class="stats-card">
                    <h3>今日跳转访问</h3>
                    <div class="number"><?php echo $redirectTodayVisits; ?></div>
                </div>
                <div class="stats-card">
                    <h3>总跳转访问</h3>
                    <div class="number"><?php echo $redirectTotalVisits; ?></div>
                </div>
            </div>

            <!-- 跳转页配置 -->
            <div class="table-section">
                <h2 class="section-title">跳转页配置</h2>
                <form class="config-form" id="redirectForm">
                    <input type="hidden" name="action" value="redirect_config">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <div class="form-group">
                        <label>主域名</label>
                        <input type="text" name="redirect_main_domain" value="<?php echo e($mainDomain); ?>" placeholder="abc.com">
                        <div class="help-text">你的短域名，如 abc.com。留空则自动使用当前访问域名</div>
                    </div>

                    <div class="form-group">
                        <label>子域名长度</label>
                        <input type="number" name="redirect_subdomain_length" value="<?php echo $subdomainLength; ?>" min="4" max="12" required>
                        <div class="help-text">随机子域名的长度（4-12位），如长度为6则生成 a3x9k2.abc.com</div>
                    </div>

                    <div class="form-group">
                        <label>倒计时秒数</label>
                        <input type="number" name="redirect_countdown" value="<?php echo $countdown; ?>" min="1" max="10" required>
                        <div class="help-text">跳转前的倒计时时间（1-10秒）</div>
                    </div>

                    <div class="form-group">
                        <label>检测超时时间（毫秒）</label>
                        <input type="number" name="redirect_check_timeout" value="<?php echo $checkTimeout; ?>" min="1000" max="10000" step="500" required>
                        <div class="help-text">检测目标地址可用性的超时时间</div>
                    </div>

                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="redirect_fallback_first" value="1" <?php echo $fallbackFirst ? 'checked' : ''; ?>>
                            <span>所有地址不可用时回退到第一个地址</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-save">保存配置</button>
                </form>
            </div>

            <!-- 使用说明 -->
            <div class="table-section">
                <h2 class="section-title">使用说明</h2>
                <div style="color: #555; line-height: 2; font-size: 14px;">
                    <p><strong>1. 配置主域名：</strong>输入你的短域名（如 abc.com）</p>
                    <p><strong>2. 配置子域名长度：</strong>设置随机子域名的长度（推荐6位）</p>
                    <p><strong>3. 用户访问：</strong>用户访问 abc.com/redirect/ 时，系统会自动生成随机子域名（如 a3x9k2.abc.com）</p>
                    <p><strong>4. 自动跳转：</strong>倒计时结束后自动跳转到随机子域名</p>
                    <p><strong>5. 防封效果：</strong>每次访问都是新域名，有效防止域名被封</p>
                </div>
            </div>

            <!-- 统计面板链接 -->
            <div class="table-section">
                <h2 class="section-title">访问统计面板</h2>
                <p style="margin-bottom: 15px;">
                    <a href="../redirect/stats/" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 600;">
                        打开跳转页统计面板 →
                    </a>
                </p>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        document.getElementById('redirectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            fetch('api/save.php?action=redirect_config', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('配置保存成功！');
                    location.reload();
                } else {
                    alert('保存失败：' + (data.message || '未知错误'));
                }
            })
            .catch(err => {
                alert('保存失败：' + err.message);
            });
        });
    </script>
</body>
</html>
