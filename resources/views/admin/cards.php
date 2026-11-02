<?php
/**
 * 卡片管理（数据：items/categories）
 * 列表按分类分组展示，排序号为「分类内独立」——数字小的排前面。
 */
$items = $items ?? [];
$categories = $categories ?? [];

// 按分类分组（保持分类自身顺序，组内按 sort_order 升序）
$grouped = [];
foreach ($categories as $cat) {
    $grouped[(int) $cat['id']] = ['name' => (string) $cat['name'], 'items' => []];
}
$grouped[0] = ['name' => '未分类', 'items' => []];
foreach ($items as $it) {
    $cid = (int) $it['category_id'];
    if (!isset($grouped[$cid])) {
        $grouped[$cid] = ['name' => (string) ($it['category_name'] ?? '未分类'), 'items' => []];
    }
    $grouped[$cid]['items'][] = $it;
}
?>
<div class="page-header">
    <div><h1>卡片管理</h1><p>首页导航卡片（按分类分组，排序号分类内独立）</p></div>
    <button type="button" class="btn btn-primary" id="btnAdd">新增卡片</button>
</div>

<?php if ($items === []): ?>
<div class="table-section">
    <table class="data-table">
        <tbody>
            <tr><td class="empty-state">暂无卡片</td></tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php foreach ($grouped as $cid => $group): ?>
    <?php if ($group['items'] === []) { continue; } ?>
    <div class="table-section">
        <div class="section-title">
            <?= e($group['name']) ?>
            <span class="form-hint" style="font-weight:400;margin-left:8px;"><?= count($group['items']) ?> 张</span>
        </div>
        <table class="data-table">
            <thead><tr><th>ID</th><th>标题</th><th>类型</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
            <tbody>
                <?php foreach ($group['items'] as $it): ?>
                <tr>
                    <td><?= (int) $it['id'] ?></td>
                    <td class="col-ellipsis"><?= e($it['title']) ?></td>
                    <td><?= $it['card_type'] === 'detail' ? '详情' : '外链' ?></td>
                    <td><?= (int) $it['sort_order'] ?></td>
                    <td><?= (int) $it['is_active'] === 1 ? '<span class="toggle-text on">启用</span>' : '<span class="toggle-text">停用</span>' ?></td>
                    <td class="table-actions">
                        <button type="button" class="btn btn-sm btn-edit"
                            data-id="<?= (int) $it['id'] ?>"
                            data-category_id="<?= (int) $it['category_id'] ?>"
                            data-title="<?= e($it['title']) ?>"
                            data-image="<?= e($it['image']) ?>"
                            data-link="<?= e($it['link']) ?>"
                            data-detail="<?= e($it['detail']) ?>"
                            data-card_type="<?= e($it['card_type']) ?>"
                            data-badge_text="<?= e($it['badge_text']) ?>"
                            data-sort_order="<?= (int) $it['sort_order'] ?>"
                            data-is_active="<?= (int) $it['is_active'] ?>">编辑</button>
                        <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="card" data-id="<?= (int) $it['id'] ?>">删除</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<div class="modal-overlay" id="editModal">
    <div class="modal-content modal-lg">
        <div class="modal-header"><h3 id="modalTitle">新增卡片</h3><button type="button" class="modal-close">&times;</button></div>
        <form id="editForm" data-action="card">
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-row">
                    <div class="form-group"><label>标题</label><input name="title" maxlength="100" required></div>
                    <div class="form-group"><label>分类</label>
                        <select name="category_id">
                            <option value="0">未分类</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>类型</label>
                        <select name="card_type">
                            <option value="link">外链</option>
                            <option value="detail">详情页</option>
                        </select>
                    </div>
                    <div class="form-group"><label>角标文字</label><input name="badge_text" maxlength="10" placeholder="留空自动"></div>
                    <div class="form-group"><label>排序（分类内，小的在前）</label><input name="sort_order" type="number" value="0"></div>
                </div>
                <div class="form-group"><label>跳转链接</label><input name="link" placeholder="https://..."></div>
                <div class="form-group"><label>图片地址</label>
                    <div class="image-upload">
                        <input name="image" id="editImage" placeholder="/uploads/cards/xxx.jpg 或 http(s)://...">
                        <label class="btn btn-sm btn-secondary" style="cursor:pointer;">上传<input type="file" id="editImageFile" accept="image/*" hidden></label>
                    </div>
                    <div class="image-preview" id="editImagePreview"></div>
                </div>
                <div class="form-group"><label>详情内容（支持 ![图](url) 标记）</label><textarea name="detail" rows="4"></textarea></div>
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
