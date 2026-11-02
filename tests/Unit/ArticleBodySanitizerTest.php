<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controller\Admin\ApiController;
use PHPUnit\Framework\TestCase;

/**
 * 文章正文净化测试。
 *
 * 正文在详情页按原始 HTML 输出（保留排版），因此写入前必须阻断可执行内容。
 * 当前生产正文已含 1 篇带内联事件处理器的内容，此防线不可缺失。
 */
final class ArticleBodySanitizerTest extends TestCase
{
    public function testRemovesScriptTags(): void
    {
        $html = '<p>正文</p><script>alert(1)</script><p>尾部</p>';
        $out = ApiController::sanitizeArticleBody($html);

        self::assertStringNotContainsString('<script', $out);
        self::assertStringNotContainsString('alert(1)', $out);
        self::assertStringContainsString('<p>正文</p>', $out, '正常内容应保留');
        self::assertStringContainsString('<p>尾部</p>', $out);
    }

    public function testKeepsInlineEventHandlersForLegitInteraction(): void
    {
        // 网盘付费下载链接依赖 onclick 改写 href，不能剥离
        $html = '<a href="https://x.com/a.php" onclick="pay(this.href)">下载</a>';
        $out = ApiController::sanitizeArticleBody($html);

        self::assertStringContainsString('onclick', $out, '正常交互的 onclick 应保留');
        self::assertStringContainsString('下载', $out);
    }

    public function testRemovesJavascriptProtocol(): void
    {
        $html = '<a href="javascript:alert(1)">点我</a><img src="data:text/html,x">';
        $out = ApiController::sanitizeArticleBody($html);

        self::assertStringNotContainsString('javascript:', $out);
        self::assertStringNotContainsString('data:text/html', $out);
        self::assertStringContainsString('点我', $out);
    }

    public function testRemovesEmbedTags(): void
    {
        $html = '<iframe src="https://evil.com"></iframe><object data="x"></object><p>正文</p>';
        $out = ApiController::sanitizeArticleBody($html);

        self::assertStringNotContainsString('<iframe', $out);
        self::assertStringNotContainsString('<object', $out);
        self::assertStringContainsString('<p>正文</p>', $out);
    }

    /** 排版用 HTML 必须原样保留（不能把正文转义成纯文本）。 */
    public function testKeepsFormattingHtml(): void
    {
        $html = '<h2>资源信息</h2><p><strong>内容名称：</strong>示例</p>'
            . '<img src="https://article.91db.cc/articles/a.jpg" alt="图">'
            . '<p>换行<br>测试</p>';
        $out = ApiController::sanitizeArticleBody($html);

        self::assertStringContainsString('<h2>资源信息</h2>', $out);
        self::assertStringContainsString('<strong>内容名称：</strong>', $out);
        self::assertStringContainsString('src="https://article.91db.cc/articles/a.jpg"', $out);
        self::assertStringContainsString('<br>', $out);
    }

    public function testEmptyInputReturnsEmpty(): void
    {
        self::assertSame('', ApiController::sanitizeArticleBody(''));
    }
}
