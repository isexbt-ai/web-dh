<?php
/**
 * 记录卡片点击 API
 * 添加 IP 速率限制防止刷点击量
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';

$cardId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cardId <= 0) {
    jsonError('无效的卡片ID');
}

// 启动 session 以使用基于 session 的速率限制
session_start();

// IP 速率限制：同一 IP 每 5 秒只能点击一次
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitKey = 'click_limit_' . md5($ip . '_' . $cardId);
$lastClick = $_SESSION[$rateLimitKey] ?? 0;
$currentTime = time();

if (($currentTime - $lastClick) < 5) {
    jsonError('点击过于频繁，请稍后再试');
}

// 记录本次点击时间
$_SESSION[$rateLimitKey] = $currentTime;

// 清理过期的速率限制记录（可选，保持 session 整洁）
if (rand(1, 100) === 1) { // 1% 概率执行清理
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'click_limit_') === 0 && ($currentTime - $value) > 3600) {
            unset($_SESSION[$key]);
        }
    }
}

incrementCardClick($cardId);
jsonResponse(['clicked' => true]);
