<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 相册 Model。
 */
final class Gallery extends BaseModel
{
    public function getAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM galleries';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM galleries WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM galleries');
    }

    public function create(array $data): int
    {
        return $this->insert('galleries', $data, ['title', 'description', 'cover_image', 'sort_order', 'is_active']);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateById('galleries', $id, $data, ['title', 'description', 'cover_image', 'sort_order', 'is_active']);
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM galleries WHERE id = ?', [$id]);
    }
}
