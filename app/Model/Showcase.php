<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 效果展示 Model。
 */
final class Showcase extends BaseModel
{
    public function getAll(bool $activeOnly = true, ?int $galleryId = null): array
    {
        $sql = 'SELECT * FROM showcase';
        $where = [];
        $params = [];
        if ($activeOnly) {
            $where[] = 'is_active = 1';
        }
        if ($galleryId !== null) {
            $where[] = 'gallery_id = ?';
            $params[] = $galleryId;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY sort_order ASC, id DESC';
        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM showcase WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM showcase');
    }

    public function create(array $data): int
    {
        return $this->insert(
            'showcase',
            $data,
            ['title', 'image', 'media_type', 'gallery_id', 'sort_order', 'is_active']
        );
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateById(
            'showcase',
            $id,
            $data,
            ['title', 'image', 'media_type', 'gallery_id', 'sort_order', 'is_active']
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM showcase WHERE id = ?', [$id]);
    }

    /** 取单条记录图片路径（删除前校验用）。 */
    public function imageOf(int $id): ?array
    {
        return $this->fetchOne('SELECT image FROM showcase WHERE id = ?', [$id]);
    }
}
