<?php
/**
 * 富文本渲染 partial：先转义，再解析 ![alt](url) 图片标记（过滤危险协议），保留换行。
 * 输入：$raw（纯文本/图片标记）
 */
$raw = (string) ($raw ?? '');
if ($raw === '') {
    return;
}
$text = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
$text = preg_replace_callback('/!\[([^\]]*)\]\(([^\)]+)\)/', static function ($m) {
    $alt = $m[1];
    $url = $m[2];
    if (preg_match('/^\s*(javascript|data|vbscript|about|chrome):/i', $url)) {
        return '';
    }
    if (!preg_match('/^(https?:\/\/|\/)/i', $url) && strpos($url, ':') !== false) {
        return '';
    }
    return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="detail-img" loading="lazy">';
}, $text);
echo nl2br($text);
