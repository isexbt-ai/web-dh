<?php
/**
 * 留言板 API
 * GET  - 获取留言列表（公开）
 * POST - 提交留言（公开，带防刷限制）
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 获取留言列表
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 10;

    $messages = getMessages($offset, $limit);
    jsonResponse($messages);

} elseif ($method === 'POST') {
    // 检查留言板是否开启
    if (getConfig('guestbook_enabled', '1') !== '1') {
        jsonError('留言板已关闭');
    }

    // 提交留言
    $data = json_decode(file_get_contents('php://input'), true);
    $nickname = isset($data['nickname']) ? substr(trim($data['nickname']), 0, 20) : '';
    $content = isset($data['content']) ? trim($data['content']) : '';

    // 验证
    if (empty($content)) {
        jsonError('留言内容不能为空');
    }
    if (mb_strlen($content) > 500) {
        jsonError('留言内容不能超过500字');
    }

    // IP防刷：同一IP 1分钟内只能提交一次
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages
        WHERE ip = ? AND created_at > datetime('now', '-1 minute')");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() > 0) {
        jsonError('操作太频繁，请稍后再试');
    }

    // 插入留言
    $stmt = $pdo->prepare("INSERT INTO messages (nickname, content, ip, user_agent)
        VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $nickname,
        $content,
        $ip,
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    jsonResponse(['id' => $pdo->lastInsertId()]);
}
