<?php
/**
 * IP查询API - 后端代理调用ipinfo.io
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonError('请先登录');
}

$ip = isset($_GET['ip']) ? trim($_GET['ip']) : '';
if (empty($ip)) {
    jsonError('请输入IP地址');
}

// 验证IP格式
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    jsonError('IP地址格式不正确');
}

$location = getIpLocation($ip);
if (!$location) {
    jsonError('查询失败，请稍后重试');
}

jsonResponse($location);
