<?php
/**
 * 记录卡片点击 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';

$cardId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cardId > 0) {
    incrementCardClick($cardId);
    jsonResponse(['clicked' => true]);
} else {
    jsonError('无效的卡片ID');
}
