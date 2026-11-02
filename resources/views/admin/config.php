<?php
/**
 * 站点配置（数据：values 数组，key => 当前值）
 */
$values = $values ?? [];
$themeChoices = $theme_choices ?? [
    'default' => '经典网格（默认）',
    'dark' => '暗夜影院',
    'corporate' => '商务列表',
    'warm' => '暖阳杂志',
    'mint' => '薄荷紧凑',
    'minimal' => '极简线条',
];
/** 主题缩略预览：[主色, 页面底色, 卡片底色, 卡片圆角, 列数, 排列方式]。 */
$themePreview = [
    'default'  => ['#e94560', '#f5f7fa', '#ffffff', 4, 3, 'grid'],
    'dark'     => ['#ff5571', '#0d1117', '#161b22', 5, 5, 'grid'],
    'corporate'=> ['#2563eb', '#f8fafc', '#ffffff', 0, 1, 'list'],
    'warm'     => ['#f97316', '#fdf6f0', '#ffffff', 6, 4, 'grid'],
    'mint'     => ['#0d9488', '#f6fefd', '#ffffff', 3, 6, 'grid'],
    'minimal'  => ['#111111', '#ffffff', '#ffffff', 0, 3, 'grid'],
];
$currentTheme = $values['site_theme'] ?? 'default';
?>
<div class="page-header">
    <div><h1>站点配置</h1><p>站点名称/描述/留言板/主题等设置</p></div>
</div>

<div class="table-section" style="max-width: 720px;">
    <form id="configForm" data-action="config" class="form-stack">
        <div class="section-title">基础信息</div>
        <div class="form-group"><label>站点名称</label><input name="site_title" value="<?= e($values['site_title'] ?? '') ?>" maxlength="50"></div>
        <div class="form-group"><label>副标题</label><input name="site_subtitle" value="<?= e($values['site_subtitle'] ?? '') ?>" maxlength="100"></div>
        <div class="form-group"><label>站点描述</label><textarea name="site_description" rows="2" maxlength="300"><?= e($values['site_description'] ?? '') ?></textarea></div>
        <div class="form-group"><label>关键词</label><input name="site_keywords" value="<?= e($values['site_keywords'] ?? '') ?>" maxlength="200"></div>

        <div class="section-title">外观主题</div>
        <p class="form-hint">每套主题包含<strong>配色 + 排版</strong>（列数、卡片方向、圆角、标题样式都不同）。保存后立即生效。</p>
        <div class="theme-picker">
            <?php foreach ($themeChoices as $key => $label): ?>
            <?php
            $pv = $themePreview[$key] ?? ['#e94560', '#f5f7fa', '#ffffff', 4, 3, 'grid'];
            [$accent, $pageBg, $cardBg, $radius, $cols, $mode] = $pv;
            ?>
            <label class="theme-option<?= $currentTheme === $key ? ' active' : '' ?>">
                <input type="radio" name="site_theme" value="<?= e($key) ?>" <?= $currentTheme === $key ? 'checked' : '' ?>>
                <span class="theme-swatch" style="background:<?= e($pageBg) ?>">
                    <?php if ($mode === 'list'): ?>
                        <span class="pv-list">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <span class="pv-row">
                                <span class="pv-bar" style="background:<?= e($accent) ?>"></span>
                                <span class="pv-line"></span>
                            </span>
                            <?php endfor; ?>
                        </span>
                    <?php else: ?>
                        <span class="pv-grid" style="grid-template-columns:repeat(<?= (int) $cols ?>,1fr)">
                            <?php
                            $cells = min(15, $cols * 3);
                            for ($i = 0; $i < $cells; $i++):
                                $isAccent = ($i % 7 === 0);
                            ?>
                            <span class="pv-cell<?= $isAccent ? ' accent' : '' ?>"
                                  style="background:<?= $isAccent ? e($accent) : e($cardBg) ?>;border-radius:<?= (int) $radius ?>px"></span>
                            <?php endfor; ?>
                        </span>
                    <?php endif; ?>
                </span>
                <span class="theme-label"><?= e($label) ?></span>
                <span class="theme-meta"><?= $mode === 'list' ? '单列横排' : $cols . ' 列网格' ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="section-title">页面背景</div>
        <p class="form-hint">整页背景图（铺满全屏）。建议使用 1920px 以内的图片，遮罩浓度越大文字越清晰。</p>
        <div class="form-group">
            <label for="siteBgImage">背景图片</label>
            <div class="image-upload-row">
                <input name="site_bg_image" id="siteBgImage" placeholder="/uploads/background/xxx.jpg 或 http(s)://...">
                <label class="btn btn-sm btn-secondary" for="siteBgImageFile">上传</label>
                <input type="file" id="siteBgImageFile" accept="image/*" hidden>
                <button type="button" class="btn btn-sm btn-secondary" id="siteBgImageClear">清除</button>
            </div>
            <div class="image-preview" id="siteBgImagePreview"></div>
        </div>
        <div class="form-group"><label for="siteBgOverlay">背景遮罩浓度（0-100）</label>
            <input type="number" id="siteBgOverlay" name="site_bg_overlay" min="0" max="100" value="<?= e($values['site_bg_overlay'] ?? '35') ?>">
        </div>

        <div class="section-title">留言板</div>
        <div class="form-group">
            <label class="toggle-switch">
                <input type="checkbox" name="guestbook_enabled" <?= ($values['guestbook_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-text">开启留言板</span>
            </label>
        </div>
        <div class="form-group"><label>留言板标题</label><input name="guestbook_title" value="<?= e($values['guestbook_title'] ?? '联系我们') ?>" maxlength="50"></div>
        <div class="form-group"><label>留言板副标题</label><input name="guestbook_subtitle" value="<?= e($values['guestbook_subtitle'] ?? '') ?>" maxlength="100"></div>
        <div class="form-group"><label>留言板图片</label><input name="guestbook_image" value="<?= e($values['guestbook_image'] ?? '') ?>" placeholder="/uploads/xxx.jpg"></div>
        <div class="form-group"><label>留言提示语</label><textarea name="guestbook_notice" rows="2" maxlength="300"><?= e($values['guestbook_notice'] ?? '') ?></textarea></div>

        <div class="form-row">
            <div class="form-group"><label>PC 卡片列数</label><input name="cards_per_row_desktop" value="<?= e($values['cards_per_row_desktop'] ?? 'repeat(6,1fr)') ?>" placeholder="repeat(6,1fr)"></div>
            <div class="form-group"><label>平板列数</label><input name="cards_per_row_tablet" value="<?= e($values['cards_per_row_tablet'] ?? 'repeat(4,1fr)') ?>" placeholder="repeat(4,1fr)"></div>
            <div class="form-group"><label>手机列数</label><input name="cards_per_row_mobile" value="<?= e($values['cards_per_row_mobile'] ?? 'repeat(3,1fr)') ?>" placeholder="repeat(3,1fr)"></div>
        </div>

        <button type="submit" class="btn btn-primary">保存配置</button>
    </form>
</div>
