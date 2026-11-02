<?php
/**
 * 文章详情页内容模板。
 * 数据：article
 */
$article = $article ?? [];
?>
<div class="article-detail-container">
    <div class="article-detail-card">
        <div class="article-detail-breadcrumb">
            <a href="/">首页</a>
            <span>/</span>
            <a href="/articles">文章资讯</a>
        </div>

        <h1 class="article-detail-title"><?= e($article['title']) ?></h1>

        <div class="article-detail-meta">
            <div class="article-detail-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span><?= e(date('Y-m-d', strtotime((string) $article['created_at']))) ?></span>
            </div>
            <?php if (!empty($article['updated_at']) && $article['updated_at'] !== $article['created_at']): ?>
            <div class="article-detail-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                <span>更新于 <?= e(date('Y-m-d', strtotime((string) $article['updated_at']))) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($article['cover_image'])): ?>
        <img src="<?= e(image_url((string) $article['cover_image'])) ?>" alt="<?= e($article['title']) ?>" class="article-detail-cover" loading="lazy">
        <?php endif; ?>

        <div class="article-detail-body">
            <?php
            /**
             * 正文为原始 HTML（后台可直接编辑标签），此处仅做两件事：
             * 1. 给 <img> 补 loading="lazy"（正文图多且非首屏，避免阻塞详情页加载）
             * 2. 移除指向 javascript:/data: 等伪协议的图片 src
             * 不改动其它 HTML 结构。
             */
            $bodyHtml = (string) ($article['content'] ?? '');
            if ($bodyHtml !== '') {
                $bodyHtml = preg_replace_callback('/<img\s+([^>]*?)>/i', static function (array $m): string {
                    $attrs = $m[1];
                    if (!preg_match('/src="([^"]*)"/i', $attrs, $sm)) {
                        return $m[0];
                    }
                    $src = html_entity_decode($sm[1], ENT_QUOTES, 'UTF-8');
                    if (preg_match('/^\s*(javascript|data|vbscript|about|chrome):/i', $src)) {
                        return '';
                    }
                    if (preg_match('/\bloading\s*=/i', $attrs)) {
                        return $m[0];
                    }
                    return '<img ' . rtrim($attrs) . ' loading="lazy">';
                }, $bodyHtml) ?? $bodyHtml;
            }
            echo $bodyHtml;
            ?>
        </div>

        <?php if (!empty($article['keywords'])): ?>
        <div class="article-keywords">
            <div class="article-keywords-label">关键词：</div>
            <?php foreach (array_map('trim', explode(',', (string) $article['keywords'])) as $keyword): ?>
                <?php if ($keyword !== ''): ?>
                <span class="article-keyword-tag"><?= e($keyword) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="article-detail-actions">
            <a href="/articles" class="article-detail-btn article-detail-btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                返回文章列表
            </a>
            <a href="/" class="article-detail-btn article-detail-btn-secondary">返回首页</a>
        </div>
    </div>
</div>
