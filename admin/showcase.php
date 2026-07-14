<?php
/**
 * 效果展示管理
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$showcases = getShowcases(false);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>效果展示管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06-.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
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
                <a href="showcase.php" class="nav-item active">
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

        <!-- 主内容区 -->
        <main class="main-content">
            <header class="page-header">
                <h1>效果展示管理</h1>
                <p>管理效果展示图片（支持WebP动图）</p>
            </header>

            <!-- 批量操作 -->
            <div class="batch-actions">
                <button class="btn btn-primary" onclick="openModal('showcaseModal')">添加展示</button>
            </div>

            <div class="table-section">
                <h2 class="section-title">展示列表</h2>
                <?php if (empty($showcases)): ?>
                <div class="empty-state">暂无展示内容，点击上方按钮添加</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>预览</th>
                            <th>标题</th>
                            <th>图片地址</th>
                            <th>排序</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($showcases as $item): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td>
                                <?php if ($item['image']):
                                    // 处理图片路径：外部URL直接使用，本地路径添加../前缀
                                    $imageUrl = $item['image'];
                                    if (strpos($imageUrl, 'http') !== 0) {
                                        $imageUrl = '../' . ltrim($imageUrl, '/');
                                    }
                                    $isVideo = ($item['media_type'] ?? '') === 'video' || preg_match('/\.(mp4|webm|mov)$/i', $item['image'] ?? '');
                                    if ($isVideo): ?>
                                        <video src="<?php echo e($imageUrl); ?>" class="showcase-preview" muted onclick="window.open(this.src, '_blank')" style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer;"></video>
                                    <?php else: ?>
                                        <img src="<?php echo e($imageUrl); ?>" class="showcase-preview" alt="<?php echo e($item['title']); ?>" onclick="window.open(this.src, '_blank')" style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer;">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="width: 80px; height: 60px; background: linear-gradient(135deg, #e94560, #ff6b6b); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff;">无图</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($item['title']); ?></td>
                            <td>
                                <?php if ($item['image']): ?>
                                    <span style="font-size: 12px; color: #666; word-break: break-all;"><?php echo e($item['image']); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['sort_order']; ?></td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?php echo $item['is_active'] ? 'checked' : ''; ?> onchange="toggleStatus('showcase', <?php echo $item['id']; ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-secondary btn-sm" onclick="editShowcase(this)"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-title="<?php echo e($item['title']); ?>"
                                        data-image="<?php echo e($item['image']); ?>"
                                        data-media_type="<?php echo e($item['media_type'] ?? 'image'); ?>"
                                        data-sort_order="<?php echo e($item['sort_order']); ?>"
                                        data-is_active="<?php echo e($item['is_active']); ?>">编辑</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteItem('showcase', <?php echo $item['id']; ?>, () => location.reload())">删除</button>
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

    <!-- 添加/编辑模态框 -->
    <div class="modal-overlay" id="showcaseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="showcaseModalTitle">添加展示</h2>
                <button class="modal-close" onclick="closeModal('showcaseModal')">&times;</button>
            </div>
            <form id="showcaseForm" onsubmit="return saveShowcase(event)">
                <input type="hidden" id="showcaseId" name="id" value="0">
                <div class="form-group">
                    <label>展示标题</label>
                    <input type="text" id="showcaseTitle" name="title" placeholder="请输入展示标题" required>
                </div>
                <div class="form-group">
                    <label>展示图片/视频 URL</label>
                    <input type="text" id="showcaseImageUrl" name="image_url" placeholder="请输入图片或视频 URL（如：https://img.scdn.io/xxx.webp）" style="width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; margin-bottom: 8px;">
                    <p style="font-size: 12px; color: #999; margin-top: 4px;">支持图片（jpg、png、webp、gif）和视频（mp4、webm）的 URL 地址</p>
                    <div class="image-upload-preview" id="showcaseImagePreview"></div>
                    <input type="hidden" id="showcaseImage" name="image" value="">
                    <input type="hidden" id="showcaseMediaType" name="media_type" value="image">
                </div>
                <div class="form-group">
                    <label>排序</label>
                    <input type="number" id="showcaseSort" name="sort_order" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="showcaseActive" name="is_active" value="1" checked>
                        启用
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">保存</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function previewShowcaseMedia(input) {
            const preview = document.getElementById('showcaseImagePreview');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const isVideo = file.type.startsWith('video/');
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (isVideo) {
                        preview.innerHTML = '<video src="' + e.target.result + '" controls style="max-width: 200px; max-height: 150px; border-radius: 8px;"></video>';
                    } else {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="预览" style="max-width: 200px; max-height: 150px; border-radius: 8px; object-fit: cover;">';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function saveShowcase(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });

            // 从 URL 输入框获取图片地址
            const imageUrl = document.getElementById('showcaseImageUrl').value.trim();
            if (imageUrl) {
                data.image = imageUrl;
                // 自动判断媒体类型
                const isVideo = /\.(mp4|webm|mov)$/i.test(imageUrl);
                data.media_type = isVideo ? 'video' : 'image';
            }

            data.is_active = document.getElementById('showcaseActive').checked ? 1 : 0;

            saveData('showcase', data, () => {
                closeModal('showcaseModal');
                location.reload();
            });
            return false;
        }

        function editShowcase(btn) {
            const id = btn.getAttribute('data-id');
            const title = btn.getAttribute('data-title') || '';
            const image = btn.getAttribute('data-image') || '';
            const mediaType = btn.getAttribute('data-media_type') || 'image';
            const sortOrder = parseInt(btn.getAttribute('data-sort_order')) || 0;
            const isActive = parseInt(btn.getAttribute('data-is_active')) || 0;

            document.getElementById('showcaseId').value = id;
            document.getElementById('showcaseTitle').value = title;
            document.getElementById('showcaseImage').value = image;
            document.getElementById('showcaseImageUrl').value = image; // 填充 URL 输入框
            document.getElementById('showcaseMediaType').value = mediaType;
            document.getElementById('showcaseSort').value = sortOrder;
            document.getElementById('showcaseActive').checked = isActive === 1;

            const preview = document.getElementById('showcaseImagePreview');
            if (image) {
                const isVideo = mediaType === 'video' || image.toLowerCase().endsWith('.mp4') || image.toLowerCase().endsWith('.webm') || image.toLowerCase().endsWith('.mov');
                if (isVideo) {
                    preview.innerHTML = '<video src="' + image + '" controls style="max-width: 200px; max-height: 150px; border-radius: 8px;"></video>';
                } else {
                    preview.innerHTML = '<img src="' + image + '" alt="预览" style="max-width: 200px; max-height: 150px; border-radius: 8px; object-fit: cover;">';
                }
            } else {
                preview.innerHTML = '';
            }

            document.getElementById('showcaseModalTitle').textContent = '编辑展示';
            openModal('showcaseModal');
        }

        async function toggleStatus(type, id, active) {
            const data = { id: id, is_active: active ? 1 : 0 };
            saveData(type, data);
        }
    </script>
</body>
</html>
