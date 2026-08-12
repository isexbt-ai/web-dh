<?php

declare(strict_types=1);

namespace App\Model;

use PDO;

/**
 * Model 基类：封装 PDO 通用查询，子类只提供业务查询方法（不含业务判断）。
 */
abstract class BaseModel
{
    protected PDO $_pdo;

    public function __construct(PDO $pdo)
    {
        $this->_pdo = $pdo;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->_pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 通用插入：按字段白名单过滤后写入指定表，返回自增 ID。
     * 白名单防止调用方传入任意列名。
     */
    protected function insert(string $table, array $data, array $allowed): int
    {
        $fields = array_intersect_key($data, array_flip($allowed));
        $keys = array_keys($fields);
        $this->execute(
            "INSERT INTO $table (" . implode(', ', $keys) . ') VALUES (' . implode(', ', array_fill(0, count($keys), '?')) . ')',
            array_values($fields)
        );
        return $this->lastInsertId();
    }

    /**
     * 通用更新：按字段白名单过滤后按 ID 更新，返回是否执行成功。
     */
    protected function updateById(string $table, int $id, array $data, array $allowed): bool
    {
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
        return $this->execute("UPDATE $table SET " . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public function lastInsertId(): int
    {
        return (int) $this->_pdo->lastInsertId();
    }
}
