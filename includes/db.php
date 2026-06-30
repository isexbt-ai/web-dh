<?php
/**
 * 数据库连接配置文件
 * 使用PDO连接SQLite数据库
 */

// 数据库文件路径
$dbPath = __DIR__ . '/../data/nav.db';

// 创建数据库目录（如果不存在）
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 设置时区
    $pdo->exec("PRAGMA timezone = '+00:00'");

} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('系统维护中，请稍后访问');
}

/**
 * 初始化数据库表结构
 */
function initDatabase($pdo) {
    // 快速检查：如果所有核心表都已存在，跳过初始化
    $requiredTables = ['site_config', 'ads', 'notices', 'categories', 'cards', 'visit_stats', 'admin_users', 'links', 'messages', 'showcase'];
    try {
        $existing = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")
                        ->fetchAll(PDO::FETCH_COLUMN);
        if (count(array_intersect($requiredTables, $existing)) === count($requiredTables)) {
            return; // 所有表已存在，跳过初始化
        }
    } catch (PDOException $e) {
        // 如果查询失败（数据库未初始化），继续执行初始化
    }

    // 站点配置表
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_config (
        id INTEGER PRIMARY KEY,
        key TEXT UNIQUE NOT NULL,
        value TEXT,
        type TEXT DEFAULT 'text',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 广告位表
    $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        image TEXT,
        link TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 公告表
    $pdo->exec("CREATE TABLE IF NOT EXISTS notices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 分类目录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 导航卡片表
    $pdo->exec("CREATE TABLE IF NOT EXISTS cards (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        title TEXT NOT NULL,
        image TEXT,
        link TEXT,
        detail TEXT DEFAULT '',
        card_type TEXT DEFAULT 'link',
        image_width INTEGER DEFAULT 0,
        image_height INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        click_count INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");

    // 迁移：为已存在的cards表添加detail字段
    try {
        $pdo->exec("ALTER TABLE cards ADD COLUMN detail TEXT DEFAULT ''");
    } catch (PDOException $e) {
        // 字段已存在，忽略错误
    }

    // 迁移：为已存在的cards表添加card_type字段
    try {
        $pdo->exec("ALTER TABLE cards ADD COLUMN card_type TEXT DEFAULT 'link'");
    } catch (PDOException $e) {
        // 字段已存在，忽略错误
    }

    // 迁移：为已存在的cards表添加image_width和image_height字段
    try {
        $pdo->exec("ALTER TABLE cards ADD COLUMN image_width INTEGER DEFAULT 0");
    } catch (PDOException $e) {
        // 字段已存在，忽略错误
    }
    try {
        $pdo->exec("ALTER TABLE cards ADD COLUMN image_height INTEGER DEFAULT 0");
    } catch (PDOException $e) {
        // 字段已存在，忽略错误
    }

    // 迁移：为已存在的cards表添加badge_text字段（自定义角标文字）
    try {
        $pdo->exec("ALTER TABLE cards ADD COLUMN badge_text TEXT DEFAULT ''");
    } catch (PDOException $e) {
        // 字段已存在，忽略错误
    }

    // 访问统计表
    $pdo->exec("CREATE TABLE IF NOT EXISTS visit_stats (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page TEXT,
        ip TEXT,
        user_agent TEXT,
        visit_date DATE,
        visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 管理员表
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 链接管理表（前台功能菜单）
    $pdo->exec("CREATE TABLE IF NOT EXISTS links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        url TEXT NOT NULL,
        icon TEXT DEFAULT '',
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 留言板表
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nickname TEXT DEFAULT '',
        content TEXT NOT NULL,
        ip TEXT,
        user_agent TEXT,
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 效果展示表
    $pdo->exec("CREATE TABLE IF NOT EXISTS showcase (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        image TEXT,
        imgbed_url TEXT DEFAULT '',
        imgbed_status INTEGER DEFAULT 0,
        imgbed_filename TEXT DEFAULT '',
        imgbed_uploaded_at TIMESTAMP,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 插入默认管理员账号 (admin / 随机强密码)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        // 生成随机强密码
        $defaultPassword = bin2hex(random_bytes(8)); // 16位随机密码
        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $hash]);
        // 记录到日志，提醒用户修改
        error_log("[SECURITY] 默认管理员账号已创建。用户名: admin, 初始密码: {$defaultPassword} - 请登录后立即修改密码！");
    }

    // 插入默认站点配置
    $defaultConfigs = [
        ['site_title', '美女导航', 'text'],
        ['avatar', '', 'image'],
        ['contact_info', '微信：xxx', 'text'],
        ['site_description', '精选美女导航网站', 'text'],
        ['cards_per_row_desktop', 'repeat(auto-fill, 120px)', 'text'],
        ['cards_per_row_tablet', 'repeat(4, 1fr)', 'text'],
        ['cards_per_row_mobile', 'repeat(3, 1fr)', 'text'],
        ['guestbook_enabled', '1', 'toggle'],
        ['guestbook_title', '留言板', 'text'],
        ['guestbook_subtitle', '欢迎留下你的想法', 'text'],
        ['guestbook_image', '', 'image'],
    ];

    foreach ($defaultConfigs as $config) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO site_config (key, value, type) VALUES (?, ?, ?)");
        $stmt->execute($config);
    }

    // 插入默认分类
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $categories = ['目录一', '目录二', '目录三'];
        foreach ($categories as $index => $name) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
            $stmt->execute([$name, $index]);
        }
    }
}

// 初始化数据库
initDatabase($pdo);

// 迁移：为已存在的 messages 表添加 reply 和 replied_at 字段
// 注意：这段代码在 initDatabase 外部，确保每次请求都会执行
try {
    $pdo->exec("ALTER TABLE messages ADD COLUMN reply TEXT DEFAULT ''");
} catch (PDOException $e) {
    // 字段已存在，忽略错误
}
try {
    $pdo->exec("ALTER TABLE messages ADD COLUMN replied_at TIMESTAMP");
} catch (PDOException $e) {
    // 字段已存在，忽略错误
}
