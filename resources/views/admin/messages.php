<?php
/**
 * 留言管理（数据：items）
 */
$items = $items ?? [];
?>
<div class="page-header">
    <div><h1>留言管理</h1><p>审核与回复访客留言</p></div>
</div>

<div class="table-section">
    <table class="data-table">
        <thead><tr><th>ID</th><th>昵称</th><th>内容</th><th>IP</th><th>时间</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
            <?php if ($items === []): ?>
            <tr><td colspan="7" class="empty-state">暂无留言</td></tr>
            <?php else: foreach ($items as $it): ?>
            <tr>
                <td><?= (int) $it['id'] ?></td>
                <td><?= e($it['nickname'] ?: '匿名') ?></td>
                <td class="col-ellipsis" title="<?= e($it['content']) ?>">
                    <?= e(mb_strimwidth((string) $it['content'], 0, 50, '…')) ?>
                    <?php if (!empty($it['reply'])): ?><div class="form-hint">回复：<?= e(mb_strimwidth((string) $it['reply'], 0, 30, '…')) ?></div><?php endif; ?>
                </td>
                <td><?= e($it['ip']) ?></td>
                <td><?= e(date('Y-m-d H:i', strtotime((string) $it['created_at']))) ?></td>
                <td><?= (int) $it['is_active'] === 1 ? '<span class="toggle-text on">已审核</span>' : '<span class="toggle-text">待审核</span>' ?></td>
                <td class="table-actions">
                    <button type="button" class="btn btn-sm btn-edit btn-reply"
                        data-id="<?= (int) $it['id'] ?>"
                        data-nickname="<?= e($it['nickname']) ?>"
                        data-content="<?= e($it['content']) ?>"
                        data-reply="<?= e($it['reply']) ?>">回复</button>
                    <button type="button" class="btn btn-sm btn-secondary btn-toggle"
                        data-action="message" data-id="<?= (int) $it['id'] ?>" data-is_active="<?= (int) $it['is_active'] ?>"><?= (int) $it['is_active'] === 1 ? '取消审核' : '通过' ?></button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="message" data-id="<?= (int) $it['id'] ?>">删除</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="replyModal">
    <div class="modal-content">
        <div class="modal-header"><h3>回复留言</h3><button type="button" class="modal-close">&times;</button></div>
        <form id="replyForm" data-action="message" data-op="reply">
            <div class="modal-body">
                <input type="hidden" name="id">
                <div class="form-group"><label>留言</label><div id="replyContent" class="form-hint"></div></div>
                <div class="form-group"><label>回复内容</label><textarea name="reply" rows="4" maxlength="500"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">保存回复</button></div>
        </form>
    </div>
</div>
