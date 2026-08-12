<?php
/**
 * 后台控制台内容模板（数据：stats 数组）
 */
$stats = $stats ?? [];
$trend = $stats['trend'] ?? [];
$max = 1;
foreach ($trend as $t) {
    $max = max($max, (int) $t['unique_visitors']);
}
?>
<div class="page-header">
    <div>
        <h1>控制台</h1>
        <p>站点数据概览</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff0f2;color:#e94560;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="stat-info"><h3>今日访客</h3><p><?= (int) ($stats['today_visitors'] ?? 0) ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#eef7ff;color:#2d9cdb;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info"><h3>总访客</h3><p><?= (int) ($stats['total_visitors'] ?? 0) ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#27ae60;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        </div>
        <div class="stat-info"><h3>导航卡片</h3><p><?= (int) ($stats['cards'] ?? 0) ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9e7;color:#f39c12;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-info"><h3>文章</h3><p><?= (int) ($stats['articles'] ?? 0) ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e8ff;color:#9b59b6;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="stat-info"><h3>留言（已审核）</h3><p><?= (int) ($stats['messages_active'] ?? 0) ?> / <?= (int) ($stats['messages'] ?? 0) ?></p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff5f0;color:#e67e22;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
        </div>
        <div class="stat-info"><h3>广告 / 公告</h3><p><?= (int) ($stats['ads'] ?? 0) ?> / <?= (int) ($stats['notices'] ?? 0) ?></p></div>
    </div>
</div>

<?php if ($trend !== []): ?>
<div class="chart-section">
    <div class="section-title">最近访问趋势（每日去重访客）</div>
    <div class="chart-bars" id="trendChart">
        <?php foreach ($trend as $t): ?>
        <div class="chart-bar-item" title="<?= e($t['visit_date']) ?>: <?= (int) $t['unique_visitors'] ?>">
            <div class="chart-bar" style="height: <?= max(2, (int) round((int) $t['unique_visitors'] / $max * 100)) ?>%;"></div>
            <span class="chart-bar-label"><?= e(mb_substr((string) $t['visit_date'], 5)) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
