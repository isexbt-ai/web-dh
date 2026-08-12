<?php
/**
 * 文章管理（数据：items）
 */
$items = $items ?? [];
?>
<div class="page-header">
    <div><h1>文章管理</h1><p>文章列表与编辑</p></div>
    <button type="button" class="btn btn-primary" id="btnAdd">新增文章</button>
</div>

<div class="table-section">
    <table class="data-table">
        <thead><tr><th>ID</th><th>标题</th><th>摘要</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
            <?php if ($items === []): ?>
            <tr><td colspan="5" class="empty-state">暂无文章</td></tr>
            <?php else: foreach ($items as $it): ?>
            <tr>
                <td><?= (int) $it['id'] ?></td>
                <td class="col-ellipsis"><?= e($it['title']) ?></td>
                <td class="col-ellipsis"><?= e(mb_strimwidth((string) $it['summary'], 0, 40, '…')) ?></td>
                <td><?= (int) $it['is_active'] === 1 ? '<span class="toggle-text on">发布</span>' : '<span class="toggle-text">草稿</span>' ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-edit"
                        data-id="<?= (int) $it['id'] ?>"
                        data-title="<?= e($it['title']) ?>"
                        data-slug="<?= e($it['slug']) ?>"
                        data-summary="<?= e($it['summary']) ?>"
                        data-content="<?= e($it['content']) ?>"
                        data-cover_image="<?= e($it['cover_image']) ?>"
                        data-keywords="<?= e($it['keywords']) ?>"
                        data-is_active="<?= (int) $it['is_active'] ?>">编辑</button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="article" data-id="<?= (int) $it['id'] ?>">删除</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-content modal-lg">
        <div class="modal-header"><h3 id="modalTitle">新增文章</h3><button type="button" class="modal-close">&times;</button></div>
        <form id="editForm" data-action="article">
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-group"><label>标题</label><input name="title" maxlength="200" required></div>
                <div class="form-group"><label>URL 别名（slug，留空自动生成）</label><input name="slug" maxlength="50" placeholder="example-article"></div>
                <div class="form-group"><label>摘要</label><textarea name="summary" rows="2" maxlength="300"></textarea></div>
                <div class="form-group"><label>关键词</label><input name="keywords" maxlength="200" placeholder="用英文逗号分隔"></div>
                <div class="form-group"><label>封面图</label>
                    <div class="image-upload">
                        <input name="cover_image" id="editCoverImage" placeholder="/uploads/articles/xxx.jpg">
                        <label class="btn btn-sm btn-secondary" style="cursor:pointer;">上传<input type="file" id="editCoverFile" accept="image/*" hidden></label>
                    </div>
                    <div class="image-preview" id="editCoverPreview"></div>
                </div>
                <div class="form-group"><label>正文（支持 HTML）</label><textarea name="content" rows="8" required></textarea></div>
                <div class="form-group">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-text">发布</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">保存</button></div>
        </form>
    </div>
</div>
