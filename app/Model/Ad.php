<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 广告轮播 Model。
 */
final class Ad extends BaseModel
{
    public function getAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM ads';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM ads WHERE id = ?', [$id]);
    }

    public function count(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM ads');
    }

    public function create(array $data): int
    {
        return $this->insert('ads', $data, ['title', 'image', 'link', 'sort_order', 'is_active']);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateById('ads', $id, $data, ['title', 'image', 'link', 'sort_order', 'is_active']);
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM ads WHERE id = ?', [$id]);
    }

    /** 取单条记录的图片路径（删除前校验用）。 */
    public function imageOf(int $id): ?array
    {
        return $this->fetchOne('SELECT image FROM ads WHERE id = ?', [$id]);
    }
}
