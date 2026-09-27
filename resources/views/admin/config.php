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
