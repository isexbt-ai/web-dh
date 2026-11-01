<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\EntityRegistry;
use PHPUnit\Framework\TestCase;

/**
 * 实体注册表测试：保证字段白名单、必填项与 URL 清洗范围不被误改。
 *
 * 注册表是后台所有实体的唯一事实来源，一处改动会影响全部后台 CRUD，
 * 因此这里逐项锁定关键约束。
 */
final class EntityRegistryTest extends TestCase
{
    public function testAllEightEntitiesAreRegistered(): void
    {
        $expected = ['category', 'card', 'ad', 'notice', 'article', 'showcase', 'gallery', 'link'];
        self::assertSame($expected, EntityRegistry::entities());
    }

    public function testUnknownEntityIsNotRegistered(): void
    {
        self::assertFalse(EntityRegistry::has('unknown'));
        self::assertFalse(EntityRegistry::has(''));
        self::assertTrue(EntityRegistry::has('card'));
    }

    /** 字段白名单必须与各 Model 的落库字段一致，否则会静默丢字段。 */
    public function testFieldWhitelistsMatchModelColumns(): void
    {
        $expected = [
            'category' => ['name', 'sort_order', 'is_active'],
            'card' => [
                'category_id', 'title', 'image', 'link', 'detail', 'card_type',
                'image_width', 'image_height', 'badge_text', 'sort_order', 'is_active',
            ],
            'ad' => ['title', 'image', 'link', 'sort_order', 'is_active'],
            'notice' => ['title', 'content', 'sort_order', 'is_active'],
            'article' => ['title', 'slug', 'summary', 'content', 'cover_image', 'keywords', 'is_active'],
            'showcase' => [
                'title', 'image', 'media_type', 'video_url', 'poster_url',
                'gallery_id', 'sort_order', 'is_active',
            ],
            'gallery' => ['title', 'description', 'cover_image', 'sort_order', 'is_active'],
            'link' => ['title', 'url', 'icon', 'sort_order', 'is_active'],
        ];

        foreach ($expected as $entity => $fields) {
            self::assertSame(
                $fields,
                array_keys(EntityRegistry::fields($entity)),
                "实体 {$entity} 的字段白名单与预期不符"
            );
        }
    }

    /** 必填项：这些字段为空时后台应拒绝保存。 */
    public function testRequiredFields(): void
    {
        $expected = [
            'category' => ['name'],
            'card' => ['title'],
            'notice' => ['title'],
            'article' => ['title'],
            'showcase' => ['title'],
            'gallery' => ['title'],
            'link' => ['title', 'url'],
            'ad' => [],
        ];
        foreach ($expected as $entity => $fields) {
            self::assertSame(
                $fields,
                EntityRegistry::definition($entity)['require'] ?? [],
                "实体 {$entity} 的必填项与预期不符"
            );
        }
    }

    /** 需要做伪协议过滤的 URL 字段。 */
    public function testUrlFieldsAreFiltered(): void
    {
        $expected = [
            'card' => ['link'],
            'ad' => ['link'],
            'link' => ['url'],
            'category' => [],
            'notice' => [],
            'article' => [],
            'showcase' => [],
            'gallery' => [],
        ];
        foreach ($expected as $entity => $fields) {
            self::assertSame(
                $fields,
                EntityRegistry::definition($entity)['url'] ?? [],
                "实体 {$entity} 的 URL 清洗字段与预期不符"
            );
        }
    }

    /** 字符串截断上限。 */
    public function testMaxLengths(): void
    {
        self::assertSame(['name' => 30], EntityRegistry::definition('category')['max'] ?? []);
    }
}
