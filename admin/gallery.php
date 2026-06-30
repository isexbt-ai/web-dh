<?php
/**
 * 相册合集管理
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$galleries = getGalleries(false);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>相册管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
    <style>
        .gallery-preview {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .gallery-preview:hover {
            transform: scale(1.05);
        }
        .gallery-preview-placeholder {
            width: 80px;
            height: 60px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- 侧边栏 -->
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
                <a href="showcase.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>效果展示</span>
                </a>
                <a href="gallery.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>相册管理</span>
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

        <!-- 主内容区 -->
        <main class="main-content">
            <header class="page-header">
                <h1>相册管理</h1>
                <p>管理效果展示的相册合集</p>
            </header>

            <div class="table-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">相册列表</h2>
                    <button class="btn btn-primary" onclick="openModal('galleryModal')">添加相册</button>
                </div>

                <?php if (empty($galleries)): ?>
                <div class="empty-state">暂无相册，点击上方按钮添加</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>封面</th>
                            <th>标题</th>
                            <th>描述</th>
                            <th>内容数</th>
                            <th>排序</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($galleries as $gallery): ?>
                        <tr>
                            <td><?php echo $gallery['id']; ?></td>
                            <td>
                                <?php if ($gallery['cover_image']): ?>
                                    <img src="../<?php echo e($gallery['cover_image']); ?>" class="gallery-preview" alt="<?php echo e($gallery['title']); ?>" onclick="window.open(this.src, '_blank')">
                                <?php else: ?>
                                    <div class="gallery-preview-placeholder">无封面</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($gallery['title']); ?></td>
                            <td><?php echo e($gallery['description'] ?: '-'); ?></td>
                            <td><?php echo getGalleryShowcaseCount($gallery['id']); ?></td>
                            <td><?php echo $gallery['sort_order']; ?></td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?php echo $gallery['is_active'] ? 'checked' : ''; ?> onchange="toggleStatus('gallery', <?php echo $gallery['id']; ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-secondary btn-sm" onclick="editGallery(this)"
                                        data-id="<?php echo $gallery['id']; ?>"
                                        data-title="<?php echo e($gallery['title']); ?>"
                                        data-description="<?php echo e($gallery['description']); ?>"
                                        data-cover_image="<?php echo e($gallery['cover_image']); ?>"
                                        data-sort_order="<?php echo e($gallery['sort_order']); ?>"
                                        data-is_active="<?php echo e($gallery['is_active']); ?>">编辑</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteItem('gallery', <?php echo $gallery['id']; ?>, () => location.reload())">删除</button>
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

    <!-- 添加/编辑相册模态框 -->
    <div class="modal-overlay" id="galleryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="galleryModalTitle">添加相册</h2>
                <button class="modal-close" onclick="closeModal('galleryModal')">&times;</button>
            </div>
            <form id="galleryForm" onsubmit="return saveGallery(event)">
                <input type="hidden" id="galleryId" name="id" value="0">
                <div class="form-group">
                    <label>相册标题</label>
                    <input type="text" id="galleryTitle" name="title" placeholder="请输入相册标题" required>
                </div>
                <div class="form-group">
                    <label>相册描述</label>
                    <textarea id="galleryDescription" name="description" placeholder="请输入相册描述（可选）" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>封面图片</label>
                    <div class="image-upload">
                        <input type="file" id="galleryCoverFile" accept="image/*" onchange="previewGalleryCover(this)">
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">点击上传封面图片</div>
                    </div>
                    <div class="image-upload-preview" id="galleryCoverPreview"></div>
                    <input type="hidden" id="galleryCoverImage" name="cover_image" value="">
                </div>
                <div class="form-group">
                    <label>排序</label>
                    <input type="number" id="gallerySort" name="sort_order" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="galleryActive" name="is_active" value="1" checked>
                        启用
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">保存</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function previewGalleryCover(input) {
            const preview = document.getElementById('galleryCoverPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="预览" style="max-width: 200px; max-height: 150px; border-radius: 8px; object-fit: cover;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function saveGallery(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });

            // 处理封面上传
            const fileInput = document.getElementById('galleryCoverFile');
            if (fileInput.files && fileInput.files[0]) {
                const uploadResult = await uploadImage(fileInput.files[0], 'showcase');
                if (uploadResult.success) {
                    data.cover_image = uploadResult.data.path;
                }
            }

            data.is_active = document.getElementById('galleryActive').checked ? 1 : 0;

            saveData('gallery', data, () => {
                closeModal('galleryModal');
                location.reload();
            });
            return false;
        }

        function editGallery(btn) {
            const id = btn.getAttribute('data-id');
            const title = btn.getAttribute('data-title') || '';
            const description = btn.getAttribute('data-description') || '';
            const coverImage = btn.getAttribute('data-cover_image') || '';
            const sortOrder = parseInt(btn.getAttribute('data-sort_order')) || 0;
            const isActive = parseInt(btn.getAttribute('data-is_active')) || 0;

            document.getElementById('galleryId').value = id;
            document.getElementById('galleryTitle').value = title;
            document.getElementById('galleryDescription').value = description;
            document.getElementById('galleryCoverImage').value = coverImage;
            document.getElementById('gallerySort').value = sortOrder;
            document.getElementById('galleryActive').checked = isActive === 1;

            const preview = document.getElementById('galleryCoverPreview');
            if (coverImage) {
                preview.innerHTML = '<img src="../' + coverImage + '" alt="预览" style="max-width: 200px; max-height: 150px; border-radius: 8px; object-fit: cover;">';
            } else {
                preview.innerHTML = '';
            }

            document.getElementById('galleryModalTitle').textContent = '编辑相册';
            openModal('galleryModal');
        }
    </script>
</body>
</html>
