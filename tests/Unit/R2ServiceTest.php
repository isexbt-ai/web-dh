<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\R2Service;
use PHPUnit\Framework\TestCase;

/**
 * R2Service 测试：URL 拼接与签名正确性。
 *
 * 这两个点曾各造成一次线上故障：
 * - public_url 未带协议 → 生成的 URL 无法用于 img src
 * - DELETE 签名用空串而非空内容 SHA256 → 403 SignatureDoesNotMatch
 */
final class R2ServiceTest extends TestCase
{
    private function service(string $publicUrl): R2Service
    {
        return new R2Service([
            'enabled' => true,
            'account_id' => 'acct',
            'access_key_id' => 'akid',
            'secret_access_key' => 'secret',
            'bucket' => 'bucket',
            'public_url' => $publicUrl,
        ]);
    }

    /** public_url 未带协议时补 https://（否则 img src 无效）。 */
    public function testUrlAddsHttpsWhenProtocolMissing(): void
    {
        $svc = $this->service('article.example.com');
        self::assertSame('https://article.example.com/articles/a.jpg', $svc->url('articles/a.jpg'));
    }

    /** 已带协议时保持原样。 */
    public function testUrlKeepsExistingProtocol(): void
    {
        $svc = $this->service('https://article.example.com');
        self::assertSame('https://article.example.com/articles/a.jpg', $svc->url('articles/a.jpg'));

        $svc2 = $this->service('http://localhost:9000');
        self::assertSame('http://localhost:9000/x.jpg', $svc2->url('x.jpg'));
    }

    /** 末尾斜杠不应产生双斜杠。 */
    public function testUrlNormalizesTrailingSlash(): void
    {
        $svc = $this->service('https://article.example.com/');
        self::assertSame('https://article.example.com/x.jpg', $svc->url('/x.jpg'));
    }

    public function testEnabledReflectsConfig(): void
    {
        self::assertTrue($this->service('https://a.com')->enabled());
        self::assertFalse((new R2Service(['enabled' => false]))->enabled());
    }

    /**
     * DELETE 签名必须用空内容的 SHA256（S3 规范），
     * 用空串会导致 R2 返回 403 SignatureDoesNotMatch。
     */
    public function testDeleteSignatureUsesSha256OfEmptyBody(): void
    {
        $svc = $this->service('https://a.com');
        $ref = new \ReflectionMethod(R2Service::class, 'signedHeaders');
        $ref->setAccessible(true);

        $headers = $ref->invoke($svc, 'DELETE', 'k', hash('sha256', ''), '');
        $joined = implode("\n", $headers);
        self::assertStringContainsString(hash('sha256', ''), $joined, 'DELETE 签名应含空内容 SHA256');
    }

    /** PUT 签名必须用实际内容的 SHA256。 */
    public function testPutSignatureUsesSha256OfBody(): void
    {
        $svc = $this->service('https://a.com');
        $ref = new \ReflectionMethod(R2Service::class, 'signedHeaders');
        $ref->setAccessible(true);

        $body = 'hello';
        $headers = $ref->invoke($svc, 'PUT', 'k', hash('sha256', $body), 'image/png');
        self::assertStringContainsString(hash('sha256', $body), implode("\n", $headers));
    }
}
