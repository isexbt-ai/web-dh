<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 公告 Model。
 */
final class Notice extends BaseModel
{
    public function getAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM notices';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM notices WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM notices');
    }

    public function create(array $data): int
    {
        return $this->insert('notices', $data, ['title', 'content', 'sort_order', 'is_active']);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateById('notices', $id, $data, ['title', 'content', 'sort_order', 'is_active']);
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM notices WHERE id = ?', [$id]);
    }
}
