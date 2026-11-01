<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 全局辅助函数测试：重点覆盖背景图 URL 过滤（XSS / 伪协议 / 路径穿越）。
 */
final class HelpersTest extends TestCase
{
    public function testImageUrlKeepsAbsoluteUrls(): void
    {
        self::assertSame('https://a.com/b.jpg', image_url('https://a.com/b.jpg'));
        self::assertSame('http://a.com/b.jpg', image_url('http://a.com/b.jpg'));
        self::assertSame('/uploads/a.jpg', image_url('/uploads/a.jpg'));
        self::assertSame('data:image/png;base64,AA', image_url('data:image/png;base64,AA'));
    }

    public function testImageUrlAddsLeadingSlashForRelativePaths(): void
    {
        self::assertSame('/uploads/cards/a.jpg', image_url('uploads/cards/a.jpg'));
    }

    public function testImageUrlReturnsEmptyForEmptyInput(): void
    {
        self::assertSame('', image_url(''));
    }

    /** 背景图地址：允许站内路径与 http(s) 绝对地址。 */
    public function testBgImageCssAllowsSafeUrls(): void
    {
        self::assertSame('url("/uploads/background/a.jpg")', bg_image_css('/uploads/background/a.jpg'));
        self::assertSame('url("/uploads/background/a.jpg")', bg_image_css('uploads/background/a.jpg'));
        self::assertSame('url("https://cdn.example.com/a.jpg")', bg_image_css('https://cdn.example.com/a.jpg'));
    }

    /** 伪协议与协议相对地址必须拒绝，避免 CSS 注入与外站探测。 */
    public function testBgImageCssRejectsDangerousUrls(): void
    {
        $dangerous = [
            'javascript:alert(1)',
            'JaVaScRiPt:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'vbscript:msgbox(1)',
            '//evil.com/x.jpg',
            'evil.jpg',
            '',
            '   ',
        ];
        foreach ($dangerous as $url) {
            self::assertSame('', bg_image_css($url), "应拒绝: {$url}");
        }
    }

    /** CSS 特殊字符必须被剔除，不能破坏 url() 结构。 */
    public function testBgImageCssStripsCssBreakingCharacters(): void
    {
        $result = bg_image_css('/uploads/a.jpg");background:url(x');
        // 去掉外层 url(" ... ") 包裹
        $inner = substr($result, 5, -2);
        self::assertStringNotContainsString('"', $inner, '引号必须被剔除');
        self::assertStringNotContainsString('(', $inner, '左括号必须被剔除');
        self::assertStringNotContainsString(')', $inner, '右括号必须被剔除');
        self::assertStringStartsWith('/uploads/a.jpg', $inner);
    }

    public function testBgImageCssReturnsEmptyForEmptyInput(): void
    {
        self::assertSame('', bg_image_css(''));
    }
}
