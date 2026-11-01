<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 文章 Model。
 */
final class Article extends BaseModel
{
    /** 前台列表（仅列字段，避免大段正文进列表查询）。 */
    public function getAllActive(int $offset = 0, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT id, title, slug, summary, cover_image, keywords, created_at FROM articles
             WHERE is_active = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /** 后台列表（含停用文章）。 */
    public function getAllAdmin(): array
    {
        return $this->fetchAll(
            'SELECT id, title, slug, summary, cover_image, keywords, is_active, created_at FROM articles ORDER BY created_at DESC'
        );
    }

    public function find(int $id, bool $activeOnly = true): ?array
    {
        $sql = 'SELECT * FROM articles WHERE id = ?';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        return $this->fetchOne($sql, [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM articles');
    }

    public function countActive(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM articles WHERE is_active = 1');
    }

    public function create(array $data): int
    {
        return $this->insert(
            'articles',
            $data,
            ['title', 'slug', 'summary', 'content', 'cover_image', 'keywords', 'is_active']
        );
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateById(
            'articles',
            $id,
            $data,
            ['title', 'slug', 'summary', 'content', 'cover_image', 'keywords', 'is_active']
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM articles WHERE id = ?', [$id]);
    }

    /** 取封面图路径（删除前校验用）。 */
    public function coverImageOf(int $id): ?array
    {
        return $this->fetchOne('SELECT cover_image FROM articles WHERE id = ?', [$id]);
    }

    /** sitemap/RSS 用：全部活跃文章的关键字段。 */
    public function sitemapItems(): array
    {
        return $this->fetchAll('SELECT id, slug, title, created_at FROM articles WHERE is_active = 1 ORDER BY created_at DESC');
    }

    /**
     * 全站搜索：匹配标题、摘要、关键词与正文。
     * 关键词参数绑定，% 与 _ 转义为字面量（避免通配符注入）。
     */
    public function search(string $keyword, int $limit = 10): array
    {
        $like = '%' . $this->escapeLike($keyword) . '%';
        return $this->fetchAll(
            "SELECT id, title, slug, summary, cover_image, keywords, created_at
             FROM articles
             WHERE is_active = 1
               AND (title LIKE ? ESCAPE '\\'
                    OR summary LIKE ? ESCAPE '\\'
                    OR keywords LIKE ? ESCAPE '\\'
                    OR content LIKE ? ESCAPE '\\')
             ORDER BY created_at DESC
             LIMIT ?",
            [$like, $like, $like, $like, $limit]
        );
    }
}
