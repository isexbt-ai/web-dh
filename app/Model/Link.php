<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 友情链接 Model。
 */
final class Link extends BaseModel
{
    public function getAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM links';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM links WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM links');
    }

    public function create(array $data): int
    {
        return $this->insert('links', $data, ['title', 'url', 'icon', 'sort_order', 'is_active']);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateById('links', $id, $data, ['title', 'url', 'icon', 'sort_order', 'is_active']);
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM links WHERE id = ?', [$id]);
    }
}
