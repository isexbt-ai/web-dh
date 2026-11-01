<?php
/**
 * 后台表格「启用状态」单元格 partial。
 * 数据：$row（当前行数据，含 is_active）
 */
$row = $row ?? [];
?>
<td><?= (int) ($row['is_active'] ?? 0) === 1 ? '<span class="toggle-text on">启用</span>' : '<span class="toggle-text">停用</span>' ?></td>
