<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 分类 Model。
 */
final class Category extends BaseModel
{
    public function getAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM categories';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    public function countCards(int $categoryId): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM cards WHERE category_id = ?', [$categoryId]);
    }

    public function create(string $name, int $sortOrder, int $isActive): int
    {
        $this->execute('INSERT INTO categories (name, sort_order, is_active) VALUES (?, ?, ?)', [$name, $sortOrder, $isActive]);
        return $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateById('categories', $id, $data, ['name', 'sort_order', 'is_active']);
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM categories WHERE id = ?', [$id]);
    }
}
