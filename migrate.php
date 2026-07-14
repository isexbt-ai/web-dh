<?php
/**
 * 数据库迁移脚本
 * 用于修复远程服务器上缺失的数据库字段
 *
 * 使用方法：
 * 1. 将本文件上传到网站根目录
 * 2. 访问 https://你的域名/migrate.php
 * 3. 查看执行结果后删除本文件
 */

require_once 'includes/db.php';

echo "<pre>数据库迁移开始...\n\n";

$errors = [];
$success = [];

// 1. 检查并添加 cards 表的 is_hot 字段
try {
    $pdo->exec("ALTER TABLE cards ADD COLUMN is_hot INTEGER DEFAULT 0");
    $success[] = "cards.is_hot 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "cards.is_hot 字段已存在，跳过";
    } else {
        $errors[] = "cards.is_hot 添加失败: " . $e->getMessage();
    }
}

// 2. 检查并添加 cards 表的 badge_text 字段
try {
    $pdo->exec("ALTER TABLE cards ADD COLUMN badge_text TEXT DEFAULT ''");
    $success[] = "cards.badge_text 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "cards.badge_text 字段已存在，跳过";
    } else {
        $errors[] = "cards.badge_text 添加失败: " . $e->getMessage();
    }
}

// 3. 检查并添加 cards 表的 detail 字段
try {
    $pdo->exec("ALTER TABLE cards ADD COLUMN detail TEXT DEFAULT ''");
    $success[] = "cards.detail 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "cards.detail 字段已存在，跳过";
    } else {
        $errors[] = "cards.detail 添加失败: " . $e->getMessage();
    }
}

// 4. 检查并添加 cards 表的 card_type 字段
try {
    $pdo->exec("ALTER TABLE cards ADD COLUMN card_type TEXT DEFAULT 'link'");
    $success[] = "cards.card_type 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "cards.card_type 字段已存在，跳过";
    } else {
        $errors[] = "cards.card_type 添加失败: " . $e->getMessage();
    }
}

// 5. 检查并添加 cards 表的 image_width 和 image_height 字段
try {
    $pdo->exec("ALTER TABLE cards ADD COLUMN image_width INTEGER DEFAULT 0");
    $success[] = "cards.image_width 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "cards.image_width 字段已存在，跳过";
    } else {
        $errors[] = "cards.image_width 添加失败: " . $e->getMessage();
    }
}

try {
    $pdo->exec("ALTER TABLE cards ADD COLUMN image_height INTEGER DEFAULT 0");
    $success[] = "cards.image_height 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "cards.image_height 字段已存在，跳过";
    } else {
        $errors[] = "cards.image_height 添加失败: " . $e->getMessage();
    }
}

// 6. 检查并添加 messages 表的 reply 字段
try {
    $pdo->exec("ALTER TABLE messages ADD COLUMN reply TEXT DEFAULT ''");
    $success[] = "messages.reply 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "messages.reply 字段已存在，跳过";
    } else {
        $errors[] = "messages.reply 添加失败: " . $e->getMessage();
    }
}

// 7. 检查并添加 messages 表的 replied_at 字段
try {
    $pdo->exec("ALTER TABLE messages ADD COLUMN replied_at TIMESTAMP");
    $success[] = "messages.replied_at 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "messages.replied_at 字段已存在，跳过";
    } else {
        $errors[] = "messages.replied_at 添加失败: " . $e->getMessage();
    }
}

// 8. 检查并添加 showcase 表的 media_type 字段
try {
    $pdo->exec("ALTER TABLE showcase ADD COLUMN media_type TEXT DEFAULT 'image'");
    $success[] = "showcase.media_type 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "showcase.media_type 字段已存在，跳过";
    } else {
        $errors[] = "showcase.media_type 添加失败: " . $e->getMessage();
    }
}

// 9. 检查并添加 cards 表的 view_count 字段
try {
    $pdo->exec("ALTER TABLE cards ADD COLUMN view_count INTEGER DEFAULT 0");
    $success[] = "cards.view_count 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "cards.view_count 字段已存在，跳过";
    } else {
        $errors[] = "cards.view_count 添加失败: " . $e->getMessage();
    }
}

// 10. 检查并添加 links 表的 icon 字段
try {
    $pdo->exec("ALTER TABLE links ADD COLUMN icon TEXT DEFAULT ''");
    $success[] = "links.icon 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "links.icon 字段已存在，跳过";
    } else {
        $errors[] = "links.icon 添加失败: " . $e->getMessage();
    }
}

// 11. 检查并添加 site_config 表的 type 字段
try {
    $pdo->exec("ALTER TABLE site_config ADD COLUMN type TEXT DEFAULT 'text'");
    $success[] = "site_config.type 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "site_config.type 字段已存在，跳过";
    } else {
        $errors[] = "site_config.type 添加失败: " . $e->getMessage();
    }
}

// 12. 检查并添加 notices 表的 content 字段
try {
    $pdo->exec("ALTER TABLE notices ADD COLUMN content TEXT DEFAULT ''");
    $success[] = "notices.content 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "notices.content 字段已存在，跳过";
    } else {
        $errors[] = "notices.content 添加失败: " . $e->getMessage();
    }
}

// 13. 检查并添加 showcase 表的 sort_order 字段
try {
    $pdo->exec("ALTER TABLE showcase ADD COLUMN sort_order INTEGER DEFAULT 0");
    $success[] = "showcase.sort_order 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "showcase.sort_order 字段已存在，跳过";
    } else {
        $errors[] = "showcase.sort_order 添加失败: " . $e->getMessage();
    }
}

// 14. 检查并添加 showcase 表的 is_active 字段
try {
    $pdo->exec("ALTER TABLE showcase ADD COLUMN is_active INTEGER DEFAULT 1");
    $success[] = "showcase.is_active 字段已添加";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "showcase.is_active 字段已存在，跳过";
    } else {
        $errors[] = "showcase.is_active 添加失败: " . $e->getMessage();
    }
}

// 输出结果
echo "===== 成功 =====\n";
foreach ($success as $s) {
    echo "✓ $s\n";
}

if (!empty($errors)) {
    echo "\n===== 错误 =====\n";
    foreach ($errors as $e) {
        echo "✗ $e\n";
    }
}

// 显示当前数据库表结构
echo "\n===== 当前数据库表结构 =====\n";
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "\n表: $table\n";
    $columns = $pdo->query("PRAGMA table_info($table)")->fetchAll();
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }
}

echo "\n===== 迁移完成 =====\n";
echo "请删除本文件(migrate.php)以确保安全。\n";
echo "</pre>";
