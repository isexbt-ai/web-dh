<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 导航卡片 Model。
 */
final class Card extends BaseModel
{
    public function getByCategory(?int $categoryId, bool $activeOnly = true, string $sortMethod = 'default'): array
    {
        $sql = 'SELECT c.*, cat.name AS category_name FROM cards c
                LEFT JOIN categories cat ON c.category_id = cat.id';
        $where = [];
        $params = [];

        if ($categoryId !== null) {
            $where[] = 'c.category_id = ?';
            $params[] = $categoryId;
        }
        if ($activeOnly) {
            $where[] = 'c.is_active = 1';
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= $sortMethod === 'click_count'
            ? ' ORDER BY c.click_count DESC, c.id DESC'
            : ' ORDER BY c.sort_order ASC, c.id DESC';

        return $this->fetchAll($sql, $params);
    }

    /** 一次取全部启用卡片（首页按分类分组用，避免 N+1 循环查库）。 */
    public function getAllActive(string $sortMethod = 'default'): array
    {
        $sql = 'SELECT c.*, cat.name AS category_name FROM cards c
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.is_active = 1';
        $sql .= $sortMethod === 'click_count'
            ? ' ORDER BY c.click_count DESC, c.id DESC'
            : ' ORDER BY c.sort_order ASC, c.id DESC';
        return $this->fetchAll($sql);
    }

    public function find(int $id, bool $activeOnly = true): ?array
    {
        $sql = 'SELECT c.*, cat.name AS category_name FROM cards c
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.id = ?';
        if ($activeOnly) {
            $sql .= ' AND c.is_active = 1';
        }
        return $this->fetchOne($sql, [$id]);
    }

    public function getHot(int $limit = 10): array
    {
        return $this->fetchAll('SELECT * FROM cards WHERE is_active = 1 ORDER BY click_count DESC LIMIT ?', [$limit]);
    }

    public function incrementClick(int $id): bool
    {
        return $this->execute('UPDATE cards SET click_count = click_count + 1 WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM cards');
    }

    public function countByCategory(int $categoryId): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM cards WHERE category_id = ?', [$categoryId]);
    }

    public function create(array $data): int
    {
        $allowed = ['category_id', 'title', 'image', 'link', 'detail', 'card_type', 'image_width', 'image_height', 'badge_text', 'sort_order', 'is_active'];
        $fields = array_intersect_key($data, array_flip($allowed));
        $keys = array_keys($fields);
        $this->execute(
            'INSERT INTO cards (' . implode(', ', $keys) . ') VALUES (' . implode(', ', array_fill(0, count($keys), '?')) . ')',
            array_values($fields)
        );
        return $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['category_id', 'title', 'image', 'link', 'detail', 'card_type', 'image_width', 'image_height', 'badge_text', 'sort_order', 'is_active'];
        $fields = array_intersect_key($data, array_flip($allowed));
        if ($fields === []) {
            return false;
        }
        $sets = [];
        $params = [];
        foreach ($fields as $key => $value) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        return $this->execute('UPDATE cards SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM cards WHERE id = ?', [$id]);
    }

    public function imageOf(int $id): ?array
    {
        return $this->fetchOne('SELECT image FROM cards WHERE id = ?', [$id]);
    }
}
