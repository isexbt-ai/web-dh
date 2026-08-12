<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 图片上传服务：白名单扩展名 + finfo MIME 校验 + 大小上限。
 * 返回 path 为含 uploads/ 前缀的相对路径（与生产数据库存储格式一致，零迁移）。
 */
final class UploadService
{
    private const MIME_MAP = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    /** @var string[] 允许的扩展名 */
    private array $allowedExtensions;

    public function __construct(
        private string $uploadDir,
        private string $uploadUrlBase,
        private int $maxSize,
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ) {
        $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * 保存上传图片到 uploads/{$subdir}。
     *
     * @param array{name?: string, tmp_name?: string, size?: int, error?: int} $file $_FILES 单项
     * @return array{ok: bool, path: string, url: string, message: string} path 相对 uploads 根，url 可作 img src
     */
    public function image(array $file, string $subdir = 'cards'): array
    {
        $fail = static fn (string $msg): array => ['ok' => false, 'path' => '', 'url' => '', 'message' => $msg];

        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return $fail('上传失败，请重试');
        }
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $fail('上传失败，请重试');
        }
        if ((int) ($file['size'] ?? 0) > $this->maxSize) {
            return $fail('文件大小超出限制');
        }

        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions, true)) {
            return $fail('不支持的图片格式');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($mime === false || (self::MIME_MAP[$ext] ?? null) !== $mime) {
            return $fail('文件内容与格式不符');
        }

        $dir = rtrim($this->uploadDir, '/') . '/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return $fail('保存失败，请重试');
        }

        $path = 'uploads/' . trim($subdir, '/') . '/' . $filename;
        return ['ok' => true, 'path' => $path, 'url' => $this->uploadUrlBase . '/' . trim($subdir, '/') . '/' . $filename, 'message' => ''];
    }

    /** 安全删除 uploads 内文件：realpath 校验目标在 upload 根目录内。 */
    public function delete(string $relativePath): bool
    {
        $root = realpath($this->uploadDir);
        $full = realpath($this->uploadDir . '/' . ltrim($relativePath, '/'));
        if ($root === false || $full === false || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
            return false;
        }
        return @unlink($full);
    }
}
