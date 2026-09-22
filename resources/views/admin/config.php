<?php
/**
 * 站点配置（数据：values 数组，key => 当前值）
 */
$values = $values ?? [];
$themeChoices = $theme_choices ?? [
    'default' => '浅红品牌（默认）',
    'dark' => '暗夜深邃',
    'corporate' => '商务极简',
    'warm' => '暖橙亲和',
    'mint' => '薄荷清新',
];
/** 主题缩略色（用于后台预览）。 */
$themeSwatches = [
    'default' => ['#e94560', '#f5f7fa', '#ffffff'],
    'dark' => ['#ff5571', '#0f1419', '#1a1f29'],
    'corporate' => ['#0066cc', '#ffffff', '#f5f5f7'],
    'warm' => ['#ff6b35', '#fff8f3', '#ffffff'],
    'mint' => ['#14b8a6', '#f0fdfa', '#ffffff'],
];
$currentTheme = $values['site_theme'] ?? 'default';
?>
<div class="page-header">
    <div><h1>站点配置</h1><p>站点名称/描述/留言板/统计等设置</p></div>
</div>

<div class="table-section" style="max-width: 720px;">
    <form id="configForm" data-action="config" class="form-stack">
        <div class="section-title">基础信息</div>
        <div class="form-group"><label>站点名称</label><input name="site_title" value="<?= e($values['site_title'] ?? '') ?>" maxlength="50"></div>
        <div class="form-group"><label>副标题</label><input name="site_subtitle" value="<?= e($values['site_subtitle'] ?? '') ?>" maxlength="100"></div>
        <div class="form-group"><label>站点描述</label><textarea name="site_description" rows="2" maxlength="300"><?= e($values['site_description'] ?? '') ?></textarea></div>
        <div class="form-group"><label>关键词</label><input name="site_keywords" value="<?= e($values['site_keywords'] ?? '') ?>" maxlength="200"></div>

        <div class="section-title">外观主题</div>
        <p class="form-hint">切换后立即生效（保存时清整页缓存），前台会按所选主题加载对应 CSS。</p>
        <div class="theme-picker">
            <?php foreach ($themeChoices as $key => $label): ?>
            <label class="theme-option<?= $currentTheme === $key ? ' active' : '' ?>">
                <input type="radio" name="site_theme" value="<?= e($key) ?>" <?= $currentTheme === $key ? 'checked' : '' ?>>
                <span class="theme-swatch">
                    <span class="swatch-bg" style="background:<?= e($themeSwatches[$key][1] ?? '#f5f7fa') ?>"></span>
                    <span class="swatch-card" style="background:<?= e($themeSwatches[$key][2] ?? '#fff') ?>"></span>
                    <span class="swatch-accent" style="background:<?= e($themeSwatches[$key][0] ?? '#e94560') ?>"></span>
                </span>
                <span class="theme-label"><?= e($label) ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="form-group"><label>卡片排序方式</label>
            <select name="card_sort_method">
                <option value="default" <?= ($values['card_sort_method'] ?? '') === 'default' ? 'selected' : '' ?>>手动排序</option>
                <option value="click_count" <?= ($values['card_sort_method'] ?? '') === 'click_count' ? 'selected' : '' ?>>按点击量</option>
            </select>
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

        <div class="section-title">统计</div>
        <div class="form-group">
            <label class="toggle-switch">
                <input type="checkbox" name="umami_enabled" <?= ($values['umami_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-text">启用 umami 统计</span>
            </label>
        </div>
        <div class="form-group"><label>umami 脚本地址</label><input name="umami_script_url" value="<?= e($values['umami_script_url'] ?? '') ?>" placeholder="https://umami.example.com/script.js"></div>
        <div class="form-group"><label>umami 站点 ID</label><input name="umami_website_id" value="<?= e($values['umami_website_id'] ?? '') ?>"></div>

        <div class="section-title">前台访客显示（虚拟人气加成）</div>
        <p class="form-hint">展示值 = 真实访客数 + 基数 + 开站天数 × 日增量 + 当天随机抖动；后台仪表盘仍显示真实数。</p>
        <div class="form-row">
            <div class="form-group"><label>人气基数</label><input type="number" min="0" name="visitor_base_offset" value="<?= e($values['visitor_base_offset'] ?? '88888') ?>"></div>
            <div class="form-group"><label>每日净增</label><input type="number" min="0" name="visitor_daily_increment" value="<?= e($values['visitor_daily_increment'] ?? '137') ?>"></div>
        </div>

        <div class="section-title">搜索引擎主动推送（SEO）</div>
        <p class="form-hint">保存卡片/文章/分类时自动推 URL；token/key 留空则跳过对应渠道。IndexNow 同时覆盖 Bing / Yandex。</p>
        <div class="form-group"><label>百度推送 token</label><input name="baidu_push_token" value="<?= e($values['baidu_push_token'] ?? '') ?>" placeholder="从 https://ziyuan.baidu.com 站点管理获取"></div>
        <div class="form-group"><label>IndexNow key</label><input name="indexnow_key" value="<?= e($values['indexnow_key'] ?? '') ?>" placeholder="8-128 位 hex；需把 {key}.txt 放到站点根"></div>

        <div class="form-row">
            <div class="form-group"><label>PC 卡片列数</label><input name="cards_per_row_desktop" value="<?= e($values['cards_per_row_desktop'] ?? 'repeat(6,1fr)') ?>" placeholder="repeat(6,1fr)"></div>
            <div class="form-group"><label>平板列数</label><input name="cards_per_row_tablet" value="<?= e($values['cards_per_row_tablet'] ?? 'repeat(4,1fr)') ?>" placeholder="repeat(4,1fr)"></div>
            <div class="form-group"><label>手机列数</label><input name="cards_per_row_mobile" value="<?= e($values['cards_per_row_mobile'] ?? 'repeat(3,1fr)') ?>" placeholder="repeat(3,1fr)"></div>
        </div>

        <button type="submit" class="btn btn-primary">保存配置</button>
    </form>
</div>
