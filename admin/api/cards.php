<?php
/**
 * 获取卡片列表 API
 * 公开接口：前台切换分类时使用，无需认证
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../../includes/functions.php';

$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$cardSortMethod = getConfig('card_sort_method', 'default');

try {
    $cards = getCards($categoryId, true, $cardSortMethod);

    // 为每张卡片添加缩略图URL
    foreach ($cards as &$card) {
        if (!empty($card['image'])) {
            $card['thumb_image'] = getThumbnailUrl($card['image']);
        } else {
            $card['thumb_image'] = '';
        }
    }

    jsonResponse($cards);
} catch (Exception $e) {
    error_log('Cards API Error: ' . $e->getMessage());
    jsonError('获取数据失败，请稍后重试');
}
