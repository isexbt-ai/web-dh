<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * 数据库迁移：CREATE TABLE IF NOT EXISTS + ALTER ADD COLUMN 兜底。
 * 与生产 nav.db 表结构完全兼容，零数据迁移；只增不删不重构现有字段。
 */
final class Migrations
{
    public static function run(PDO $pdo): void
    {
        self::createTables($pdo);
        self::addColumns($pdo);
        self::seedDefaults($pdo);
        self::createIndexes($pdo);
    }

    private static function createTables(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_config (
            id INTEGER PRIMARY KEY,
            key TEXT UNIQUE NOT NULL,
            value TEXT,
            type TEXT DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            image TEXT,
            link TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

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
            badge_text TEXT DEFAULT '',
            sort_order INTEGER DEFAULT 0,
            click_count INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS visit_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page TEXT,
            ip TEXT,
            user_agent TEXT,
            visit_date DATE,
            visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            url TEXT NOT NULL,
            icon TEXT DEFAULT '',
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nickname TEXT DEFAULT '',
            content TEXT NOT NULL,
            ip TEXT,
            user_agent TEXT,
            is_active INTEGER DEFAULT 1,
            reply TEXT DEFAULT '',
            replied_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS showcase (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            image TEXT,
            media_type TEXT DEFAULT 'image',
            imgbed_url TEXT DEFAULT '',
            imgbed_status INTEGER DEFAULT 0,
            imgbed_filename TEXT DEFAULT '',
            imgbed_uploaded_at TIMESTAMP,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            gallery_id INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS galleries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT DEFAULT '',
            cover_image TEXT DEFAULT '',
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE,
            summary TEXT,
            content TEXT NOT NULL,
            cover_image TEXT,
            keywords TEXT,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            attempts INTEGER DEFAULT 0,
            locked_until TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ip_location_cache (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT UNIQUE NOT NULL,
            country TEXT,
            region TEXT,
            city TEXT,
            isp TEXT,
            org TEXT,
            loc TEXT,
            timezone TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    private static function addColumns(PDO $pdo): void
    {
        // 兼容性兜底：老库缺失字段时补列
        $checks = [
            ['admin_users', 'role', "TEXT DEFAULT 'admin'"],
            ['cards', 'detail', "TEXT DEFAULT ''"],
            ['cards', 'card_type', "TEXT DEFAULT 'link'"],
            ['cards', 'image_width', 'INTEGER DEFAULT 0'],
            ['cards', 'image_height', 'INTEGER DEFAULT 0'],
            ['cards', 'badge_text', "TEXT DEFAULT ''"],
            ['messages', 'reply', "TEXT DEFAULT ''"],
            ['messages', 'replied_at', 'TIMESTAMP'],
            ['showcase', 'media_type', "TEXT DEFAULT 'image'"],
            ['showcase', 'gallery_id', 'INTEGER DEFAULT 1'],
        ];
        foreach ($checks as [$table, $column, $def]) {
            try {
                $pdo->exec("ALTER TABLE $table ADD COLUMN $column $def");
            } catch (\PDOException $e) {
                // 列已存在，忽略
            }
        }
    }

    private static function seedDefaults(PDO $pdo): void
    {
        // 默认相册合集
        $count = (int) $pdo->query('SELECT COUNT(*) FROM galleries')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare('INSERT INTO galleries (title, sort_order) VALUES (?, ?)');
            foreach (['默认相册', '精选推荐', '最新上传'] as $i => $name) {
                $stmt->execute([$name, $i]);
            }
        }

        // 默认分类
        $count = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare('INSERT INTO categories (name, sort_order) VALUES (?, ?)');
            foreach (['目录一', '目录二', '目录三'] as $i => $name) {
                $stmt->execute([$name, $i]);
            }
        }
    }

    private static function createIndexes(PDO $pdo): void
    {
        $sqls = [
            'CREATE INDEX IF NOT EXISTS idx_cards_category ON cards(category_id)',
            'CREATE INDEX IF NOT EXISTS idx_cards_active ON cards(is_active)',
            'CREATE INDEX IF NOT EXISTS idx_cards_sort ON cards(sort_order)',
            'CREATE INDEX IF NOT EXISTS idx_cards_click ON cards(click_count)',
            'CREATE INDEX IF NOT EXISTS idx_visit_stats_ip_date ON visit_stats(ip, visit_date)',
            'CREATE INDEX IF NOT EXISTS idx_visit_stats_date ON visit_stats(visit_date)',
            'CREATE INDEX IF NOT EXISTS idx_messages_ip ON messages(ip)',
            'CREATE INDEX IF NOT EXISTS idx_messages_created ON messages(created_at)',
            'CREATE INDEX IF NOT EXISTS idx_showcase_gallery ON showcase(gallery_id)',
            'CREATE INDEX IF NOT EXISTS idx_showcase_active ON showcase(is_active)',
            'CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip)',
        ];
        foreach ($sqls as $sql) {
            try {
                $pdo->exec($sql);
            } catch (\PDOException $e) {
                error_log('Index creation failed: ' . $e->getMessage());
            }
        }
    }
}
