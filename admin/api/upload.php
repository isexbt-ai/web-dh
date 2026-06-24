<?php
/**
 * 图片上传 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// 需要登录
if (!isLoggedIn()) {
    jsonError('请先登录');
}

// CSRF验证
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    jsonError('安全验证失败，请刷新页面重试');
}

$type = isset($_GET['type']) ? $_GET['type'] : 'cards';
$allowedTypes = ['ads', 'cards', 'avatar'];

if (!in_array($type, $allowedTypes)) {
    $type = 'cards';
}

if (!isset($_FILES['image']) || empty($_FILES['image']['tmp_name'])) {
    jsonError('请选择图片文件');
}

$result = uploadImage($_FILES['image'], $type);

if ($result['success']) {
    jsonResponse(['path' => $result['path']]);
} else {
    jsonError($result['message']);
}
