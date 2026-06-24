<?php
/**
 * 获取卡片列表 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';

$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

try {
    $cards = getCards($categoryId);
    jsonResponse($cards);
} catch (Exception $e) {
    error_log('Cards API Error: ' . $e->getMessage());
    jsonError('获取数据失败，请稍后重试');
}
