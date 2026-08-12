<?php
/**
 * 效果展示管理（数据：items/galleries）
 */
$items = $items ?? [];
$galleries = $galleries ?? [];
?>
<div class="page-header">
    <div><h1>效果展示</h1><p>展示墙图片/视频</p></div>
    <button type="button" class="btn btn-primary" id="btnAdd">新增展示</button>
</div>

<div class="table-section">
    <table class="data-table">
        <thead><tr><th>ID</th><th>标题</th><th>类型</th><th>相册</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
            <?php if ($items === []): ?>
            <tr><td colspan="7" class="empty-state">暂无展示</td></tr>
            <?php else: foreach ($items as $it): ?>
            <tr>
                <td><?= (int) $it['id'] ?></td>
                <td class="col-ellipsis"><?= e($it['title']) ?></td>
                <td><?= $it['media_type'] === 'video' ? '视频' : '图片' ?></td>
                <td><?= e($it['gallery_title'] ?? "相册#{$it['gallery_id']}") ?></td>
                <td><?= (int) $it['sort_order'] ?></td>
                <td><?= (int) $it['is_active'] === 1 ? '<span class="toggle-text on">启用</span>' : '<span class="toggle-text">停用</span>' ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-edit"
                        data-id="<?= (int) $it['id'] ?>"
                        data-title="<?= e($it['title']) ?>"
                        data-image="<?= e($it['image']) ?>"
                        data-media_type="<?= e($it['media_type']) ?>"
                        data-gallery_id="<?= (int) $it['gallery_id'] ?>"
                        data-sort_order="<?= (int) $it['sort_order'] ?>"
                        data-is_active="<?= (int) $it['is_active'] ?>">编辑</button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="showcase" data-id="<?= (int) $it['id'] ?>">删除</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">新增展示</h3><button type="button" class="modal-close">&times;</button></div>
        <form id="editForm" data-action="showcase">
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-group"><label>标题</label><input name="title" maxlength="100" required></div>
                <div class="form-row">
                    <div class="form-group"><label>类型</label>
                        <select name="media_type">
                            <option value="image">图片</option>
                            <option value="video">视频</option>
                        </select>
                    </div>
                    <div class="form-group"><label>相册</label>
                        <select name="gallery_id">
                            <?php foreach ($galleries as $g): ?>
                            <option value="<?= (int) $g['id'] ?>"><?= e($g['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>图片/视频地址</label>
                    <div class="image-upload">
                        <input name="image" id="editImage" placeholder="/uploads/showcase/xxx.jpg 或 https://...">
                        <label class="btn btn-sm btn-secondary" style="cursor:pointer;">上传<input type="file" id="editImageFile" accept="image/*" hidden></label>
                    </div>
                    <div class="image-preview" id="editImagePreview"></div>
                </div>
                <div class="form-group"><label>排序</label><input name="sort_order" type="number" value="0"></div>
                <div class="form-group">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-text">启用</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">保存</button></div>
        </form>
    </div>
</div>

<div class="table-section" style="margin-top:24px;">
    <h2 class="section-title">相册管理</h2>
    <table class="data-table">
        <thead><tr><th>ID</th><th>名称</th><th>说明</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
            <?php foreach ($galleries as $g): ?>
            <tr>
                <td><?= (int) $g['id'] ?></td>
                <td><?= e($g['title']) ?></td>
                <td class="col-ellipsis"><?= e($g['description']) ?></td>
                <td><?= (int) $g['sort_order'] ?></td>
                <td><?= (int) $g['is_active'] === 1 ? '<span class="toggle-text on">启用</span>' : '<span class="toggle-text">停用</span>' ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-edit"
                        data-id="<?= (int) $g['id'] ?>"
                        data-title="<?= e($g['title']) ?>"
                        data-description="<?= e($g['description']) ?>"
                        data-sort_order="<?= (int) $g['sort_order'] ?>"
                        data-is_active="<?= (int) $g['is_active'] ?>">编辑</button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="gallery" data-id="<?= (int) $g['id'] ?>">删除</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="margin-top:12px;">
        <button type="button" class="btn btn-secondary" id="btnAddGallery">新增相册</button>
    </div>
</div>

<div class="modal-overlay" id="galleryModal">
    <div class="modal-content">
        <div class="modal-header"><h3>新增相册</h3><button type="button" class="modal-close">&times;</button></div>
        <form id="galleryForm" data-action="gallery">
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-group"><label>名称</label><input name="title" maxlength="50" required></div>
                <div class="form-group"><label>说明</label><textarea name="description" rows="2" maxlength="300"></textarea></div>
                <div class="form-group"><label>排序</label><input name="sort_order" type="number" value="0"></div>
                <div class="form-group">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-text">启用</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">保存</button></div>
        </form>
    </div>
</div>
