<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 后台实体元数据注册表。
 *
 * 把「字段白名单 + 类型转换 + 校验 + URL 清洗」集中成一份声明，
 * 使 Admin\ApiController 的 save/delete 不再为每个实体手写一遍 case。
 *
 * 每项定义：
 *   fields  字段名 => 类型（string|int|bool），决定 clean() 的转换与落库范围
 *   url     需做伪协议过滤的 URL 字段名列表
 *   require 非空必填字段（值经转换后判空），缺失时返回错误信息
 *   max     字段最大长度（字符串截断）
 *   check   额外校验回调：fn(array $data): ?string 返回错误信息或 null
 */
final class EntityRegistry
{
    /**
     * 实体定义表。
     *
     * @var array<string, array{fields: array<string, string>, url?: list<string>, require?: list<string>, max?: array<string, int>, check?: callable}>
     */
    private const ENTITIES = [
        'category' => [
            'fields' => ['name' => 'string', 'sort_order' => 'int', 'is_active' => 'bool'],
            'require' => ['name'],
            'max' => ['name' => 30],
        ],
        'card' => [
            'fields' => [
                'category_id' => 'int', 'title' => 'string', 'image' => 'string', 'link' => 'string',
                'detail' => 'string', 'card_type' => 'string', 'image_width' => 'int',
                'image_height' => 'int', 'badge_text' => 'string', 'sort_order' => 'int', 'is_active' => 'bool',
            ],
            'url' => ['link'],
            'require' => ['title'],
        ],
        'ad' => [
            'fields' => ['title' => 'string', 'image' => 'string', 'link' => 'string', 'sort_order' => 'int', 'is_active' => 'bool'],
            'url' => ['link'],
        ],
        'notice' => [
            'fields' => ['title' => 'string', 'content' => 'string', 'sort_order' => 'int', 'is_active' => 'bool'],
            'require' => ['title'],
        ],
        'article' => [
            'fields' => [
                'title' => 'string', 'slug' => 'string', 'summary' => 'string', 'content' => 'string',
                'cover_image' => 'string', 'keywords' => 'string', 'is_active' => 'bool',
            ],
            'require' => ['title'],
        ],
        'showcase' => [
            'fields' => [
                'title' => 'string', 'image' => 'string', 'media_type' => 'string', 'video_url' => 'string',
                'poster_url' => 'string', 'gallery_id' => 'int', 'sort_order' => 'int', 'is_active' => 'bool',
            ],
            'require' => ['title'],
        ],
        'gallery' => [
            'fields' => ['title' => 'string', 'description' => 'string', 'cover_image' => 'string', 'sort_order' => 'int', 'is_active' => 'bool'],
            'require' => ['title'],
        ],
        'link' => [
            'fields' => ['title' => 'string', 'url' => 'string', 'icon' => 'string', 'sort_order' => 'int', 'is_active' => 'bool'],
            'url' => ['url'],
            'require' => ['title', 'url'],
        ],
    ];

    /** 该实体是否在注册表中（可走通用 save/delete 流程）。 */
    public static function has(string $entity): bool
    {
        return isset(self::ENTITIES[$entity]);
    }

    /** 实体字段定义（字段名 => 类型）。 */
    public static function fields(string $entity): array
    {
        return self::ENTITIES[$entity]['fields'] ?? [];
    }

    /** 实体的完整定义。 */
    public static function definition(string $entity): array
    {
        return self::ENTITIES[$entity] ?? [];
    }

    /** 全部实体名。 */
    public static function entities(): array
    {
        return array_keys(self::ENTITIES);
    }
}
