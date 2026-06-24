<?php
/**
 * 通用保存 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    jsonError('请先登录');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

// CSRF验证
$csrf_token = $data['csrf_token'] ?? $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    jsonError('安全验证失败，请刷新页面重试');
}

try {
    switch ($action) {
        // 保存站点配置
        case 'config':
            $allowedConfigKeys = ['site_title', 'avatar', 'contact_info', 'site_description'];
            foreach ($data as $key => $value) {
                if (!in_array($key, $allowedConfigKeys, true)) {
                    continue; // 跳过不在白名单中的键
                }
                setConfig($key, $value);
            }
            jsonResponse(['saved' => true]);
            break;

        // 保存广告
        case 'ad':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $title = isset($data['title']) ? $data['title'] : '';
            $image = isset($data['image']) ? $data['image'] : '';
            $link = isset($data['link']) ? $data['link'] : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            // 禁止javascript:协议
            if (!empty($link) && preg_match('/^\s*javascript\s*:/i', $link)) {
                jsonError('链接地址不允许使用javascript:协议');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE ads SET title = ?, image = ?, link = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $image, $link, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO ads (title, image, link, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $image, $link, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存链接
        case 'link':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $title = isset($data['title']) ? $data['title'] : '';
            $url = isset($data['url']) ? $data['url'] : '';
            $icon = isset($data['icon']) ? $data['icon'] : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if (empty($title)) {
                jsonError('链接标题不能为空');
            }
            if (empty($url)) {
                jsonError('链接地址不能为空');
            }

            // 禁止javascript:协议
            if (preg_match('/^\s*javascript\s*:/i', $url)) {
                jsonError('链接地址不允许使用javascript:协议');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE links SET title = ?, url = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $url, $icon, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO links (title, url, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $url, $icon, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存公告
        case 'notice':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $title = isset($data['title']) ? $data['title'] : '';
            $content = isset($data['content']) ? $data['content'] : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE notices SET title = ?, content = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $content, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO notices (title, content, sort_order, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存分类
        case 'category':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $name = isset($data['name']) ? $data['name'] : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if (empty($name)) {
                jsonError('分类名称不能为空');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, sort_order, is_active) VALUES (?, ?, ?)");
                $stmt->execute([$name, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存卡片
        case 'card':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
            $title = isset($data['title']) ? $data['title'] : '';
            $image = isset($data['image']) ? $data['image'] : '';
            $link = isset($data['link']) ? $data['link'] : '';
            $detail = isset($data['detail']) ? $data['detail'] : '';
            $card_type = isset($data['card_type']) ? $data['card_type'] : 'link';
            $badge_text = isset($data['badge_text']) ? $data['badge_text'] : '';
            $image_width = isset($data['image_width']) ? intval($data['image_width']) : 0;
            $image_height = isset($data['image_height']) ? intval($data['image_height']) : 0;
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if (empty($title)) {
                jsonError('卡片标题不能为空');
            }

            // 禁止javascript:协议
            if (!empty($link) && preg_match('/^\s*javascript\s*:/i', $link)) {
                jsonError('链接地址不允许使用javascript:协议');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE cards SET category_id = ?, title = ?, image = ?, link = ?, detail = ?, card_type = ?, badge_text = ?, image_width = ?, image_height = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$category_id, $title, $image, $link, $detail, $card_type, $badge_text, $image_width, $image_height, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cards (category_id, title, image, link, detail, card_type, badge_text, image_width, image_height, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $title, $image, $link, $detail, $card_type, $badge_text, $image_width, $image_height, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 更新排序
        case 'sort':
            $table = isset($data['table']) ? $data['table'] : '';
            $items = isset($data['items']) ? $data['items'] : [];

            $allowedTables = ['ads', 'notices', 'categories', 'cards', 'links'];
            if (!in_array($table, $allowedTables)) {
                jsonError('无效的表名');
            }

            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
                $stmt->execute([$item['sort_order'], $item['id']]);
            }
            jsonResponse(['sorted' => true]);
            break;

        // 修改密码
        case 'changePassword':
            $old_password = isset($data['old_password']) ? $data['old_password'] : '';
            $new_password = isset($data['new_password']) ? $data['new_password'] : '';
            $confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';

            // 后端验证
            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                jsonError('所有密码字段都不能为空');
            }
            if (mb_strlen($new_password) < 6) {
                jsonError('新密码长度不能少于6个字符');
            }
            if (mb_strlen($new_password) > 128) {
                jsonError('新密码长度不能超过128个字符');
            }
            if ($new_password !== $confirm_password) {
                jsonError('两次输入的新密码不一致');
            }

            // 验证旧密码
            $username = $_SESSION['admin_username'] ?? '';
            $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($old_password, $user['password'])) {
                jsonError('旧密码不正确');
            }

            // 新密码不能与旧密码相同
            if (password_verify($new_password, $user['password'])) {
                jsonError('新密码不能与旧密码相同');
            }

            // 更新密码
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            $stmt->execute([$new_hash, $username]);

            jsonResponse(['changed' => true]);
            break;

        default:
            jsonError('未知的操作');
    }
} catch (Exception $e) {
    error_log('Save API Error: ' . $e->getMessage());
    jsonError('保存失败，请稍后重试');
}
