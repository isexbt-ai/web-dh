<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 媒体上传服务：白名单扩展名 + finfo MIME 校验 + 大小上限。
 * 返回 path 为含 uploads/ 前缀的相对路径（与生产数据库存储格式一致，零迁移）。
 */
final class UploadService
{
    private const IMAGE_MIME_MAP = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    private const VIDEO_MIME_MAP = [
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'ogv' => ['video/ogg'],
        'mov' => ['video/quicktime'],
    ];

    /** @var string[] 允许的图片扩展名 */
    private array $allowedExtensions;

    /** @var string[] 允许的视频扩展名 */
    private array $allowedVideoExtensions;

    public function __construct(
        private string $uploadDir,
        private string $uploadUrlBase,
        private int $maxSize,
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        array $allowedVideoExtensions = ['mp4', 'webm', 'ogv', 'mov']
    ) {
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedVideoExtensions = $allowedVideoExtensions;
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
        if ($mime === false || (self::IMAGE_MIME_MAP[$ext] ?? null) !== $mime) {
            return $fail('文件内容与格式不符');
        }

        return $this->persist($file, $subdir, $ext);
    }

    /**
     * 保存上传视频到 uploads/{$subdir}。大小上限单独放大（默认 200MB）。
     *
     * @param array{name?: string, tmp_name?: string, size?: int, error?: int} $file $_FILES 单项
     * @return array{ok: bool, path: string, url: string, message: string}
     */
    public function video(array $file, string $subdir = 'showcase', int $maxSize = 0): array
    {
        $fail = static fn (string $msg): array => ['ok' => false, 'path' => '', 'url' => '', 'message' => $msg];
        $limit = $maxSize > 0 ? $maxSize : 200 * 1024 * 1024;

        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            $e = (int) ($file['error'] ?? 0);
            if ($e === UPLOAD_ERR_INI_SIZE || $e === UPLOAD_ERR_FORM_SIZE) {
                return $fail('视频文件过大（上限 ' . (int) ($limit / 1024 / 1024) . 'MB）');
            }
            return $fail('上传失败，请重试');
        }
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $fail('上传失败，请重试');
        }
        if ((int) ($file['size'] ?? 0) > $limit) {
            return $fail('视频文件过大（上限 ' . (int) ($limit / 1024 / 1024) . 'MB）');
        }

        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedVideoExtensions, true)) {
            return $fail('不支持的视频格式（' . implode('/', $this->allowedVideoExtensions) . '）');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $accepted = self::VIDEO_MIME_MAP[$ext] ?? [];
        if ($mime === false || !in_array($mime, $accepted, true)) {
            return $fail('文件内容与格式不符');
        }

        return $this->persist($file, $subdir, $ext);
    }

    /** 落盘并返回 path/url。 */
    private function persist(array $file, string $subdir, string $ext): array
    {
        $dir = rtrim($this->uploadDir, '/') . '/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'path' => '', 'url' => '', 'message' => '保存失败，请重试'];
        }

        $sub = trim($subdir, '/');
        return [
            'ok' => true,
            'path' => 'uploads/' . $sub . '/' . $filename,
            'url' => $this->uploadUrlBase . '/' . $sub . '/' . $filename,
            'message' => '',
        ];
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
