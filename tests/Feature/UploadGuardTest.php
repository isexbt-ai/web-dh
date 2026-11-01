<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Service\UploadService;
use PHPUnit\Framework\TestCase;

/**
 * 上传安全测试：扩展名白名单、MIME 校验、子目录白名单。
 *
 * 这些是防止「上传任意文件 / 目录穿越」的关键防线，
 * 任何放宽都会造成远程代码执行或任意文件写入，必须锁死。
 */
final class UploadGuardTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/upload_test_' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    private function service(): UploadService
    {
        return new UploadService($this->tmpDir, '/uploads', 1024 * 1024);
    }

    /** 构造真实 PNG 内容（用于 MIME 校验）。 */
    private function pngFile(string $name = 'a.png'): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        return $path;
    }

    /** 扩展名不在白名单必须被拒绝。 */
    public function testDisallowedExtensionsAreRejected(): void
    {
        $path = $this->tmpDir . '/evil.php';
        file_put_contents($path, '<?php echo 1;');

        $error = $this->service()->validateImage('evil.php', $path);
        self::assertSame('不支持的图片格式', $error);
    }

    /** 扩展名合法但内容不是图片（伪装扩展名）必须被拒绝。 */
    public function testContentMustMatchExtension(): void
    {
        $path = $this->tmpDir . '/fake.png';
        file_put_contents($path, '<?php system($_GET["c"]); ?>');

        $error = $this->service()->validateImage('fake.png', $path);
        self::assertSame('文件内容与格式不符', $error);
    }

    /** 合法 PNG 通过校验。 */
    public function testValidPngPassesValidation(): void
    {
        $path = $this->pngFile('good.png');
        self::assertNull($this->service()->validateImage('good.png', $path));
    }

    /** 各白名单扩展名与 MIME 的对应关系必须严格。 */
    public function testEveryAllowedExtensionHasMimeMapping(): void
    {
        $svc = $this->service();
        foreach (['a.jpg', 'a.jpeg', 'a.png', 'a.gif', 'a.webp'] as $name) {
            $path = $this->tmpDir . '/' . $name;
            // 任意内容都不应崩溃，且必须返回明确错误而非放行
            file_put_contents($path, 'not a real image');
            self::assertNotNull($svc->validateImage($name, $path), "{$name} 内容不匹配时应报错");
        }
    }

    /** 超过大小上限必须被拒绝。 */
    public function testOversizedFileIsRejected(): void
    {
        $svc = new UploadService($this->tmpDir, '/uploads', 10);
        self::assertSame('文件大小超出限制', $svc->validateSize(100));
        self::assertNull($svc->validateSize(5));
    }

    /** 显式传入上限时以传入值为准。 */
    public function testExplicitMaxSizeOverridesDefault(): void
    {
        $svc = new UploadService($this->tmpDir, '/uploads', 1024);
        self::assertSame('文件大小超出限制', $svc->validateSize(2048, 1024));
        self::assertNull($svc->validateSize(512, 1024));
    }

    /** 安全删除：只能删 uploads 目录内的文件，穿越路径必须失败。 */
    public function testDeleteRejectsPathTraversal(): void
    {
        $outside = $this->tmpDir . '/../outside_target.txt';
        file_put_contents($outside, 'x');

        $svc = $this->service();
        self::assertFalse($svc->delete('../outside_target.txt'));

        @unlink($outside);
    }

    /** 安全删除：目录内的文件应能删除。 */
    public function testDeleteRemovesFileInsideUploadDir(): void
    {
        file_put_contents($this->tmpDir . '/inside.txt', 'x');
        $svc = $this->service();
        self::assertTrue($svc->delete('inside.txt'));
        self::assertFileDoesNotExist($this->tmpDir . '/inside.txt');
    }
}
