<?php
/**
 * 广告管理（数据：items）
 */
$items = $items ?? [];
?>
<div class="page-header">
    <div><h1>广告管理</h1><p>首页轮播图广告位</p></div>
    <button type="button" class="btn btn-primary" id="btnAdd">新增广告</button>
</div>

<div class="table-section">
    <table class="data-table">
        <thead><tr><th>ID</th><th>标题</th><th>图片</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
            <?php if ($items === []): ?>
            <tr><td colspan="6" class="empty-state">暂无广告</td></tr>
            <?php else: foreach ($items as $it): ?>
            <tr>
                <td><?= (int) $it['id'] ?></td>
                <td><?= e($it['title']) ?></td>
                <td><?= $it['image'] !== '' ? '<img src="' . e($it['image']) . '" style="height:36px;border-radius:6px;" alt="">' : '<span class="form-hint">无</span>' ?></td>
                <td><?= (int) $it['sort_order'] ?></td>
                <td><?= (int) $it['is_active'] === 1 ? '<span class="toggle-text on">启用</span>' : '<span class="toggle-text">停用</span>' ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-edit"
                        data-id="<?= (int) $it['id'] ?>"
                        data-title="<?= e($it['title']) ?>"
                        data-image="<?= e($it['image']) ?>"
                        data-link="<?= e($it['link']) ?>"
                        data-sort_order="<?= (int) $it['sort_order'] ?>"
                        data-is_active="<?= (int) $it['is_active'] ?>">编辑</button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="ad" data-id="<?= (int) $it['id'] ?>">删除</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">新增广告</h3><button type="button" class="modal-close">&times;</button></div>
        <form id="editForm" data-action="ad">
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-group"><label>标题</label><input name="title" maxlength="100" required></div>
                <div class="form-group"><label>图片地址</label><input name="image" placeholder="/uploads/ads/xxx.jpg 或 http(s)://..."></div>
                <div class="form-group"><label>跳转链接</label><input name="link" placeholder="https://..."></div>
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
