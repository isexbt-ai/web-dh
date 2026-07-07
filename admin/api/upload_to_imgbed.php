<?php
/**
 * 批量上传图片到图床 API
 * 手动触发，无需定时任务
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonError('请先登录');
}

// 获取请求参数
$input = json_decode(file_get_contents('php://input'), true);
$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];
$cdnDomain = isset($input['cdn_domain']) && is_string($input['cdn_domain']) ? trim($input['cdn_domain']) : null;

// CSRF验证
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    jsonError('安全验证失败，请刷新页面重试');
}

// 执行批量上传（支持自定义 CDN 域名）
$result = batchUploadToImgbed($ids, $cdnDomain);

// 返回结果
jsonResponse([
    'success' => $result['success'],
    'failed' => $result['failed'],
    'details' => $result['details']
]);
