<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$categoryFilter = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$cards = getCards($categoryFilter, false);
$categories = getCategories(false);

// 获取当前筛选的分类名称
$currentCategoryName = '全部';
if ($categoryFilter) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryFilter) {
            $currentCategoryName = $cat['name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <title>卡片管理 - 后台管理</title>
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
                <a href="cards.php" class="nav-item active">
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
                <a href="ip_stats.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <span>IP统计</span>
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
                <h1>卡片管理</h1>
                <p>管理导航卡片内容</p>
            </header>

            <div class="table-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <h2 class="section-title" style="margin-bottom: 0;">卡片列表</h2>
                        <select onchange="location.href='cards.php?category_id=' + this.value" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #e0e0e0; background: #ffffff; color: #1a1a2e; font-size: 14px;">
                            <option value="">全部分类</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo e($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="color: #999999; font-size: 14px;">共 <?php echo count($cards); ?> 条</span>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('cardModal')">添加卡片</button>
                </div>

                <?php if (empty($cards)): ?>
                <div class="empty-state">暂无卡片，点击上方按钮添加</div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>图片</th>
                            <th>标题</th>
                            <th>分类</th>
                            <th>类型</th>
                            <th>点击</th>
                            <th>热门</th>
                            <th>排序</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $card): ?>
                        <tr>
                            <td><?php echo $card['id']; ?></td>
                            <td>
                                <?php if ($card['image']): ?>
                                    <img src="../<?php echo e($card['image']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #e94560, #ff6b6b); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff;">图片</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($card['title']); ?></td>
                            <td><?php echo e($card['category_name'] ?? '未分类'); ?></td>
                            <td>
                                <?php if (($card['card_type'] ?? 'link') === 'detail'): ?>
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; background: rgba(233, 69, 96, 0.1); color: #e94560;">详情页</span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; background: rgba(78, 204, 163, 0.1); color: #4ecca3;">外链</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $card['click_count']; ?></td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?php echo ($card['is_hot'] ?? 0) ? 'checked' : ''; ?> onchange="toggleHot(<?php echo $card['id']; ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td><?php echo $card['sort_order']; ?></td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?php echo $card['is_active'] ? 'checked' : ''; ?> onchange="toggleStatus('card', <?php echo $card['id']; ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-secondary btn-sm" onclick="editCard(this)" data-category_id="<?php echo e($card['category_id']); ?>" data-title="<?php echo e($card['title']); ?>" data-image="<?php echo e($card['image']); ?>" data-link="<?php echo e($card['link']); ?>" data-detail="<?php echo e($card['detail']); ?>" data-sort_order="<?php echo e($card['sort_order']); ?>" data-is_active="<?php echo e($card['is_active']); ?>" data-card_type="<?php echo e($card['card_type']); ?>" data-badge_text="<?php echo e($card['badge_text']); ?>" data-is_hot="<?php echo e($card['is_hot'] ?? 0); ?>" style="padding: 6px 12px; font-size: 12px; background: #f8f9fa; border: 1px solid #e0e0e0; color: #333333; border-radius: 8px; cursor: pointer; transition: all 0.3s;">编辑</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteItem('card', <?php echo $card['id']; ?>, () => location.reload())" style="padding: 6px 12px; font-size: 12px; background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.2); color: #f44336; border-radius: 8px; cursor: pointer; transition: all 0.3s;">删除</button>
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

    <!-- 添加/编辑卡片模态框 -->
    <div class="modal-overlay" id="cardModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="cardModalTitle">添加卡片</h2>
                <button class="modal-close" onclick="closeModal('cardModal')">&times;</button>
            </div>
            <form id="cardForm" onsubmit="return saveCard(event)">
                <input type="hidden" id="cardId" name="id" value="0">
                <div class="form-group">
                    <label>所属分类</label>
                    <select id="cardCategory" name="category_id" required>
                        <option value="">请选择分类</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>卡片标题</label>
                    <input type="text" id="cardTitle" name="title" placeholder="请输入卡片标题" required>
                </div>
                <div class="form-group">
                    <label>卡片图片</label>
                    <div class="image-upload">
                        <input type="file" id="cardImageFile" accept="image/*" onchange="previewCardImage(this)">
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">点击上传卡片图片（支持 GIF）</div>
                    </div>
                    <div id="cardImagePreview" style="margin-top: 12px;"></div>
                    <input type="hidden" id="cardImage" name="image" value="">
                </div>
                <div class="form-group">
                    <label>卡片类型</label>
                    <select id="cardType" name="card_type" required>
                        <option value="link">外部链接（点击跳转外部网站）</option>
                        <option value="detail">产品详情（点击跳转到详情页）</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>跳转链接</label>
                    <input type="text" id="cardLink" name="link" placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label>详细介绍</label>
                    <div class="detail-toolbar">
                        <button type="button" class="toolbar-btn" onclick="insertImageFromFile()" title="上传图片">
                            📷 上传图片
                        </button>
                        <button type="button" class="toolbar-btn" onclick="insertImageFromURL()" title="插入URL图片">
                            🔗 URL图片
                        </button>
                        <span class="toolbar-hint">提示：也可以直接粘贴图片</span>
                    </div>
                    <textarea id="cardDetail" name="detail" placeholder="请输入产品的详细介绍内容..." rows="8"></textarea>
                    <input type="file" id="detailImageFile" accept="image/*" style="display:none" onchange="handleDetailImageUpload(this)">
                </div>
                <div class="form-group">
                    <label>自定义角标</label>
                    <input type="text" id="cardBadgeText" name="badge_text" placeholder="如：热门、推荐、NEW" maxlength="10">
                    <small style="color: #999999; font-size: 12px; display: block; margin-top: 4px;">留空则显示默认角标（外链/详情）</small>
                </div>
                <div class="form-group">
                    <label>排序</label>
                    <input type="number" id="cardSort" name="sort_order" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="cardActive" name="is_active" value="1" checked>
                        启用
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="cardHot" name="is_hot" value="1">
                        标记为热门（首页热门推荐显示）
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">保存</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function previewCardImage(input) {
            const preview = document.getElementById('cardImagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; border-radius: 8px;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function saveCard(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });

            // 处理图片上传
            const fileInput = document.getElementById('cardImageFile');
            if (fileInput.files && fileInput.files[0]) {
                const uploadResult = await uploadImage(fileInput.files[0], 'cards');
                if (uploadResult.success) {
                    data.image = uploadResult.data.path;
                }
            }

            data.is_active = document.getElementById('cardActive').checked ? 1 : 0;

            saveData('card', data, () => {
                closeModal('cardModal');
                location.reload();
            });
            return false;
        }

        // ==================== 详情图片插入功能 ====================

        // 监听粘贴事件
        document.getElementById('cardDetail').addEventListener('paste', async function(e) {
            const items = e.clipboardData.items;
            for (let item of items) {
                if (item.type.startsWith('image/')) {
                    e.preventDefault();
                    const file = item.getAsFile();
                    showToast('正在上传粘贴的图片...', 'warning');
                    const result = await uploadImage(file, 'cards');
                    if (result.success) {
                        insertImageTag(result.data.path, '粘贴的图片');
                        showToast('图片上传成功', 'success');
                    } else {
                        showToast(result.message || '上传失败', 'error');
                    }
                }
            }
        });

        // 从文件上传图片
        function insertImageFromFile() {
            document.getElementById('detailImageFile').click();
        }

        async function handleDetailImageUpload(input) {
            if (input.files && input.files[0]) {
                showToast('正在上传图片...', 'warning');
                const result = await uploadImage(input.files[0], 'cards');
                if (result.success) {
                    insertImageTag(result.data.path, '');
                    showToast('图片上传成功', 'success');
                } else {
                    showToast(result.message || '上传失败', 'error');
                }
                input.value = '';
            }
        }

        // 从URL插入图片
        function insertImageFromURL() {
            const url = prompt('请输入图片URL地址：', 'https://');
            if (url && url.trim()) {
                insertImageTag(url.trim(), '');
            }
        }

        // 在详情文本框中插入图片标记
        function insertImageTag(url, alt) {
            const textarea = document.getElementById('cardDetail');
            const tag = '![' + (alt || '图片') + '](' + url + ')';
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const before = textarea.value.substring(0, start);
            const after = textarea.value.substring(end);
            textarea.value = before + tag + '\n' + after;
            textarea.selectionStart = textarea.selectionEnd = start + tag.length + 1;
            textarea.focus();
        }

        function editCard(btn) {
            const id = btn.closest('tr').querySelector('td').textContent.trim();
            const categoryId = parseInt(btn.getAttribute('data-category_id')) || 0;
            const title = btn.getAttribute('data-title') || '';
            const image = btn.getAttribute('data-image') || '';
            const link = btn.getAttribute('data-link') || '';
            const detail = btn.getAttribute('data-detail') || '';
            const sortOrder = parseInt(btn.getAttribute('data-sort_order')) || 0;
            const isActive = parseInt(btn.getAttribute('data-is_active')) || 0;
            const cardType = btn.getAttribute('data-card_type') || '';
            const badgeText = btn.getAttribute('data-badge_text') || '';
            const isHot = parseInt(btn.getAttribute('data-is_hot')) || 0;

            document.getElementById('cardId').value = id;
            document.getElementById('cardCategory').value = categoryId || '';
            document.getElementById('cardTitle').value = title;
            document.getElementById('cardLink').value = link;
            document.getElementById('cardDetail').value = detail || '';
            document.getElementById('cardSort').value = sortOrder;
            document.getElementById('cardActive').checked = isActive === 1;
            document.getElementById('cardImage').value = image;
            document.getElementById('cardType').value = cardType || 'link';
            document.getElementById('cardBadgeText').value = badgeText || '';
            document.getElementById('cardHot').checked = isHot === 1;

            const preview = document.getElementById('cardImagePreview');
            if (image) {
                preview.innerHTML = '<img src="../' + image + '" style="max-width: 200px; border-radius: 8px;">';
            } else {
                preview.innerHTML = '';
            }

            document.getElementById('cardModalTitle').textContent = '编辑卡片';
            openModal('cardModal');
        }

        async function toggleStatus(type, id, active) {
            const data = { id: id, is_active: active ? 1 : 0 };
            saveData(type, data);
        }

        async function toggleHot(id, isHot) {
            const data = { id: id, is_hot: isHot ? 1 : 0 };
            saveData('card', data);
        }
    </script>
</body>
</html>
