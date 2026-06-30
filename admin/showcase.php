<?php
/**
 * 效果展示管理
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$showcases = getShowcases(false);
$galleries = getGalleries(false);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>效果展示管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
    <style>
        /* 图床状态标签 */
        .imgbed-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .imgbed-status.uploaded {
            background: rgba(78, 204, 163, 0.15);
            color: #4ecca3;
        }
        .imgbed-status.pending {
            background: rgba(249, 168, 37, 0.15);
            color: #f9a825;
        }
        .imgbed-status.failed {
            background: rgba(244, 67, 54, 0.15);
            color: #f44336;
        }

        /* 批量操作按钮 */
        .batch-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .btn-upload-imgbed {
            background: linear-gradient(135deg, #7c4dff, #b388ff);
            color: #fff;
            border: none;
        }

        .btn-upload-imgbed:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-upload-imgbed:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* 上传进度 */
        .upload-progress {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .upload-progress.active {
            display: flex;
        }

        .upload-progress-bar {
            flex: 1;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .upload-progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #7c4dff, #b388ff);
            border-radius: 3px;
            transition: width 0.3s ease;
            width: 0%;
        }

        .upload-progress-text {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
        }

        /* 图片预览 */
        .showcase-preview {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .showcase-preview:hover {
            transform: scale(1.05);
        }

        /* 模态框图片上传 */
        .image-upload-preview {
            margin-top: 12px;
        }

        .image-upload-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            object-fit: cover;
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

            <!-- 相册列表 -->
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
                                    <img src="../<?php echo e($gallery['cover_image']); ?>" class="showcase-preview" alt="<?php echo e($gallery['title']); ?>" onclick="window.open(this.src, '_blank')">
                                <?php else: ?>
                                    <div style="width: 80px; height: 60px; background: linear-gradient(135deg, #e94560, #ff6b6b); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff;">无封面</div>
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

            <!-- 批量操作 -->
            <div class="batch-actions">
                <button class="btn btn-primary" onclick="openModal('showcaseModal')">添加展示</button>
                <button class="btn btn-primary" onclick="openModal('galleryModal')" style="background: linear-gradient(135deg, #00bcd4, #4dd0e1); border: none;">添加相册</button>
                <button class="btn btn-upload-imgbed" id="btnUploadImgbed" onclick="batchUploadToImgbed()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 4px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    批量上传到图床
                </button>
            </div>

            <!-- 上传进度 -->
            <div class="upload-progress" id="uploadProgress">
                <div class="upload-progress-bar">
                    <div class="upload-progress-fill" id="progressFill"></div>
                </div>
                <span class="upload-progress-text" id="progressText">准备上传...</span>
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
                            <th>所属相册</th>
                            <th>本地图片</th>
                            <th>图床状态</th>
                            <th>图床URL</th>
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
                                <?php if ($item['image']): ?>
                                    <?php
                                    $isVideo = ($item['media_type'] ?? '') === 'video' || preg_match('/\.(mp4|webm|mov)$/i', $item['image'] ?? '');
                                    if ($isVideo): ?>
                                        <video src="../<?php echo e($item['image']); ?>" class="showcase-preview" style="object-fit: cover;" muted onclick="window.open(this.src, '_blank')"></video>
                                    <?php else: ?>
                                        <img src="../<?php echo e($item['image']); ?>" class="showcase-preview" alt="<?php echo e($item['title']); ?>" onclick="window.open(this.src, '_blank')">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="width: 80px; height: 60px; background: linear-gradient(135deg, #e94560, #ff6b6b); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff;">无图</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($item['title']); ?></td>
                            <td>
                                <?php
                                $galleryName = '默认相册';
                                $foundGallery = false;
                                foreach ($galleries as $g) {
                                    if ($g['id'] == ($item['gallery_id'] ?? 1)) {
                                        $galleryName = $g['title'];
                                        $foundGallery = true;
                                        break;
                                    }
                                }
                                // 如果gallery_id存在但找不到对应相册，显示相册ID
                                if (!$foundGallery && !empty($item['gallery_id']) && $item['gallery_id'] != 1) {
                                    $galleryName = '相册ID:' . $item['gallery_id'];
                                }
                                echo e($galleryName);
                                ?>
                            </td>
                            <td>
                                <?php if ($item['image']): ?>
                                    <span style="font-size: 12px; color: #666; word-break: break-all;"><?php echo e($item['image']); ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['imgbed_status'] == 1): ?>
                                    <span class="imgbed-status uploaded">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 12px; height: 12px;"><polyline points="20 6 9 17 4 12"/></svg>
                                        已上传
                                    </span>
                                <?php elseif ($item['imgbed_status'] == 2): ?>
                                    <span class="imgbed-status failed">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 12px; height: 12px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        失败
                                    </span>
                                <?php else: ?>
                                    <span class="imgbed-status pending">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 12px; height: 12px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        待上传
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['imgbed_url']): ?>
                                    <a href="<?php echo e($item['imgbed_url']); ?>" target="_blank" style="font-size: 12px; color: #7c4dff; word-break: break-all;">
                                        <?php echo e(mb_substr($item['imgbed_url'], 0, 40)); ?><?php echo mb_strlen($item['imgbed_url']) > 40 ? '...' : ''; ?>
                                    </a>
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
                                        data-gallery_id="<?php echo e($item['gallery_id'] ?? 1); ?>"
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
                    <label>展示图片/视频</label>
                    <div class="image-upload">
                        <input type="file" id="showcaseImageFile" accept="image/*,video/*" onchange="previewShowcaseMedia(this)">
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">点击上传图片或视频（支持 WebP 动图、MP4、WebM）</div>
                    </div>
                    <div class="image-upload-preview" id="showcaseImagePreview"></div>
                    <input type="hidden" id="showcaseImage" name="image" value="">
                    <input type="hidden" id="showcaseMediaType" name="media_type" value="image">
                </div>
                <div class="form-group">
                    <label>所属相册</label>
                    <select id="showcaseGallery" name="gallery_id" required>
                        <option value="">请选择相册</option>
                        <?php foreach ($galleries as $g): ?>
                        <option value="<?php echo $g['id']; ?>"><?php echo e($g['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                reader.readAsDataURL(file);
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

        async function saveShowcase(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });

            // 处理文件上传
            const fileInput = document.getElementById('showcaseImageFile');
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                const isVideo = file.type.startsWith('video/');
                data.media_type = isVideo ? 'video' : 'image';

                const uploadResult = await uploadImage(file, 'showcase');
                if (uploadResult.success) {
                    data.image = uploadResult.data.path;
                }
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
            const galleryId = parseInt(btn.getAttribute('data-gallery_id')) || 1;
            const sortOrder = parseInt(btn.getAttribute('data-sort_order')) || 0;
            const isActive = parseInt(btn.getAttribute('data-is_active')) || 0;

            document.getElementById('showcaseId').value = id;
            document.getElementById('showcaseTitle').value = title;
            document.getElementById('showcaseImage').value = image;
            document.getElementById('showcaseMediaType').value = mediaType;
            document.getElementById('showcaseGallery').value = galleryId;
            document.getElementById('showcaseSort').value = sortOrder;
            document.getElementById('showcaseActive').checked = isActive === 1;

            const preview = document.getElementById('showcaseImagePreview');
            if (image) {
                const isVideo = mediaType === 'video' || image.toLowerCase().endsWith('.mp4') || image.toLowerCase().endsWith('.webm') || image.toLowerCase().endsWith('.mov');
                if (isVideo) {
                    preview.innerHTML = '<video src="../' + image + '" controls style="max-width: 200px; max-height: 150px; border-radius: 8px;"></video>';
                } else {
                    preview.innerHTML = '<img src="../' + image + '" alt="预览" style="max-width: 200px; max-height: 150px; border-radius: 8px; object-fit: cover;">';
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

        // 批量上传到图床
        async function batchUploadToImgbed() {
            const btn = document.getElementById('btnUploadImgbed');
            const progress = document.getElementById('uploadProgress');
            const fill = document.getElementById('progressFill');
            const text = document.getElementById('progressText');

            btn.disabled = true;
            progress.classList.add('active');

            try {
                const response = await fetch('api/upload_to_imgbed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({})
                });

                const result = await response.json();

                if (result.success) {
                    fill.style.width = '100%';
                    text.textContent = `上传完成：成功 ${result.data.success} 个，失败 ${result.data.failed} 个`;
                    showToast(`批量上传完成！成功 ${result.data.success} 个，失败 ${result.data.failed} 个`, 'success');

                    // 显示详细结果
                    if (result.data.failed > 0) {
                        const failedItems = result.data.details.filter(d => d.status === 'failed');
                        console.log('上传失败详情:', failedItems);
                    }

                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    text.textContent = '上传失败：' + (result.message || '未知错误');
                    showToast(result.message || '批量上传失败', 'error');
                    btn.disabled = false;
                }
            } catch (error) {
                text.textContent = '上传出错：' + error.message;
                showToast('批量上传出错：' + error.message, 'error');
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
