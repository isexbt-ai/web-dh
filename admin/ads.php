<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$ads = getAds(false);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>广告管理 - 后台管理</title>
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
                <a href="ads.php" class="nav-item active">
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
                <a href="showcase.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>效果展示</span>
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
                <h1>广告管理</h1>
                <p>管理网站的广告位内容</p>
            </header>

            <div class="table-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">广告列表</h2>
                    <button class="btn btn-primary" onclick="openModal('adModal')">添加广告</button>
                </div>

                <?php if (empty($ads)): ?>
                <div class="empty-state">暂无广告，点击上方按钮添加</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>标题</th>
                            <th>图片</th>
                            <th>链接</th>
                            <th>排序</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ads as $ad): ?>
                        <tr>
                            <td><?php echo $ad['id']; ?></td>
                            <td><?php echo e($ad['title']); ?></td>
                            <td>
                                <?php if ($ad['image']): ?>
                                    <img src="../<?php echo e($ad['image']); ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 6px;">
                                <?php else: ?>
                                    <span style="color: #cccccc;">无图片</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($ad['link']); ?></td>
                            <td><?php echo $ad['sort_order']; ?></td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?php echo $ad['is_active'] ? 'checked' : ''; ?> onchange="toggleStatus('ad', <?php echo $ad['id']; ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-secondary btn-sm" onclick="editAd(this)" data-title="<?php echo e($ad['title']); ?>" data-image="<?php echo e($ad['image']); ?>" data-link="<?php echo e($ad['link']); ?>" data-sort_order="<?php echo e($ad['sort_order']); ?>" data-is_active="<?php echo e($ad['is_active']); ?>" style="padding: 6px 12px; font-size: 12px; background: #f8f9fa; border: 1px solid #e0e0e0; color: #333333; border-radius: 8px; cursor: pointer; transition: all 0.3s;">编辑</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteItem('ad', <?php echo $ad['id']; ?>, () => location.reload())" style="padding: 6px 12px; font-size: 12px; background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.2); color: #f44336; border-radius: 8px; cursor: pointer; transition: all 0.3s;">删除</button>
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

    <!-- 添加/编辑广告模态框 -->
    <div class="modal-overlay" id="adModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="adModalTitle">添加广告</h2>
                <button class="modal-close" onclick="closeModal('adModal')">&times;</button>
            </div>
            <form id="adForm" onsubmit="return saveAd(event)">
                <input type="hidden" id="adId" name="id" value="0">
                <div class="form-group">
                    <label>广告标题</label>
                    <input type="text" id="adTitle" name="title" placeholder="请输入广告标题" required>
                </div>
                <div class="form-group">
                    <label>广告图片</label>
                    <div class="image-upload">
                        <input type="file" id="adImageFile" accept="image/*" onchange="previewAdImage(this)">
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">点击上传广告图片（支持 GIF）</div>
                    </div>
                    <div id="adImagePreview" style="margin-top: 12px;"></div>
                    <input type="hidden" id="adImage" name="image" value="">
                </div>
                <div class="form-group">
                    <label>跳转链接</label>
                    <input type="text" id="adLink" name="link" placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label>排序</label>
                    <input type="number" id="adSort" name="sort_order" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="adActive" name="is_active" value="1" checked>
                        启用
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">保存</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function previewAdImage(input) {
            const preview = document.getElementById('adImagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; border-radius: 8px;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function saveAd(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });

            // 处理图片上传
            const fileInput = document.getElementById('adImageFile');
            if (fileInput.files && fileInput.files[0]) {
                const uploadResult = await uploadImage(fileInput.files[0], 'ads');
                if (uploadResult.success) {
                    data.image = uploadResult.data.path;
                }
            }

            data.is_active = document.getElementById('adActive').checked ? 1 : 0;

            saveData('ad', data, () => {
                closeModal('adModal');
                location.reload();
            });
            return false;
        }

        function editAd(btn) {
            const id = btn.closest('tr').querySelector('td').textContent.trim();
            const title = btn.getAttribute('data-title') || '';
            const image = btn.getAttribute('data-image') || '';
            const link = btn.getAttribute('data-link') || '';
            const sortOrder = parseInt(btn.getAttribute('data-sort_order')) || 0;
            const isActive = parseInt(btn.getAttribute('data-is_active')) || 0;

            document.getElementById('adId').value = id;
            document.getElementById('adTitle').value = title;
            document.getElementById('adLink').value = link;
            document.getElementById('adSort').value = sortOrder;
            document.getElementById('adActive').checked = isActive === 1;
            document.getElementById('adImage').value = image;

            const preview = document.getElementById('adImagePreview');
            if (image) {
                preview.innerHTML = '<img src="../' + image + '" style="max-width: 200px; border-radius: 8px;">';
            } else {
                preview.innerHTML = '';
            }

            document.getElementById('adModalTitle').textContent = '编辑广告';
            openModal('adModal');
        }

        async function toggleStatus(type, id, active) {
            const data = { id: id, is_active: active ? 1 : 0 };
            saveData(type, data);
        }
    </script>
</body>
</html>
