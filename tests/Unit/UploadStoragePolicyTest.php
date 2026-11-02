<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\UploadService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * 上传存储策略测试：锁定「哪些目录走 R2、哪些只存本地」。
 *
 * 规则（按加载时机）：
 * - 文章封面懒加载，不阻塞首屏 → 走 R2
 * - 卡片图/背景图/广告图/展示位图需秒开 → 只存本地
 *
 * 该策略直接影响首屏加载速度与外网依赖，改动必须有测试兜底。
 */
final class UploadStoragePolicyTest extends TestCase
{
    /**
     * 读取 UploadService 内部的 R2 目录白名单。
     */
    private function r2Subdirs(): array
    {
        $method = new ReflectionMethod(UploadService::class, 'shouldPushToR2');
        $method->setAccessible(true);

        // 用反射取私有常量 R2_SUBDIRS
        $ref = new \ReflectionClass(UploadService::class);
        $const = $ref->getReflectionConstant('R2_SUBDIRS');

        return $const === false ? [] : (array) $const->getValue();
    }

    public function testOnlyArticlesGoToR2(): void
    {
        self::assertSame(['articles'], $this->r2Subdirs());
    }

    /** 需要秒开的目录必须不在 R2 白名单里。 */
    public function testFirstScreenImagesStayLocal(): void
    {
        $r2 = $this->r2Subdirs();

        foreach (['cards', 'background', 'ads', 'showcase'] as $subdir) {
            self::assertNotContains($subdir, $r2, "{$subdir} 属首屏图片，应只存本地");
        }
    }

    /** R2 未启用时全部回退本地，不应抛错。 */
    public function testFallsBackToLocalWhenR2Disabled(): void
    {
        $svc = new UploadService('/tmp', '/uploads', 1024);
        $method = new ReflectionMethod(UploadService::class, 'shouldPushToR2');
        $method->setAccessible(true);

        // 无 R2Service 注入 → 恒为 false
        self::assertFalse($method->invoke($svc, 'articles'));
    }
}
