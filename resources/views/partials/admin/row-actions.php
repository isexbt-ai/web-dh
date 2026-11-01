<?php
/**
 * 后台表格「操作」单元格 partial。
 * 数据：$action（删除用的实体名）、$row（当前行数据）、$fields（编辑按钮回填的字段 => 类型）
 *   类型为 int 的字段输出整数，其余按字符串转义。
 */
$action = $action ?? '';
$row = $row ?? [];
$fields = $fields ?? [];
?>
<td class="table-actions">
    <button type="button" class="btn btn-sm btn-edit"
        data-id="<?= (int) $row['id'] ?>"
        <?php foreach ($fields as $f => $type): ?>
        data-<?= e($f) ?>="<?= $type === 'int' ? (int) ($row[$f] ?? 0) : e($row[$f] ?? '') ?>"
        <?php endforeach; ?>
        >编辑</button>
    <button type="button" class="btn btn-sm btn-danger btn-delete" data-action="<?= e($action) ?>" data-id="<?= (int) $row['id'] ?>">删除</button>
</td>
