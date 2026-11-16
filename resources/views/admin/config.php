<?php
/**
 * 站点配置（数据：values 数组，key => 当前值）
 */
$values = $values ?? [];
?>
<div class="page-header">
    <div><h1>站点配置</h1><p>站点名称/描述/留言板等设置</p></div>
</div>

<div class="table-section" style="max-width: 720px;">
    <form id="configForm" data-action="config" class="form-stack">
        <div class="section-title">基础信息</div>
        <div class="form-group"><label>站点名称</label><input name="site_title" value="<?= e($values['site_title'] ?? '') ?>" maxlength="50"></div>
        <div class="form-group"><label>副标题</label><input name="site_subtitle" value="<?= e($values['site_subtitle'] ?? '') ?>" maxlength="100"></div>
        <div class="form-group"><label>站点描述</label><textarea name="site_description" rows="2" maxlength="300"><?= e($values['site_description'] ?? '') ?></textarea></div>
        <div class="form-group"><label>关键词</label><input name="site_keywords" value="<?= e($values['site_keywords'] ?? '') ?>" maxlength="200"></div>

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

        <button type="submit" class="btn btn-primary">保存配置</button>
    </form>
</div>
