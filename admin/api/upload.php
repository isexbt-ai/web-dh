<?php
/**
 * 文件上传 API（支持图片和视频）
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
$allowedTypes = ['ads', 'cards', 'avatar', 'showcase'];

if (!in_array($type, $allowedTypes)) {
    $type = 'cards';
}

// 调试信息
error_log('Upload API called. Type: ' . $type);
error_log('FILES: ' . print_r($_FILES, true));
error_log('POST: ' . print_r($_POST, true));

if (!isset($_FILES['image']) || empty($_FILES['image']['tmp_name'])) {
    $errorMsg = '请选择文件';
    if (isset($_FILES['image']['error'])) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMsg = '文件超过服务器大小限制（PHP限制：' . ini_get('upload_max_filesize') . '）';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = '文件超过表单大小限制';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = '文件上传不完整，请重试';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = '未选择文件';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg = '服务器临时目录不存在';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg = '服务器写入失败';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMsg = '服务器扩展阻止了上传';
                break;
        }
    }
    jsonError($errorMsg);
}

$result = uploadImage($_FILES['image'], $type);

if ($result['success']) {
    jsonResponse(['path' => $result['path'], 'is_video' => $result['is_video'] ?? false]);
} else {
    jsonError($result['message']);
}
