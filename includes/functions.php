<?php
/**
 * 公共函数文件
 */

require_once __DIR__ . '/db.php';

/**
 * 获取客户端真实 IP 地址（支持 Cloudflare CDN 等反向代理）
 *
 * 优先级：
 * 1. HTTP_CF_CONNECTING_IP - Cloudflare 提供的真实客户端IP（最可信）
 * 2. HTTP_X_FORWARDED_FOR - 通用反向代理头
 * 3. HTTP_CLIENT_IP - 部分代理服务器
 * 4. REMOTE_ADDR - 直接连接IP（无代理时）
 */
function getClientIp() {
    $ip = null;

    // 1. Cloudflare 专用请求头（最优先，不可伪造）
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    // 2. 通用 X-Forwarded-For（可能包含多个IP，取第一个）
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // 处理可能包含多个 IP 的情况，取第一个（最原始客户端IP）
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }
    }
    // 3. 其他代理头
    elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // 4. 直接连接的IP
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

/**
 * 安全输出 - 防止XSS攻击
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 获取站点配置（带内存缓存）
 */
function getConfig($key, $default = '') {
    global $pdo;
    static $configCache = null;

    // 首次调用时批量加载所有配置到内存
    if ($configCache === null) {
        $configCache = [];
        try {
            $stmt = $pdo->query("SELECT key, value FROM site_config");
            while ($row = $stmt->fetch()) {
                $configCache[$row['key']] = $row['value'];
            }
        } catch (PDOException $e) {
            // 表可能不存在，返回默认值
        }
    }

    return $configCache[$key] ?? $default;
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
 * 记录安全日志到独立文件
 *
 * @param string $type 日志类型 (login_success, login_failed, logout, password_changed, permission_denied 等)
 * @param string $message 日志内容
 * @param array $extra 额外数据
 */
function logSecurityEvent($type, $message, $extra = []) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/security_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIp();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $username = $_SESSION['admin_username'] ?? 'guest';

    $logEntry = sprintf(
        "[%s] [%s] [IP:%s] [User:%s] %s | UA: %s | Extra: %s\n",
        $timestamp,
        strtoupper($type),
        $ip,
        $username,
        $message,
        $userAgent,
        json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    error_log($logEntry, 3, $logFile);
}

/**
 * 记录访问日志
 *
 * @param string $action 操作描述
 * @param array $data 额外数据
 */
function logAccess($action, $data = []) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/access_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIp();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    $uri = $_SERVER['REQUEST_URI'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $logEntry = sprintf(
        "[%s] %s %s | IP:%s | Action:%s | UA:%s | Data:%s\n",
        $timestamp,
        $method,
        $uri,
        $ip,
        $action,
        $userAgent,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    error_log($logEntry, 3, $logFile);
}
function getCache($key, $ttl = 300) {
    $cacheFile = __DIR__ . '/../data/cache_' . md5($key) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data !== null) {
            return $data;
        }
    }
    return null;
}

/**
 * 设置文件缓存
 */
function setCache($key, $data) {
    $cacheFile = __DIR__ . '/../data/cache_' . md5($key) . '.json';
    file_put_contents($cacheFile, json_encode($data), LOCK_EX);
}

/**
 * 清除所有文件缓存
 */
function clearCache() {
    $cacheDir = __DIR__ . '/../data/';
    foreach (glob($cacheDir . 'cache_*.json') as $file) {
        @unlink($file);
    }
}

/**
 * API 速率限制检查
 * 基于IP的文件缓存实现，默认60次/分钟
 *
 * @param string $action 操作标识（如 'cards', 'click', 'upload' 等）
 * @param int $maxRequests 最大请求次数（默认60）
 * @param int $windowSeconds 时间窗口（默认60秒）
 * @return bool true=允许请求, false=超出限制
 */
function checkRateLimit($action = 'default', $maxRequests = 60, $windowSeconds = 60) {
    $ip = getClientIp();
    $key = 'ratelimit_' . $action . '_' . md5($ip);
    $cacheFile = __DIR__ . '/../data/cache_' . md5($key) . '.json';
    $now = time();

    $requests = [];
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (is_array($data)) {
            // 过滤掉过期的请求记录
            $requests = array_filter($data, function($timestamp) use ($now, $windowSeconds) {
                return ($now - $timestamp) < $windowSeconds;
            });
        }
    }

    // 检查是否超出限制
    if (count($requests) >= $maxRequests) {
        return false;
    }

    // 记录本次请求
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode(array_values($requests)), LOCK_EX);

    return true;
}

/**
 * 检查速率限制并返回JSON错误（如果超出限制）
 *
 * @param string $action 操作标识
 * @param int $maxRequests 最大请求次数
 * @param int $windowSeconds 时间窗口
 * @return bool true=允许继续, false=已返回错误响应
 */
function requireRateLimit($action = 'default', $maxRequests = 60, $windowSeconds = 60) {
    if (!checkRateLimit($action, $maxRequests, $windowSeconds)) {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => '请求过于频繁，请稍后再试',
            'code' => 'RATE_LIMITED'
        ]);
        return false;
    }
    return true;
}
function getNotices($activeOnly = true) {
    $cacheKey = 'notices_' . ($activeOnly ? '1' : '0');
    $cached = getCache($cacheKey, 300);
    if ($cached !== null) {
        return $cached;
    }

    global $pdo;
    $sql = "SELECT * FROM notices";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id DESC";
    $data = $pdo->query($sql)->fetchAll();
    setCache($cacheKey, $data);
    return $data;
}

/**
 * 获取所有分类
 */
function getCategories($activeOnly = true) {
    $cacheKey = 'categories_' . ($activeOnly ? '1' : '0');
    $cached = getCache($cacheKey, 300);
    if ($cached !== null) {
        return $cached;
    }

    global $pdo;
    $sql = "SELECT * FROM categories";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";
    $data = $pdo->query($sql)->fetchAll();
    setCache($cacheKey, $data);
    return $data;
}

/**
 * 获取分类下的卡片
 */
function getCards($categoryId = null, $activeOnly = true, $sortMethod = 'default') {
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

    switch ($sortMethod) {
        case 'click_count':
            $sql .= " ORDER BY c.click_count DESC, c.id DESC";
            break;
        case 'default':
        default:
            $sql .= " ORDER BY c.sort_order ASC, c.id DESC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 记录访问统计（异步队列模式）
 * 写入文件队列，由 processVisitQueue() 批量处理
 */
function recordVisit($page = '') {
    $ip = getClientIp(); // 使用已有的函数获取真实IP（支持反向代理）
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $data = json_encode([
        'page' => $page,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'time' => time()
    ]);

    $queueFile = __DIR__ . '/../data/visit_queue.log';
    file_put_contents($queueFile, $data . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * 处理访问统计队列（批量写入数据库）
 * 应在页面输出完成后调用
 */
function processVisitQueue() {
    global $pdo;

    $queueFile = __DIR__ . '/../data/visit_queue.log';
    if (!file_exists($queueFile) || filesize($queueFile) === 0) {
        return;
    }

    // 读取并清空队列
    $content = file_get_contents($queueFile);
    file_put_contents($queueFile, '');

    if (empty(trim($content))) {
        return;
    }

    $lines = array_filter(explode("\n", trim($content)));
    if (empty($lines)) {
        return;
    }

    // 批量处理：按 IP 去重，只保留每个 IP 的最新记录
    $uniqueVisits = [];
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if (!$data) continue;
        $key = $data['ip'] . '_' . $data['page'];
        $uniqueVisits[$key] = $data;
    }

    foreach ($uniqueVisits as $data) {
        $ip = $data['ip'];
        $page = $data['page'];
        $userAgent = $data['user_agent'];

        // 检查该IP在当天是否已有记录
        try {
            $stmt = $pdo->prepare("SELECT id FROM visit_stats WHERE ip = ? AND visit_date = date('now') LIMIT 1");
            $stmt->execute([$ip]);
            $existing = $stmt->fetch();

            if (!$existing) {
                $stmt = $pdo->prepare("INSERT INTO visit_stats (page, ip, user_agent, visit_date) VALUES (?, ?, ?, date('now'))");
                $stmt->execute([$page, $ip, $userAgent]);
            }
        } catch (PDOException $e) {
            // 忽略写入错误，避免影响页面
            error_log('Visit queue processing error: ' . $e->getMessage());
        }
    }
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
 * 获取总访问量（所有记录数，不去重）
 */
function getTotalVisitsAll() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visit_stats");
    return $stmt->fetch()['count'];
}

/**
 * 获取显示用的热度访客数（前台页脚使用）
 * 公式：真实UV + (点击量/click_divisor) + (今日UV × today_multiplier)
 * 倍数可通过后台配置调整
 */
function getDisplayVisitorCount() {
    $realUv = getTotalVisits();           // 真实总独立IP
    $totalClicks = getTotalClicks();      // 总点击量
    $todayUv = getTodayIpCount();         // 今日独立IP

    // 从配置读取倍数，默认：点击除数100，今日加成倍数2
    $clickDivisor = max(1, intval(getConfig('visitor_click_divisor', '100')));
    $todayMultiplier = max(0, intval(getConfig('visitor_today_multiplier', '2')));

    // 热度公式
    $clickBonus = floor($totalClicks / $clickDivisor);
    $todayBonus = $todayUv * $todayMultiplier;

    $displayCount = $realUv + $clickBonus + $todayBonus;

    // 保底显示，新站也不尴尬
    return max($displayCount, 88);
}

/**
 * 获取今日访问IP数量（独立IP数）
 */
function getTodayIpCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip) as count FROM visit_stats WHERE visit_date = date('now')");
    return $stmt->fetch()['count'];
}
function getTotalVisits() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip) as count FROM visit_stats");
    return $stmt->fetch()['count'];
}

/**
 * 获取24小时内独立访问IP数
 */
function get24hVisits() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip) as count FROM visit_stats WHERE visit_date >= date('now', '-1 day')");
    return $stmt->fetch()['count'];
}

/**
 * 获取总点击量（所有卡片点击数之和）
 */
function getTotalClicks() {
    global $pdo;
    $stmt = $pdo->query("SELECT SUM(click_count) as total FROM cards");
    return $stmt->fetch()['total'] ?? 0;
}

/**
 * 获取总PV（visit_stats表总记录数）
 */
function getTotalPv() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visit_stats");
    return $stmt->fetch()['count'] ?? 0;
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
function getHotCards($perCategory = 3) {
    global $pdo;
    // 每个分类取点击量前 N 的卡片，合并后按点击量排序
    $sql = "SELECT c.*, cat.name as category_name FROM cards c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.is_active = 1 AND c.category_id IN (SELECT id FROM categories WHERE is_active = 1)
            ORDER BY c.category_id, c.click_count DESC";
    $all = $pdo->query($sql)->fetchAll();

    $result = [];
    $seen = [];
    foreach ($all as $card) {
        $catId = $card['category_id'];
        if (!isset($seen[$catId])) $seen[$catId] = 0;
        if ($seen[$catId] < $perCategory) {
            $result[] = $card;
            $seen[$catId]++;
        }
    }
    // 按点击量降序排列
    usort($result, function($a, $b) { return $b['click_count'] - $a['click_count']; });
    return $result;
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
 * 获取缩略图URL（优先WebP，其次JPG，回退原图）
 */
function getThumbnailUrl($imagePath) {
    if (empty($imagePath)) return '';

    $baseDir = __DIR__ . '/../';
    $thumbPath = $imagePath . '_thumb.jpg';
    $webpThumbPath = $thumbPath . '.webp';

    // 优先返回 WebP 缩略图
    if (file_exists($baseDir . $webpThumbPath)) {
        return $webpThumbPath;
    }

    // 其次返回 JPG 缩略图
    if (file_exists($baseDir . $thumbPath)) {
        return $thumbPath;
    }

    // 回退原图
    return $imagePath;
}

/**
 * 渲染响应式图片标签（支持 WebP 回退）
 *
 * @param string $src 图片原始路径
 * @param string $alt 替代文本
 * @param string $class CSS类名
 * @param string $loading loading属性 (lazy/eager/auto)
 * @param array $sizes 响应式尺寸配置 [width, height] 或 null
 * @return string HTML img/picture 标签
 */
function renderResponsiveImage($src, $alt = '', $class = '', $loading = 'lazy', $sizes = null, $includeDims = true) {
    if (empty($src)) {
        return '<div class="' . e($class) . ' card-placeholder">图片</div>';
    }

    $baseDir = __DIR__ . '/../';
    $classAttr = $class ? ' class="' . e($class) . '"' : '';
    $loadingAttr = in_array($loading, ['lazy', 'eager']) ? ' loading="' . $loading . '"' : '';
    $altAttr = $alt ? ' alt="' . e($alt) . '"' : '';

    // 尝试读取图片尺寸，用于减少 CLS
    $dimAttr = '';
    if ($includeDims) {
        $imgFile = $baseDir . $src;
        if (file_exists($imgFile)) {
            $imgSize = @getimagesize($imgFile);
            if ($imgSize !== false) {
                $dimAttr = ' width="' . $imgSize[0] . '" height="' . $imgSize[1] . '"';
            }
        }
    }

    // 获取各种格式的缩略图路径
    $thumbJpg = $src . '_thumb.jpg';
    $thumbWebp = $thumbJpg . '.webp';

    $hasWebp = file_exists($baseDir . $thumbWebp);
    $hasJpg = file_exists($baseDir . $thumbJpg);

    // 确定最佳图片源
    $bestSrc = $hasWebp ? $thumbWebp : ($hasJpg ? $thumbJpg : $src);

    // 构建 srcset（如果有尺寸配置）
    $srcsetAttr = '';
    if (!empty($sizes) && is_array($sizes)) {
        $srcsetParts = [];
        // 生成多种尺寸的缩略图URL（如果存在）
        $widths = [320, 640, 960];
        foreach ($widths as $w) {
            if ($w <= $sizes[0]) {
                $srcsetParts[] = $bestSrc . ' ' . $w . 'w';
            }
        }
        if (!empty($srcsetParts)) {
            $srcsetAttr = ' srcset="' . implode(', ', $srcsetParts) . '"';
        }
    }

    // 构建 sizes 属性
    $sizesAttr = '';
    if (!empty($sizes) && is_array($sizes)) {
        $sizesAttr = ' sizes="(max-width: 480px) 100vw, (max-width: 768px) 50vw, 33vw"';
    }

    // 如果有 WebP 格式，使用 picture 标签提供格式回退
    if ($hasWebp) {
        $html = '<picture>';
        $html .= '<source srcset="' . e($thumbWebp) . '" type="image/webp">';
        if ($hasJpg) {
            $html .= '<source srcset="' . e($thumbJpg) . '" type="image/jpeg">';
        }
        $html .= '<img src="' . e($src) . '"' . $altAttr . $classAttr . $loadingAttr . $dimAttr . $srcsetAttr . $sizesAttr . '>';
        $html .= '</picture>';
        return $html;
    }

    // 没有 WebP，直接返回 img 标签
    return '<img src="' . e($bestSrc) . '"' . $altAttr . $classAttr . $loadingAttr . $dimAttr . $srcsetAttr . $sizesAttr . '>';
}

/**
 * 渲染卡片HTML（用于PHP端SSR）
 * 输出与JS端 renderCards() 完全一致的HTML结构
 */
function renderCardsHtml($cards) {
    if (empty($cards)) {
        echo '<div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="M21 15l-5-5L5 21"/>
            </svg>
            <p>暂无内容</p>
        </div>';
        return;
    }

    foreach ($cards as $card) {
        $cardType = $card['card_type'] ?? 'link';

        // 角标文字
        $badgeText = $card['badge_text'] ?? '';
        $badgeClass = '';
        if ($badgeText) {
            $badgeClass = 'custom';
        } else {
            $badgeClass = $cardType === 'detail' ? 'detail' : 'link';
            $badgeText = $cardType === 'detail' ? '详情' : '外链';
        }

        // 点击事件
        $onclickAttr = $cardType === 'detail'
            ? 'onclick="goToDetail(' . intval($card['id']) . ')"'
            : 'onclick="goToLink(' . intval($card['id']) . ', \'' . rawurlencode($card['link'] ?? '#') . '\')"';

        // 图片处理 - 使用响应式图片（支持WebP回退）
        $imageHtml = '';
        if (!empty($card['image'])) {
            $imageHtml = renderResponsiveImage($card['image'], $card['title'], '', 'lazy');
        } else {
            $imageHtml = '<div class="card-placeholder">图片</div>';
        }

        echo '<div class="card-item" ' . $onclickAttr . '>';
        echo '<span class="card-type-badge ' . $badgeClass . '">' . e($badgeText) . '</span>';
        echo '<div class="card-image">' . $imageHtml . '</div>';
        echo '<div class="card-title">' . e($card['title']) . '</div>';
        echo '</div>';
    }
}
function parseDetail($text) {
    if (empty($text)) return '';

    // 先转义HTML特殊字符
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // 解析图片标记 ![alt](url) -> <img src="url" alt="alt" class="detail-img">
    // 注意：对 URL 进行安全过滤，禁止 javascript: 等伪协议
    $text = preg_replace_callback(
        '/!\[([^\]]*)\]\(([^\)]+)\)/',
        function($matches) {
            $alt = $matches[1];
            $url = $matches[2];
            // 过滤危险协议
            if (preg_match('/^\s*(javascript|data|vbscript|about|chrome):/i', $url)) {
                return ''; // 危险URL，直接移除
            }
            // 只允许 http/https 协议或相对路径
            if (!preg_match('/^(https?:\/\/|\/)/i', $url) && strpos($url, ':') !== false) {
                return ''; // 包含其他协议，移除
            }
            return '<img src="' . $url . '" alt="' . $alt . '" class="detail-img" loading="lazy">';
        },
        $text
    );

    // 保留换行
    $text = nl2br($text);

    return $text;
}

/**
 * 获取留言列表
 */
function getMessages($offset = 0, $limit = 10, $includeDeleted = false) {
    global $pdo;
    $sql = "SELECT * FROM messages";
    if (!$includeDeleted) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * 获取留言总数
 */
function getMessageCount($includeDeleted = false) {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM messages";
    if (!$includeDeleted) {
        $sql .= " WHERE is_active = 1";
    }
    return $pdo->query($sql)->fetchColumn();
}

/**
 * 渲染留言列表HTML（PHP端SSR）
 */
function renderGuestbookMessages($messages) {
    if (empty($messages)) {
        echo '<div class="guestbook-empty">暂无留言，快来抢沙发吧~</div>';
        return;
    }
    foreach ($messages as $msg) {
        echo '<div class="guestbook-item">';
        echo '<div class="guestbook-item-header">';
        echo '<span class="guestbook-item-nickname">' . e($msg['nickname'] ?: '匿名用户') . '</span>';
        echo '<span class="guestbook-item-time">' . formatMessageTime($msg['created_at']) . '</span>';
        echo '</div>';
        echo '<div class="guestbook-item-content">' . e($msg['content']) . '</div>';
        // 如果有回复，显示回复
        if (!empty($msg['reply'])) {
            echo '<div class="guestbook-reply">';
            echo '<div class="guestbook-reply-label">管理员回复</div>';
            echo '<div class="guestbook-reply-content">' . e($msg['reply']) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}

/**
 * 格式化留言时间（刚刚/几分钟前/几小时前/日期）
 */
function formatMessageTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 604800) return floor($diff / 86400) . '天前';

    return date('n月j日 H:i', $time);
}
function uploadImage($file, $directory = 'cards') {
    // 支持图片和视频格式
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'video/quicktime'];
    $maxSize = 50 * 1024 * 1024; // 50MB（视频需要更大）
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov'];

    // 检查上传错误
    if (isset($file['error'])) {
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                return ['success' => false, 'message' => '文件超过服务器大小限制（最大50MB）'];
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'message' => '文件超过表单大小限制'];
            case UPLOAD_ERR_PARTIAL:
                return ['success' => false, 'message' => '文件上传不完整，请重试'];
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'message' => '请选择文件'];
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
        return ['success' => false, 'message' => '请选择文件'];
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

    // 视频文件特殊处理：检查文件头（某些视频可能被识别为 application/octet-stream）
    $isVideo = false;
    if (strpos($mimeType, 'video/') === 0) {
        $isVideo = true;
    } elseif ($mimeType === 'application/octet-stream') {
        // 尝试通过扩展名判断视频
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $videoExts = ['mp4', 'webm', 'mov'];
        if (in_array($ext, $videoExts)) {
            $isVideo = true;
            // 根据扩展名修正 MIME 类型
            $mimeMap = ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime'];
            $mimeType = $mimeMap[$ext] ?? $mimeType;
        }
    }

    if (!in_array($mimeType, $allowedTypes) && !$isVideo) {
        return ['success' => false, 'message' => '只允许上传 jpg/png/gif/webp/mp4/webm/mov 格式的文件'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小不能超过50MB'];
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
        return ['success' => true, 'path' => 'uploads/' . $directory . '/' . $filename, 'is_video' => $isVideo];
    }

    return ['success' => false, 'message' => '文件上传失败'];
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

/**
 * 获取效果展示列表（可按相册筛选）
 */
function getShowcases($activeOnly = true, $galleryId = null) {
    global $pdo;
    $sql = "SELECT * FROM showcase";
    $where = [];
    $params = [];

    if ($activeOnly) {
        $where[] = "is_active = 1";
    }
    if ($galleryId !== null) {
        $where[] = "gallery_id = ?";
        $params[] = $galleryId;
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY sort_order ASC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 获取所有相册合集（包含展示数量）
 */
function getGalleriesWithCount($activeOnly = true) {
    global $pdo;
    $sql = "SELECT g.*, COUNT(s.id) as showcase_count FROM galleries g LEFT JOIN showcase s ON g.id = s.gallery_id AND s.is_active = 1";
    if ($activeOnly) {
        $sql .= " WHERE g.is_active = 1";
    }
    $sql .= " GROUP BY g.id ORDER BY g.sort_order ASC, g.id ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * 获取所有相册合集（兼容旧版，不带展示数量）
 */
function getGalleries($activeOnly = true) {
    global $pdo;
    $sql = "SELECT * FROM galleries";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";
    return $pdo->query($sql)->fetchAll();
}

/**
 * 获取单个相册
 */
function getGallery($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM galleries WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * 获取相册下的展示数量
 */
function getGalleryShowcaseCount($galleryId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM showcase WHERE gallery_id = ? AND is_active = 1");
    $stmt->execute([$galleryId]);
    return $stmt->fetchColumn();
}

/**
 * 上传图片到图床 (img.scdn.io)
 *
 * @param string $localPath 本地图片路径（相对项目根目录）
 * @param array $options 可选参数：outputFormat, cdn_domain, storage_destination
 * @return array ['success' => bool, 'url' => string, 'filename' => string, 'message' => string]
 */
function uploadToImgbed($localPath, $options = []) {
    $fullPath = realpath(__DIR__ . '/../' . $localPath);
    if (!$fullPath || !file_exists($fullPath)) {
        return ['success' => false, 'url' => '', 'filename' => '', 'message' => '本地文件不存在: ' . $localPath];
    }

    $apiUrl = 'https://img.scdn.io/api/v1.php';

    // 构建 multipart 请求
    $boundary = '----WebKitFormBoundary' . uniqid();
    $body = '';

    // 文件字段
    $fileContent = file_get_contents($fullPath);
    $mimeType = mime_content_type($fullPath) ?: 'image/webp';
    $filename = basename($fullPath);

    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"image\"; filename=\"{$filename}\"\r\n";
    $body .= "Content-Type: {$mimeType}\r\n\r\n";
    $body .= $fileContent . "\r\n";

    // 可选参数
    if (!empty($options['outputFormat'])) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"outputFormat\"\r\n\r\n";
        $body .= $options['outputFormat'] . "\r\n";
    }
    if (!empty($options['cdn_domain'])) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"cdn_domain\"\r\n\r\n";
        $body .= $options['cdn_domain'] . "\r\n";
    }
    if (!empty($options['storage_destination'])) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"storage_destination\"\r\n\r\n";
        $body .= $options['storage_destination'] . "\r\n";
    }

    $body .= "--{$boundary}--\r\n";

    $headers = [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Content-Length: ' . strlen($body),
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'url' => '', 'filename' => '', 'message' => 'cURL错误: ' . $curlError];
    }

    if ($httpCode !== 200) {
        return ['success' => false, 'url' => '', 'filename' => '', 'message' => 'HTTP错误码: ' . $httpCode];
    }

    $result = json_decode($response, true);
    if (!$result || !isset($result['success'])) {
        return ['success' => false, 'url' => '', 'filename' => '', 'message' => '图床API返回格式异常'];
    }

    if ($result['success']) {
        return [
            'success' => true,
            'url' => $result['url'] ?? ($result['data']['url'] ?? ''),
            'filename' => $result['data']['filename'] ?? '',
            'message' => $result['message'] ?? '上传成功',
            'data' => $result['data'] ?? []
        ];
    } else {
        return [
            'success' => false,
            'url' => '',
            'filename' => '',
            'message' => $result['message'] ?? $result['error'] ?? '上传失败'
        ];
    }
}

/**
 * 查询图床图片元数据
 *
 * @param string $query 图片ID或完整文件名
 * @return array ['success' => bool, 'data' => array, 'message' => string]
 */
function queryImgbedMeta($query) {
    $apiUrl = 'https://img.scdn.io/api/v1.php?q=' . urlencode($query);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'data' => [], 'message' => 'cURL错误: ' . $curlError];
    }

    $result = json_decode($response, true);
    if (!$result) {
        return ['success' => false, 'data' => [], 'message' => '图床API返回格式异常'];
    }

    return [
        'success' => $result['success'] ?? false,
        'data' => $result['data'] ?? [],
        'message' => $result['message'] ?? $result['error'] ?? '查询失败'
    ];
}

/**
 * 获取图片展示URL（优先图床，失败回退本地）
 *
 * @param array $showcase 效果展示记录数组
 * @return string 可用的图片URL
 */
function getShowcaseImageUrl($showcase) {
    // 优先使用图床URL
    if (!empty($showcase['imgbed_url'])) {
        // 验证图床URL是否可用（简单检查）
        return $showcase['imgbed_url'];
    }

    // 回退到本地图片
    if (!empty($showcase['image'])) {
        return $showcase['image'];
    }

    return '';
}

/**
 * 批量上传本地图片到图床
 *
 * @param array $ids 要上传的showcase ID数组，空数组则上传所有未上传的
 * @return array ['success' => int, 'failed' => int, 'details' => array]
 */
function batchUploadToImgbed($ids = []) {
    global $pdo;

    if (empty($ids)) {
        // 获取所有未上传图床且本地有图片的记录
        $stmt = $pdo->query("SELECT * FROM showcase WHERE imgbed_status = 0 AND image != '' AND is_active = 1 ORDER BY id ASC");
        $items = $stmt->fetchAll();
    } else {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM showcase WHERE id IN ({$placeholders}) AND image != '' ORDER BY id ASC");
        $stmt->execute($ids);
        $items = $stmt->fetchAll();
    }

    $success = 0;
    $failed = 0;
    $details = [];

    foreach ($items as $item) {
        $result = uploadToImgbed($item['image'], [
            'outputFormat' => 'auto',
            'cdn_domain' => 'img.scdn.io'
        ]);

        if ($result['success']) {
            // 更新数据库
            $stmt = $pdo->prepare("UPDATE showcase SET imgbed_url = ?, imgbed_status = 1, imgbed_filename = ?, imgbed_uploaded_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$result['url'], $result['filename'], $item['id']]);
            $success++;
            $details[] = ['id' => $item['id'], 'title' => $item['title'], 'status' => 'success', 'url' => $result['url']];
        } else {
            $failed++;
            $details[] = ['id' => $item['id'], 'title' => $item['title'], 'status' => 'failed', 'message' => $result['message']];
        }

        // 避免触发限流：每次上传后休眠0.5秒
        usleep(500000);
    }

    return ['success' => $success, 'failed' => $failed, 'details' => $details];
}

/**
 * 国家代码转中文名称映射
 */
function getCountryName($code) {
    $map = [
        'CN' => '中国', 'US' => '美国', 'JP' => '日本', 'KR' => '韩国',
        'GB' => '英国', 'DE' => '德国', 'FR' => '法国', 'RU' => '俄罗斯',
        'CA' => '加拿大', 'AU' => '澳大利亚', 'SG' => '新加坡', 'HK' => '中国香港',
        'TW' => '中国台湾', 'MO' => '中国澳门', 'IN' => '印度', 'BR' => '巴西',
        'MX' => '墨西哥', 'ID' => '印度尼西亚', 'TH' => '泰国', 'VN' => '越南',
        'MY' => '马来西亚', 'PH' => '菲律宾', 'IT' => '意大利', 'ES' => '西班牙',
        'NL' => '荷兰', 'SE' => '瑞典', 'NO' => '挪威', 'FI' => '芬兰',
        'DK' => '丹麦', 'PL' => '波兰', 'CZ' => '捷克', 'HU' => '匈牙利',
        'AT' => '奥地利', 'CH' => '瑞士', 'BE' => '比利时', 'IE' => '爱尔兰',
        'PT' => '葡萄牙', 'GR' => '希腊', 'TR' => '土耳其', 'IL' => '以色列',
        'SA' => '沙特阿拉伯', 'AE' => '阿联酋', 'ZA' => '南非', 'EG' => '埃及',
        'NG' => '尼日利亚', 'KE' => '肯尼亚', 'AR' => '阿根廷', 'CL' => '智利',
        'CO' => '哥伦比亚', 'PE' => '秘鲁', 'VE' => '委内瑞拉', 'UA' => '乌克兰',
        'BY' => '白俄罗斯', 'KZ' => '哈萨克斯坦', 'UZ' => '乌兹别克斯坦',
        'PK' => '巴基斯坦', 'BD' => '孟加拉国', 'LK' => '斯里兰卡', 'NP' => '尼泊尔',
        'MM' => '缅甸', 'KH' => '柬埔寨', 'LA' => '老挝', 'MN' => '蒙古',
        'KP' => '朝鲜', 'IR' => '伊朗', 'IQ' => '伊拉克', 'SY' => '叙利亚',
        'JO' => '约旦', 'LB' => '黎巴嫩', 'KW' => '科威特', 'QA' => '卡塔尔',
        'OM' => '阿曼', 'BH' => '巴林', 'YE' => '也门', 'AF' => '阿富汗',
        'TJ' => '塔吉克斯坦', 'KG' => '吉尔吉斯斯坦', 'TM' => '土库曼斯坦',
        'GE' => '格鲁吉亚', 'AM' => '亚美尼亚', 'AZ' => '阿塞拜疆',
        'RO' => '罗马尼亚', 'BG' => '保加利亚', 'RS' => '塞尔维亚',
        'HR' => '克罗地亚', 'SI' => '斯洛文尼亚', 'SK' => '斯洛伐克',
        'LT' => '立陶宛', 'LV' => '拉脱维亚', 'EE' => '爱沙尼亚',
        'MD' => '摩尔多瓦', 'AL' => '阿尔巴尼亚', 'BA' => '波黑',
        'MK' => '北马其顿', 'ME' => '黑山', 'XK' => '科索沃',
        'NZ' => '新西兰', 'FJI' => '斐济', 'PG' => '巴布亚新几内亚',
        'IS' => '冰岛', 'GL' => '格陵兰', 'FO' => '法罗群岛',
        'SJ' => '斯瓦尔巴', 'AX' => '奥兰群岛',
    ];
    return $map[$code] ?? $code;
}

/**
 * 判断IP是否为中国IP（根据国家代码或地区判断）
 */
function isChinaIP($countryCode, $region = '') {
    $chinaCodes = ['CN', 'HK', 'TW', 'MO'];
    return in_array($countryCode, $chinaCodes, true);
}
function isPrivateIP($ip) {
    $parts = array_map('intval', explode('.', $ip));
    if ($parts[0] === 10) return true;
    if ($parts[0] === 172 && $parts[1] >= 16 && $parts[1] <= 31) return true;
    if ($parts[0] === 192 && $parts[1] === 168) return true;
    if ($parts[0] === 127) return true;
    return false;
}

/**
 * 查询IP归属地（带缓存）
 * @param string $ip IP地址
 * @return array|null 归属地信息
 */
function getIpLocation($ip) {
    global $pdo;

    // 验证IP格式
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return null;
    }

    // 1. 先查缓存
    try {
        $stmt = $pdo->prepare("SELECT * FROM ip_location_cache WHERE ip = ?");
        $stmt->execute([$ip]);
        $cached = $stmt->fetch();
        if ($cached) {
            return $cached;
        }
    } catch (PDOException $e) {
        // 缓存表可能不存在，继续查询
    }

    // 2. 内网IP直接返回
    if (isPrivateIP($ip)) {
        return [
            'ip' => $ip,
            'country' => '内网',
            'region' => '-',
            'city' => '-',
            'isp' => '-',
            'org' => '-',
            'loc' => '-',
            'timezone' => '-'
        ];
    }

    // 3. 调用 ipinfo.io API
    $url = "https://ipinfo.io/{$ip}/json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || isset($data['bogon']) || isset($data['error'])) {
        return null;
    }

    // 4. 解析ISP
    $isp = '-';
    $org = '-';
    if (!empty($data['org']) && strpos($data['org'], ' ') !== false) {
        $parts = explode(' ', $data['org'], 2);
        $org = $parts[0];
        $isp = $parts[1];
    } elseif (!empty($data['org'])) {
        $isp = $data['org'];
    }

    // 5. 存入缓存
    try {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO ip_location_cache (ip, country, region, city, isp, org, loc, timezone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $ip,
            $data['country'] ?? '-',
            $data['region'] ?? '-',
            $data['city'] ?? '-',
            $isp,
            $org,
            $data['loc'] ?? '-',
            $data['timezone'] ?? '-'
        ]);
    } catch (PDOException $e) {
        // 忽略缓存写入错误
    }

    return [
        'ip' => $ip,
        'country' => $data['country'] ?? '-',
        'country_name' => getCountryName($data['country'] ?? ''),
        'region' => $data['region'] ?? '-',
        'city' => $data['city'] ?? '-',
        'isp' => $isp,
        'org' => $org,
        'loc' => $data['loc'] ?? '-',
        'timezone' => $data['timezone'] ?? '-'
    ];
}

/**
 * 清理IP归属地缓存
 * @param int $maxAgeDays 清理多少天前的缓存，默认30天
 * @return int 清理的记录数
 */
function clearIpLocationCache($maxAgeDays = 30) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM ip_location_cache WHERE created_at < datetime('now', '-? days')");
        $stmt->execute([$maxAgeDays]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Clear IP cache error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * 获取IP缓存统计信息
 * @return array [total, oldest, newest]
 */
function getIpCacheStats() {
    global $pdo;
    try {
        $total = $pdo->query("SELECT COUNT(*) FROM ip_location_cache")->fetchColumn();
        $oldest = $pdo->query("SELECT MIN(created_at) FROM ip_location_cache")->fetchColumn();
        $newest = $pdo->query("SELECT MAX(created_at) FROM ip_location_cache")->fetchColumn();
        $size = $pdo->query("SELECT SUM(LENGTH(ip) + LENGTH(country) + LENGTH(region) + LENGTH(city) + LENGTH(isp) + LENGTH(org) + LENGTH(loc) + LENGTH(timezone)) FROM ip_location_cache")->fetchColumn();
        return ['total' => $total, 'oldest' => $oldest, 'newest' => $newest, 'size' => $size];
    } catch (PDOException $e) {
        return ['total' => 0, 'oldest' => null, 'newest' => null, 'size' => 0];
    }
}

/**
 * 获取当前页面完整 URL
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
}

/**
 * 智能 HTML 截断（在完整词/句后截断）
 */
function smartTruncate($text, $length = 160) {
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    if (mb_strlen($text) <= $length) return $text;
    $truncated = mb_substr($text, 0, $length);
    // 在最后一个标点或空格处截断
    $lastPunct = 0;
    for ($i = mb_strlen($truncated) - 1; $i >= 0; $i--) {
        $char = mb_substr($truncated, $i, 1);
        if (in_array($char, ['，', '。', '！', '？', '；', '、', ' ', ',', '.', '!', '?', ';'])) {
            $lastPunct = $i;
            break;
        }
    }
    if ($lastPunct > $length * 0.6) {
        $truncated = mb_substr($truncated, 0, $lastPunct);
    }
    return $truncated . '...';
}

/**
 * 生成 JSON-LD 结构化数据
 */
function generateJsonLd($data) {
    return '<script type="application/ld+json">' . json_encode(array_merge(['@context' => 'https://schema.org'], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
