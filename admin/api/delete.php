<?php
/**
 * 通用删除 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonError('请先登录');
}

// 读取POST数据
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

// CSRF验证
$csrf_token = $data['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    jsonError('安全验证失败，请刷新页面重试');
}

$action = isset($data['action']) ? $data['action'] : '';
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    jsonError('无效的ID');
}

try {
    switch ($action) {
        case 'ad':
            // 删除关联图片
            $stmt = $pdo->prepare("SELECT image FROM ads WHERE id = ?");
            $stmt->execute([$id]);
            $ad = $stmt->fetch();
            if ($ad && $ad['image']) {
                deleteImage($ad['image']);
            }
            $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
            $stmt->execute([$id]);
            // 清除缓存
            clearCache();
            jsonResponse(['deleted' => true]);
            break;

        case 'link':
            $stmt = $pdo->prepare("DELETE FROM links WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['deleted' => true]);
            break;

        case 'notice':
            $stmt = $pdo->prepare("DELETE FROM notices WHERE id = ?");
            $stmt->execute([$id]);
            // 清除公告缓存
            clearCache();
            jsonResponse(['deleted' => true]);
            break;

        case 'category':
            // 检查分类下是否有卡片
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                jsonError('该分类下还有卡片，请先删除或转移卡片');
            }
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            // 清除分类缓存
            clearCache();
            jsonResponse(['deleted' => true]);
            break;

        case 'card':
            // 删除关联图片
            $stmt = $pdo->prepare("SELECT image FROM cards WHERE id = ?");
            $stmt->execute([$id]);
            $card = $stmt->fetch();
            if ($card && $card['image']) {
                deleteImage($card['image']);
            }
            $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['deleted' => true]);
            break;

        case 'message':
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['deleted' => true]);
            break;

        case 'showcase':
            // 删除关联图片
            $stmt = $pdo->prepare("SELECT image FROM showcase WHERE id = ?");
            $stmt->execute([$id]);
            $showcase = $stmt->fetch();
            if ($showcase && $showcase['image']) {
                deleteImage($showcase['image']);
            }
            $stmt = $pdo->prepare("DELETE FROM showcase WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['deleted' => true]);
            break;

        case 'gallery':
            // 检查相册下是否有展示内容
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM showcase WHERE gallery_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                jsonError('该相册下还有展示内容，请先删除或转移');
            }
            $stmt = $pdo->prepare("DELETE FROM galleries WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['deleted' => true]);
            break;
    }
} catch (Exception $e) {
    error_log('Delete API Error: ' . $e->getMessage());
    jsonError('删除失败，请稍后重试');
}
