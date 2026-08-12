<?php
/**
 * 链接管理（数据：items）
 */
$items = $items ?? [];
?>
<div class="page-header">
    <div><h1>链接管理</h1><p>页面底部/功能菜单链接</p></div>
    <button type="button" class="btn btn-primary" id="btnAdd">新增链接</button>
</div>

<div class="table-section">
    <table class="data-table">
        <thead><tr><th>ID</th><th>标题</th><th>地址</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
            <?php if ($items === []): ?>
            <tr><td colspan="6" class="empty-state">暂无链接</td></tr>
            <?php else: foreach ($items as $it): ?>
            <tr>
                <td><?= (int) $it['id'] ?></td>
                <td><?= e($it['title']) ?></td>
                <td class="col-ellipsis"><?= e($it['url']) ?></td>
                <td><?= (int) $it['sort_order'] ?></td>
                <td><?= (int) $it['is_active'] === 1 ? '<span class="toggle-text on">启用</span>' : '<span class="toggle-text">停用</span>' ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-edit"
                        data-id="<?= (int) $it['id'] ?>"
                        data-title="<?= e($it['title']) ?>"
                        data-url="<?= e($it['url']) ?>"
                        data-icon="<?= e($it['icon']) ?>"
                        data-sort_order="<?= (int) $it['sort_order'] ?>"
                        data-is_active="<?= (int) $it['is_active'] ?>">编辑</button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="link" data-id="<?= (int) $it['id'] ?>">删除</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">新增链接</h3><button type="button" class="modal-close">&times;</button></div>
        <form id="editForm" data-action="link">
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-group"><label>标题</label><input name="title" maxlength="50" required></div>
                <div class="form-group"><label>地址</label><input name="url" placeholder="https://..." required></div>
                <div class="form-group"><label>图标</label><input name="icon" placeholder="可选，图片地址或 SVG 内容"></div>
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
