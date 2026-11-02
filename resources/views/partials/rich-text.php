<?php
/**
 * 富文本渲染 partial：先转义，再解析 ![alt](url) 图片标记（过滤危险协议），保留换行。
 * 输入：$raw（纯文本/图片标记）
 */
$raw = (string) ($raw ?? '');
if ($raw === '') {
    return;
}

/** 图片 src 是否安全：拒绝 javascript:/data:/vbscript: 等伪协议与含协议的相对地址。 */
$safeImageUrl = static function (string $url): bool {
    if (preg_match('/^\s*(javascript|data|vbscript|about|chrome):/i', $url)) {
        return false;
    }
    // 形如 "foo:bar" 的相对地址含协议分隔符 → 拒绝；纯相对路径（含 / 开头）放行
    return (bool) preg_match('#^(https?://|/)#i', $url) || strpos($url, ':') === false;
};

$text = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');

// 1) ![alt](url) 标记 → 带懒加载的 <img>
$text = preg_replace_callback('/!\[([^\]]*)\]\(([^\)]+)\)/', static function ($m) use ($safeImageUrl) {
    $alt = $m[1];
    $url = $m[2];
    if (!$safeImageUrl($url)) {
        return '';
    }
    return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="detail-img" loading="lazy">';
}, $text);

echo nl2br($text);
