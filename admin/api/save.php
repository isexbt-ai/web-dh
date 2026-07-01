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

// 定义字段长度限制
const MAX_TITLE_LENGTH = 255;
const MAX_CONTENT_LENGTH = 65535;
const MAX_URL_LENGTH = 2048;
const MAX_DETAIL_LENGTH = 65535;

try {
    switch ($action) {
        // 保存站点配置
        case 'config':
            $allowedConfigKeys = ['site_title', 'avatar', 'contact_info', 'site_description', 'cards_per_row_desktop', 'cards_per_row_tablet', 'cards_per_row_mobile', 'card_sort_method', 'guestbook_enabled', 'guestbook_title', 'guestbook_subtitle', 'guestbook_image'];
            foreach ($data as $key => $value) {
                if (!in_array($key, $allowedConfigKeys, true)) {
                    continue; // 跳过不在白名单中的键
                }
                setConfig($key, $value);
            }
            // 清除数据缓存
            clearCache();
            jsonResponse(['saved' => true]);
            break;

        // 保存广告
        case 'ad':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $title = isset($data['title']) ? substr(trim($data['title']), 0, MAX_TITLE_LENGTH) : '';
            $image = isset($data['image']) ? substr($data['image'], 0, MAX_URL_LENGTH) : '';
            $link = isset($data['link']) ? substr(trim($data['link']), 0, MAX_URL_LENGTH) : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if (empty($title)) {
                jsonError('广告标题不能为空');
            }

            // 禁止危险协议
            if (!empty($link) && preg_match('/^\s*(javascript|data|vbscript):/i', $link)) {
                jsonError('链接地址包含不允许的协议');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE ads SET title = ?, image = ?, link = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $image, $link, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO ads (title, image, link, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $image, $link, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            // 清除公告缓存
            clearCache();
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存链接
        case 'link':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $title = isset($data['title']) ? substr(trim($data['title']), 0, MAX_TITLE_LENGTH) : '';
            $url = isset($data['url']) ? substr(trim($data['url']), 0, MAX_URL_LENGTH) : '';
            $icon = isset($data['icon']) ? substr($data['icon'], 0, MAX_URL_LENGTH) : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if (empty($title)) {
                jsonError('链接标题不能为空');
            }
            if (empty($url)) {
                jsonError('链接地址不能为空');
            }

            // 禁止危险协议
            if (preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
                jsonError('链接地址包含不允许的协议');
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
            $title = isset($data['title']) ? substr(trim($data['title']), 0, MAX_TITLE_LENGTH) : '';
            $content = isset($data['content']) ? substr($data['content'], 0, MAX_CONTENT_LENGTH) : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if (empty($title)) {
                jsonError('公告标题不能为空');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE notices SET title = ?, content = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$title, $content, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO notices (title, content, sort_order, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            // 清除公告缓存
            clearCache();
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存分类
        case 'category':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $name = isset($data['name']) ? substr(trim($data['name']), 0, MAX_TITLE_LENGTH) : '';
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
            // 清除分类缓存
            clearCache();
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存卡片
        case 'card':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $category_id = isset($data['category_id']) ? intval($data['category_id']) : 0;
            $title = isset($data['title']) ? substr(trim($data['title']), 0, MAX_TITLE_LENGTH) : '';
            $image = isset($data['image']) ? substr($data['image'], 0, MAX_URL_LENGTH) : '';
            $link = isset($data['link']) ? substr(trim($data['link']), 0, MAX_URL_LENGTH) : '';
            $detail = isset($data['detail']) ? substr($data['detail'], 0, MAX_DETAIL_LENGTH) : '';
            $card_type = isset($data['card_type']) ? $data['card_type'] : 'link';
            $badge_text = isset($data['badge_text']) ? substr($data['badge_text'], 0, 50) : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            // card_type 白名单验证
            $allowedCardTypes = ['link', 'detail', 'image', 'video', 'text'];
            if (!in_array($card_type, $allowedCardTypes, true)) {
                $card_type = 'link'; // 默认回退到 link
            }

            if (empty($title)) {
                jsonError('卡片标题不能为空');
            }

            // 禁止危险协议
            if (!empty($link) && preg_match('/^\s*(javascript|data|vbscript):/i', $link)) {
                jsonError('链接地址包含不允许的协议');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE cards SET category_id = ?, title = ?, image = ?, link = ?, detail = ?, card_type = ?, badge_text = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$category_id, $title, $image, $link, $detail, $card_type, $badge_text, $sort_order, $is_active, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cards (category_id, title, image, link, detail, card_type, badge_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $title, $image, $link, $detail, $card_type, $badge_text, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 更新排序
        case 'sort':
            $table = isset($data['table']) ? $data['table'] : '';
            $items = isset($data['items']) ? $data['items'] : [];

            $allowedTables = ['ads', 'notices', 'categories', 'cards', 'links'];
            if (!in_array($table, $allowedTables, true)) {
                jsonError('无效的表名');
            }

            // 验证 items 数组结构
            if (!is_array($items) || empty($items)) {
                jsonError('排序数据不能为空');
            }

            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['id']) || !isset($item['sort_order'])) {
                    jsonError('排序数据格式错误');
                }
                $sortId = intval($item['id']);
                $sortOrder = intval($item['sort_order']);
                if ($sortId <= 0) {
                    jsonError('无效的排序ID');
                }
                $stmt = $pdo->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
                $stmt->execute([$sortOrder, $sortId]);
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

        // 管理留言（删除/恢复）
        case 'message':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if ($id <= 0) {
                jsonError('无效的留言ID');
            }

            $stmt = $pdo->prepare("UPDATE messages SET is_active = ? WHERE id = ?");
            $stmt->execute([$is_active, $id]);
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 回复留言
        case 'messageReply':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $reply = isset($data['reply']) ? trim($data['reply']) : '';

            if ($id <= 0) {
                jsonError('无效的留言ID');
            }

            $stmt = $pdo->prepare("UPDATE messages SET reply = ?, replied_at = CASE WHEN ? = '' THEN NULL ELSE CURRENT_TIMESTAMP END WHERE id = ?");
            $stmt->execute([$reply, $reply, $id]);
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存跳转页配置
        case 'redirect_config':
            $mainUrl = isset($data['redirect_main_url']) ? trim($data['redirect_main_url']) : '';
            $mainName = isset($data['redirect_main_name']) ? trim($data['redirect_main_name']) : '';
            $backupUrls = isset($data['backup_urls']) ? $data['backup_urls'] : [];
            $backupNames = isset($data['backup_names']) ? $data['backup_names'] : [];
            $countdown = isset($data['redirect_countdown']) ? intval($data['redirect_countdown']) : 3;
            $checkTimeout = isset($data['redirect_check_timeout']) ? intval($data['redirect_check_timeout']) : 3000;
            $fallbackFirst = isset($data['redirect_fallback_first']) ? '1' : '0';

            if (empty($mainUrl) || empty($mainName)) {
                jsonError('主站地址和名称不能为空');
            }

            // 验证URL格式
            if (!preg_match('/^https:\/\//i', $mainUrl)) {
                jsonError('主站地址必须以 https:// 开头');
            }

            // 构建备用地址JSON
            $backups = [];
            if (is_array($backupUrls) && is_array($backupNames)) {
                foreach ($backupUrls as $index => $url) {
                    $url = trim($url);
                    $name = isset($backupNames[$index]) ? trim($backupNames[$index]) : '';
                    if (!empty($url)) {
                        $backups[] = [
                            'url' => $url,
                            'name' => $name ?: '备用' . ($index + 1),
                            'priority' => $index + 2
                        ];
                    }
                }
            }

            setConfig('redirect_main_url', $mainUrl);
            setConfig('redirect_main_name', $mainName);
            setConfig('redirect_backup_urls', json_encode($backups));
            setConfig('redirect_countdown', strval(max(1, min(10, $countdown))));
            setConfig('redirect_check_timeout', strval(max(1000, min(10000, $checkTimeout))));
            setConfig('redirect_fallback_first', $fallbackFirst);

            jsonResponse(['saved' => true]);
            break;
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $title = isset($data['title']) ? substr(trim($data['title']), 0, MAX_TITLE_LENGTH) : '';
            $description = isset($data['description']) ? substr(trim($data['description']), 0, MAX_CONTENT_LENGTH) : '';
            $cover_image = isset($data['cover_image']) ? substr($data['cover_image'], 0, MAX_URL_LENGTH) : '';
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if ($id > 0) {
                // 更新现有记录 - 只更新提供的字段（支持toggleStatus部分更新）
                $updateFields = [];
                $params = [];
                if (isset($data['title'])) {
                    if (empty($title)) {
                        jsonError('相册标题不能为空');
                    }
                    $updateFields[] = "title = ?";
                    $params[] = $title;
                }
                if (isset($data['description'])) {
                    $updateFields[] = "description = ?";
                    $params[] = $description;
                }
                if (isset($data['cover_image'])) {
                    $updateFields[] = "cover_image = ?";
                    $params[] = $cover_image;
                }
                if (isset($data['sort_order'])) {
                    $updateFields[] = "sort_order = ?";
                    $params[] = $sort_order;
                }
                if (isset($data['is_active'])) {
                    $updateFields[] = "is_active = ?";
                    $params[] = $is_active;
                }
                if (!empty($updateFields)) {
                    $params[] = $id;
                    $stmt = $pdo->prepare("UPDATE galleries SET " . implode(', ', $updateFields) . " WHERE id = ?");
                    $stmt->execute($params);
                }
            } else {
                if (empty($title)) {
                    jsonError('相册标题不能为空');
                }
                $stmt = $pdo->prepare("INSERT INTO galleries (title, description, cover_image, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $cover_image, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存效果展示
        case 'showcase':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $title = isset($data['title']) ? substr(trim($data['title']), 0, MAX_TITLE_LENGTH) : '';
            $image = isset($data['image']) ? substr($data['image'], 0, MAX_URL_LENGTH) : '';
            $media_type = isset($data['media_type']) ? $data['media_type'] : 'image';
            $gallery_id = isset($data['gallery_id']) ? intval($data['gallery_id']) : 1;
            $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
            $is_active = isset($data['is_active']) ? intval($data['is_active']) : 1;

            if ($id > 0) {
                // 更新现有记录 - 只更新提供的字段（支持toggleStatus部分更新）
                $updateFields = [];
                $params = [];
                if (isset($data['title'])) {
                    if (empty($title)) {
                        jsonError('展示标题不能为空');
                    }
                    $updateFields[] = "title = ?";
                    $params[] = $title;
                }
                if (isset($data['image'])) {
                    $updateFields[] = "image = ?";
                    $params[] = $image;
                }
                if (isset($data['media_type'])) {
                    $updateFields[] = "media_type = ?";
                    $params[] = $media_type;
                }
                if (isset($data['gallery_id'])) {
                    $updateFields[] = "gallery_id = ?";
                    $params[] = $gallery_id;
                }
                if (isset($data['sort_order'])) {
                    $updateFields[] = "sort_order = ?";
                    $params[] = $sort_order;
                }
                if (isset($data['is_active'])) {
                    $updateFields[] = "is_active = ?";
                    $params[] = $is_active;
                }
                if (!empty($updateFields)) {
                    $params[] = $id;
                    $stmt = $pdo->prepare("UPDATE showcase SET " . implode(', ', $updateFields) . " WHERE id = ?");
                    $stmt->execute($params);
                }
            } else {
                if (empty($title)) {
                    jsonError('展示标题不能为空');
                }
                $stmt = $pdo->prepare("INSERT INTO showcase (title, image, media_type, gallery_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $image, $media_type, $gallery_id, $sort_order, $is_active]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 保存跳转页配置
        case 'redirect_config':
            $mainDomain = isset($data['redirect_main_domain']) ? trim($data['redirect_main_domain']) : '';
            $subdomainLength = isset($data['redirect_subdomain_length']) ? intval($data['redirect_subdomain_length']) : 6;
            $countdown = isset($data['redirect_countdown']) ? intval($data['redirect_countdown']) : 3;
            $checkTimeout = isset($data['redirect_check_timeout']) ? intval($data['redirect_check_timeout']) : 3000;
            $fallbackFirst = isset($data['redirect_fallback_first']) ? '1' : '0';

            // 验证主域名格式
            if (!empty($mainDomain)) {
                $mainDomain = preg_replace('/^https?:\/\//', '', $mainDomain);
                $mainDomain = rtrim($mainDomain, '/');
            }

            setConfig('redirect_main_domain', $mainDomain);
            setConfig('redirect_subdomain_length', strval(max(4, min(12, $subdomainLength))));
            setConfig('redirect_countdown', strval(max(1, min(10, $countdown))));
            setConfig('redirect_check_timeout', strval(max(1000, min(10000, $checkTimeout))));
            setConfig('redirect_fallback_first', $fallbackFirst);

            jsonResponse(['saved' => true]);
            break;
            $domain = isset($data['domain']) ? trim($data['domain']) : '';
            $name = isset($data['name']) ? trim($data['name']) : '';

            if (empty($domain)) {
                jsonError('域名不能为空');
            }

            // 验证URL格式
            if (!preg_match('/^https:\/\//i', $domain)) {
                jsonError('域名必须以 https:// 开头');
            }

            // 确保域名以 / 结尾
            if (!str_ends_with($domain, '/')) {
                $domain .= '/';
            }

            $stmt = $pdo->prepare("INSERT INTO redirect_domains (domain, name, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$domain, $name, 0]);
            $id = $pdo->lastInsertId();

            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 封禁/解封域名
        case 'redirect_domain_block':
            $id = isset($data['id']) ? intval($data['id']) : 0;
            $isBlocked = isset($data['is_blocked']) ? intval($data['is_blocked']) : 0;

            if ($id <= 0) {
                jsonError('无效的域名ID');
            }

            $blockedAt = $isBlocked ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("UPDATE redirect_domains SET is_blocked = ?, blocked_at = ? WHERE id = ?");
            $stmt->execute([$isBlocked, $blockedAt, $id]);

            jsonResponse(['id' => $id, 'saved' => true]);
            break;

        // 删除域名
        case 'redirect_domain_delete':
            $id = isset($data['id']) ? intval($data['id']) : 0;

            if ($id <= 0) {
                jsonError('无效的域名ID');
            }

            $stmt = $pdo->prepare("DELETE FROM redirect_domains WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse(['id' => $id, 'deleted' => true]);
            break;
    }
} catch (Exception $e) {
    error_log('Save API Error: ' . $e->getMessage());
    jsonError('保存失败，请稍后重试');
}
