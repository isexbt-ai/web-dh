<?php
/**
 * 公共函数文件
 */

require_once __DIR__ . '/db.php';

/**
 * 安全输出 - 防止XSS攻击
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 获取站点配置
 */
function getConfig($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM site_config WHERE key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : $default;
}

/**
 * 设置站点配置
 */
function setConfig($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO site_config (key, value) VALUES (?, ?)
                          ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP");
    return $stmt->execute([$key, $value]);
}

/**
 * 获取所有广告
 */
function getAds($activeOnly = true) {
    global $pdo;
    $sql = "SELECT * FROM ads";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id DESC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * 获取所有公告
 */
function getNotices($activeOnly = true) {
    global $pdo;
    $sql = "SELECT * FROM notices";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id DESC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * 获取所有分类
 */
function getCategories($activeOnly = true) {
    global $pdo;
    $sql = "SELECT * FROM categories";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * 获取分类下的卡片
 */
function getCards($categoryId = null, $activeOnly = true) {
    global $pdo;
    $sql = "SELECT c.*, cat.name as category_name FROM cards c
            LEFT JOIN categories cat ON c.category_id = cat.id";
    $where = [];
    $params = [];

    if ($categoryId !== null) {
        $where[] = "c.category_id = ?";
        $params[] = $categoryId;
    }
    if ($activeOnly) {
        $where[] = "c.is_active = 1";
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY c.sort_order ASC, c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 记录访问统计
 */
function recordVisit($page = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO visit_stats (page, ip, user_agent, visit_date) VALUES (?, ?, ?, date('now'))");
    $stmt->execute([
        $page,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * 获取今日访问量
 */
function getTodayVisits() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visit_stats WHERE visit_date = date('now')");
    return $stmt->fetch()['count'];
}

/**
 * 获取总访问量
 */
function getTotalVisits() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visit_stats");
    return $stmt->fetch()['count'];
}

/**
 * 获取本周访问量数据（用于趋势图）
 */
function getWeekVisits() {
    global $pdo;
    $stmt = $pdo->query("SELECT
        visit_date as date,
        COUNT(*) as count
        FROM visit_stats
        WHERE visit_date >= date('now', '-6 days')
        GROUP BY visit_date
        ORDER BY visit_date ASC");
    return $stmt->fetchAll();
}

/**
 * 获取所有链接
 */
function getLinks($activeOnly = true) {
    global $pdo;
    $sql = "SELECT * FROM links";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id DESC";
    return $pdo->query($sql)->fetchAll();
}
function getHotCards($limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE is_active = 1 ORDER BY click_count DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * 增加卡片点击次数
 */
function incrementCardClick($cardId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE cards SET click_count = click_count + 1 WHERE id = ?");
    return $stmt->execute([$cardId]);
}

/**
 * 返回JSON响应
 */
function jsonResponse($data, $success = true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'data' => $data]);
    exit;
}

/**
 * 返回错误JSON响应
 */
function jsonError($message) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * 上传图片
 */
function uploadImage($file, $directory = 'cards') {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // 检查上传错误
    if (isset($file['error'])) {
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                return ['success' => false, 'message' => '文件超过服务器大小限制（最大10MB）'];
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'message' => '文件超过表单大小限制'];
            case UPLOAD_ERR_PARTIAL:
                return ['success' => false, 'message' => '文件上传不完整，请重试'];
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'message' => '请选择图片文件'];
            case UPLOAD_ERR_NO_TMP_DIR:
                return ['success' => false, 'message' => '服务器临时目录不存在'];
            case UPLOAD_ERR_CANT_WRITE:
                return ['success' => false, 'message' => '服务器写入失败'];
            case UPLOAD_ERR_EXTENSION:
                return ['success' => false, 'message' => '服务器扩展阻止了上传'];
            default:
                return ['success' => false, 'message' => '上传失败，错误代码：' . $file['error']];
        }
    }

    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => '请选择图片文件'];
    }

    // 使用 fileinfo 检测真实 MIME 类型
    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mimeType = mime_content_type($file['tmp_name']);
    }

    // 如果 fileinfo 检测失败，拒绝上传而不是回退到浏览器提供的类型
    if (empty($mimeType)) {
        return ['success' => false, 'message' => '无法检测文件类型，请确保服务器已安装fileinfo扩展'];
    }

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => '只允许上传 jpg/png/gif/webp 格式的图片'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '图片大小不能超过10MB'];
    }

    // 扩展名白名单校验
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'message' => '不支持的文件扩展名：' . $ext];
    }

    $uploadDir = __DIR__ . '/../uploads/' . $directory . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'uploads/' . $directory . '/' . $filename];
    }

    return ['success' => false, 'message' => '图片上传失败'];
}

/**
 * 生成图片缩略图
 */
function createThumbnail($sourcePath, $maxWidth = 300, $maxHeight = 300) {
    if (!file_exists($sourcePath)) return false;

    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $width = $info[0];
    $height = $info[1];
    $type = $info[2];

    // GIF 动图不生成缩略图，直接返回原图路径以保持动画效果
    if ($type === IMAGETYPE_GIF) {
        return $sourcePath;
    }

    // 计算缩放比例
    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    // 创建缩略图
    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $thumbPath = $sourcePath . '_thumb.jpg';
    imagejpeg($thumb, $thumbPath, 85);

    imagedestroy($thumb);
    imagedestroy($source);

    return $thumbPath;
}

/**
 * 删除图片文件
 */
function deleteImage($path) {
    $fullPath = realpath(__DIR__ . '/../' . $path);
    $uploadsDir = realpath(__DIR__ . '/../uploads');

    // 验证路径在uploads目录内
    if ($fullPath === false || $uploadsDir === false) {
        return;
    }
    if (strpos($fullPath, $uploadsDir) !== 0) {
        return; // 路径不在uploads目录内，拒绝操作
    }

    if (file_exists($fullPath) && is_file($fullPath)) {
        unlink($fullPath);
    }
    $thumbPath = $fullPath . '_thumb.jpg';
    if (file_exists($thumbPath) && is_file($thumbPath)) {
        unlink($thumbPath);
    }
}
