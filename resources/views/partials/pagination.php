<?php
/**
 * 分页 partial。
 * 数据：page/pages/base（列表页链接前缀）
 */
$page = (int) ($page ?? 1);
$pages = (int) ($pages ?? 1);
$base = (string) ($base ?? '/');
if ($pages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="分页">
    <?php if ($page > 1): ?>
    <a class="pagination-btn" href="<?= e($base) ?>?page=<?= $page - 1 ?>">上一页</a>
    <?php endif; ?>
    <span class="pagination-info"><?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?>
    <a class="pagination-btn" href="<?= e($base) ?>?page=<?= $page + 1 ?>">下一页</a>
    <?php endif; ?>
</nav>
